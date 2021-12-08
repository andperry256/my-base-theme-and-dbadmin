<?php

class tables_transactions
{
	function account__validate($record, $value)
	{
		if (empty($value))
		{
			return report_error("Account not specified.");
		}
		else
			return true;
	}

	function date__validate($record, $value)
	{
		if (!DateIsValid($value))
		{
			return report_error("Invalid date.");
		}
		else
			return true;
	}

	function seq_no__validate($record, $value)
	{
		$action = $record->action;
		$table = $record->table;
		if (!empty($record->OldPKVal('account')))
		{
			$old_account = $record->OldPKVal('account');
		}
		elseif (substr($table,0,14) == "_view_account_")
		{
			$old_account = substr($table,14,strlen($table)-14);
		}
		else
		{
			$old_account = '';
		}
		$account = $record->FieldVal('account');
		if (($account != $old_account) && ($value != NEXT_SEQ_NO_INDICATOR))
		{
			return report_error("Sequence number must be ".NEXT_SEQ_NO_INDICATOR." for a new/changed account field.");
		}
		else
			return true;
	}

	function acct_month__validate($record, $value)
	{
		if (empty($value))#
		{
			return true;
		}
		$year = (int)substr($value,0,4);
		$separator = substr($value,4,1);
		$month = (int)substr($value,5,2);
		if (($year < 2000) || ($year > 2099) || ( $month < 1) || ($month > 12) || ($separator != '-'))
		{
			return report_error("Invalid accounting month.");
		}
		else
		{
			return true;
		}
	}

	function credit_amount__validate($record, $value)
	{
		if (((!is_numeric($value)) && (!empty($value))) || ($value > MAX_TRANSACTION_VALUE) || ($value < -MAX_TRANSACTION_VALUE))
		{
			return report_error("Invalid credit amount.");
		}
		else
			return true;
	}

	function debit_amount__validate($record, $value)
	{
		if (((!is_numeric($value)) && (!empty($value))) || ($value > MAX_TRANSACTION_VALUE) || ($value < -MAX_TRANSACTION_VALUE))
		{
			return report_error("Invalid debit amount.");
		}
		else
		{
			return true;
		}
	}

	function target_account__validate($record, $value)
	{
		if (!empty($value))
		{
			$db = admin_db_connect();
			$query_result = mysqli_query($db,"SELECT * FROM accounts WHERE label='$value[0]'");
			if (($row = mysqli_fetch_assoc($query_result)) && ($row['currency'] != $record->FieldVal('currency')))
			{
				return report_error("Attempt create transfer with account of different currency.");
			}
		}
		return true;
	}

	function beforeDelete($record)
	{
		$reconciled = $record->FieldVal('reconciled');
		if ($reconciled)
		{
			return report_error("Cannot delete record while reconciled status is set.");
		}
	}

	function delete_record__validate($record, $value)
	{
		$reconciled = $record->FieldVal('reconciled');
		$source_account = $record->FieldVal('source_account');
		$source_seq_no = $record->FieldVal('source_seq_no');
		if (($value) && ($reconciled))
		{
			return report_error("Cannot delete record while reconciled status is set.");
		}
		elseif (($value) && ((!empty($source_account)) || (!empty($source_seq_no))))
		{
			return report_error("Cannot delete record here - please do so by unlinking transfer from other side.");
		}
		else
		{
			return true;
		}
	}

	function afterDelete($record)
	{
		$db = admin_db_connect();

		$account = $record->FieldVal('account');
		$seq_no = $record->FieldVal('seq_no');
		$date = $record->FieldVal('date');
		$target_account = $record->FieldVal('target_account');
		$target_seq_no = $record->FieldVal('target_seq_no');
		if (!empty($target_account))
		{
			unlink_transaction($target_account,$target_seq_no);
		}
		update_account_balances($account,$date);
	}

	function beforeSave($record)
	{
		$db = admin_db_connect();
		$action = $record->action;
		$table = $record->table;
		$account = $record->FieldVal('account');
		$seq_no = $record->FieldVal('seq_no');
		$credit_amount = $record->FieldVal('credit_amount');
		$debit_amount = $record->FieldVal('debit_amount');
		$target_account = $record->FieldVal('target_account');
		$copy_to_date = $record->FieldVal('copy_to_date');

		$query_result = mysqli_query($db,"SELECT * FROM transactions WHERE account='$account' AND seq_no=$seq_no");
		if ($row = mysqli_fetch_assoc($query_result))
		{
			$old_target_account = $row['target_account'];
			$old_target_seq_no = $row['target_seq_no'];
		}
		else
		{
			$old_target_account = '';
			$old_target_seq_no = '';
		}

		if 	($table == '_ctab_new_transaction')
		{
			$target_seq_no = '';
			$source_account = '';
			$source_seq_no = '';
		}
		else
		{
			$target_seq_no = $record->FieldVal('target_seq_no');
			$source_account = $record->FieldVal('source_account');
			$source_seq_no = $record->FieldVal('source_seq_no');
			if ((empty($target_account)) && (!empty($target_seq_no)))
			{
				return report_error("Target sequence number set without an account.");
			}
		}

		// Check for various error conditions
		if ((!empty($target_account)) && (!empty($target_seq_no)))
		{
			$query_result = mysqli_query($db,"SELECT * FROM transactions WHERE account='$target_account' AND seq_no=$target_seq_no");
			if (($row = mysqli_fetch_assoc($query_result)) &&
			    ($row['reconciled'] ) &&
			    (($row['credit_amount']!= $debit_amount) || ($row['debit_amount']!= $credit_amount)))
			{
				return report_error("Amount conflicts with reconciled record at other end of transfer.");
			}
		}
		if (!empty($source_account))
		{
			$query_result = mysqli_query($db,"SELECT * FROM transactions WHERE account='$source_account' AND seq_no=$source_seq_no");
			if (($row = mysqli_fetch_assoc($query_result)) &&
			    ($row['reconciled'] ) &&
			    (($row['credit_amount']!= $debit_amount) || ($row['debit_amount']!= $credit_amount)))
			{
				return report_error("Amount conflicts with reconciled record at other end of transfer.");
			}
		}
		if (($credit_amount != 0) && ($debit_amount != 0))
		{
			return report_error("Credit and debit amounts both specified.");
		}
		elseif ((!empty($target_account)) && (!empty($source_account)))
		{
			return report_error("Attempt to set both source and target accounts.");
		}
		elseif ((!empty($old_target_account)) && (!empty($old_target_seq_no)) &&
		    (($target_account != $old_target_account) || ($target_seq_no != $old_target_seq_no)) &&
			((!empty($target_account)) || (!empty($target_seq_no))))
		{
			return report_error("Attempt to modify transfer link - please break then re-create.");
		}
		elseif ((!empty($source_account)) && (!empty($copy_to_date)))
		{
			return report_error("Attempt to copy transaction that is at the target end of a transfer.");
		}

		// All error checks passed - OK to make permanent changes
		if ((!empty($old_target_account)) && (empty($target_account)))
		{
			unlink_transaction($old_target_account,$old_target_seq_no);
		}
	}

	function afterSave($record)
	{
		global $BaseURL, $RelativePath;

		$db = admin_db_connect();
		$action = $record->action;
		$table = $record->table;

		$account = $record->FieldVal('account');
		$date = $record->FieldVal('date');
		$chq_no = $record->FieldVal('chq_no');
		$payee = addslashes($record->FieldVal('payee'));
		$credit_amount = $record->FieldVal('credit_amount');
		$debit_amount = $record->FieldVal('debit_amount');
		$auto_total = $record->FieldVal('auto_total');
		$fund = $record->FieldVal('fund');
		$category = $record->FieldVal('category');
		$memo = addslashes($record->FieldVal('memo'));
		$acct_month = $record->FieldVal('acct_month');
		$change_acct_month = $record->FieldVal('change_acct_month');
		$target_account = $record->FieldVal('target_account');
		$sched_freq = $record->FieldVal('sched_freq');
		$sched_count = $record->FieldVal('sched_count');
		$record_sched = $record->FieldVal('record_sched');
		$save_defaults = $record->FieldVal('save_defaults');
		$seq_no = $record->FieldVal('seq_no');
		$target_seq_no = $record->FieldVal('target_seq_no');
		$source_account = $record->FieldVal('source_account');
		$source_seq_no = $record->FieldVal('source_seq_no');
		$bank_import_id = $record->FieldVal('bank_import_id');
		$copy_to_date = $record->FieldVal('copy_to_date');
		$delete_record = $record->FieldVal('delete_record');

		$query_result = mysqli_query($db,"SELECT * FROM transactions WHERE account='$account' AND seq_no=$seq_no");
		if ($row = mysqli_fetch_assoc($query_result))
		{
			$date = $row['date'];
			$payee = addslashes($row['payee']);
		}

		$primary_keys = array();
		$primary_keys['account'] = $account;
		$primary_keys['seq_no'] = $seq_no;

		if ($delete_record)
		{
			// Delete flag is set
			$query_result = mysqli_query($db,"DELETE FROM transactions WHERE account='$account' AND seq_no=$seq_no");
			$this->afterDelete($record);
			return;
		}
		elseif ($seq_no == NEXT_SEQ_NO_INDICATOR)
		{
			// New record or change of account
			$seq_no = next_seq_number('transactions',$account);
			mysqli_query($db,"UPDATE transactions SET seq_no=$seq_no WHERE account='$account' AND seq_no=".NEXT_SEQ_NO_INDICATOR);
			$primary_keys['seq_no'] = $seq_no;
			update_session_var('saved_record_id',encode_record_id($primary_keys));
		}
		$old_account = $record->OldPKVal('account');
		$old_seq_no = $record->OldPKVal('seq_no');

		// Re-link any splits if the transaction primary keys have changed.
		// Can leave the split sequence numbers intact as they are specific to the individual transaction.
		if (($account != $old_account) || ($seq_no != $old_seq_no))
		{
			mysqli_query($db,"UPDATE splits SET account='$account',transact_seq_no=$seq_no WHERE account='$old_account' AND transact_seq_no=$old_seq_no");
		}

		// Get account currency
		$query_result = mysqli_query($db,"SELECT * FROM accounts WHERE label='$account'");
		if ($row = mysqli_fetch_assoc($query_result))
		{
			$account_currency = $row['currency'];
		}
		else
		{
			exit("This should not occur");
		}

		// Add payee to payees table
		$query_result = mysqli_query($db,"SELECT * FROM payees WHERE name='$payee'");
		if (mysqli_num_rows($query_result) == 0)
		{
			mysqli_query($db,"INSERT INTO payees (name) VALUES ('$payee')");
		}

		// Adjust credit/debit amounts as necessary.
		if ($auto_total)
		{
			$total = 0;
			$query_result = mysqli_query($db,"SELECT * FROM splits WHERE account='$account' AND transact_seq_no=$seq_no");
			while ($row = mysqli_fetch_assoc($query_result))
			{
				// Add value of split to total
				$total = add_money($total,subtract_money($row['credit_amount'],$row['debit_amount']));
			}
			if ($total >= 0)
			{
				$credit_amount = $total;
				$debit_amount = 0;
			}
			else
			{
				$debit_amount = -$total;
				$credit_amount = 0;
			}
		}
		if ($credit_amount < 0)
		{
			$debit_amount = -$credit_amount;
			$credit_amount = 0;
		}
		elseif ($debit_amount < 0)
		{
			$credit_amount = -$debit_amount;
			$debit_amount = 0;
		}
		else
		{
			// Comment both zero - no action.
		}

		// Set/clear transfer category as required.
		if ((!empty($source_account)) || (!empty($target_account)))
		{
			$category = '-transfer-';
		}
		elseif ((empty($source_account)) && (empty($target_account)) && ($category == '-transfer-'))
		{
			$category = '-none-';
		}

		// Select default category for fund or vice versa where appropriate.
		if (($fund != '-default-') && ($category == '-default-'))
		{
			$query_result = mysqli_query($db,"SELECT * FROM funds WHERE name='$fund'");
			if ($row = mysqli_fetch_assoc($query_result))
			{
				if ((!empty($row['default_income_cat'])) && ($credit_amount > 0))
				{
					$category = $row['default_income_cat'];
				}
				elseif ((!empty($row['default_expense_cat'])) && ($debit_amount > 0))
				{
					$category = $row['default_expense_cat'];
				}
			}
		}
		elseif (($category != '-default-') && ($fund == '-default-'))
		{
			$query_result = mysqli_query($db,"SELECT * FROM categories WHERE name='$category'");
			if (($row = mysqli_fetch_assoc($query_result)) && (!empty($row['default_fund'])))
			{
				$fund = $row['default_fund'];
			}
		}

		// For each of the fund and category, check if it indicates default at this
		// point. If so then check if there is a default value for the given payee,
		// otherwise use the global default value.
		if ($fund == '-default-')
		{
			$query_result = mysqli_query($db,"SELECT * FROM payees WHERE name='$payee'");
			if (($row = mysqli_fetch_assoc($query_result)) && (!empty($row['default_fund'])))
			{
				$fund = $row['default_fund'];
			}
			else
			{
				$fund = 'General';
			}
		}
		if ($category == '-default-')
		{
			$query_result = mysqli_query($db,"SELECT * FROM payees WHERE name='$payee'");
			if (($row = mysqli_fetch_assoc($query_result)) && (!empty($row['default_cat'])))
			{
				$category = $row['default_cat'];
			}
			else
			{
				$category = '-none-';
			}
		}

		// Save default fund and/or category for payee where 'save defaults' option
		// has been selected.
		if (($save_defaults) && (substr($fund,0,1) != '-'))
		{
			mysqli_query($db,"UPDATE payees SET default_fund='$fund' WHERE name='$payee'");
		}
		if (($save_defaults) && (substr($category,0,1) != '-'))
		{
			mysqli_query($db,"UPDATE payees SET default_category='$category' WHERE name='$payee'");
		}

		if ((empty($acct_month)) || ($change_acct_month == 0))
		{
			$acct_month = accounting_month($date);
		}

		if ($table == '_ctab_new_transaction')
		{
			// Save record in main table
			if (empty($credit_amount))
			{
				$credit_amount = 0;
			}
			if (empty($debit_amount))
			{
				$debit_amount = 0;
			}
			if (empty($sched_count))
			{
				$sched_count = 0;
			}
			$query = "INSERT INTO transactions (account,seq_no,date,currency,payee,credit_amount,debit_amount,fund,category,memo,acct_month,target_account,sched_freq,sched_count,save_defaults) VALUES ('$account',$seq_no,'$date','$account_currency','$payee',$credit_amount,$debit_amount,'$fund','$category','$memo','$acct_month','$target_account','$sched_freq',$sched_count,$save_defaults)";
			if (!empty($chq_no))
			{
				$query = str_replace('date,','date,chq_no,',$query);
				$query = str_replace("'$date',","'$date','$chq_no',",$query);
			}
			mysqli_query($db,$query);
		}
		else
		{
			// Re-update record
			mysqli_query($db,"UPDATE transactions SET seq_no=$seq_no,currency='$account_currency',credit_amount=$credit_amount,debit_amount=$debit_amount,auto_total=0,fund='$fund',category='$category',acct_month='$acct_month',save_defaults=0 WHERE account='$account' AND seq_no=$seq_no");
			if ($sched_freq != '#')
			{
				mysqli_query($db,"UPDATE transactions SET cleared_balance=0.00,full_balance=0.00 WHERE account='$account' AND seq_no=$seq_no");
			}
		}

		// Handle auto-reconciliation if record has been created via the reconcile screen
		if ($bank_import_id != 0 )
		{
			$query_result = mysqli_query($db,"SELECT * FROM bank_import WHERE rec_id=$bank_import_id");
			if (($row = mysqli_fetch_assoc($query_result)) &&
			    (($row['amount'] = $credit_amount) || ($row['amount'] = -$debit_amount)))
			{
				mysqli_query($db,"UPDATE transactions SET reconciled=1 WHERE account='$account' AND seq_no=$seq_no");
				mysqli_query($db,"UPDATE bank_import SET reconciled=1 WHERE rec_id=$bank_import_id");
			}
			mysqli_query($db,"UPDATE transactions SET bank_import_id=0 WHERE account='$account' AND seq_no=$seq_no");
		}

		// Update account balances
		update_account_balances($account,$date);

		// Update appropriate fields on other side of transfer
		if ((!empty($target_account)) && (!empty($target_seq_no)))
		{
			mysqli_query($db,"UPDATE transactions SET date='$date',credit_amount=$debit_amount,debit_amount=$credit_amount,fund='$fund',acct_month='$acct_month' WHERE account='$target_account' AND seq_no=$target_seq_no");
			update_account_balances($target_account,$date);
		}
		if ((!empty($source_account)) && (!empty($source_seq_no)))
		{
			mysqli_query($db,"UPDATE transactions SET date='$date',credit_amount=$debit_amount,debit_amount=$credit_amount,fund='$fund',acct_month='$acct_month' WHERE account='$source_account' AND seq_no=$source_seq_no");
			update_account_balances($source_account,$date);
		}

		if (!empty($copy_to_date))
		{
			// Make copy of transaction
			copy_transaction($account,$seq_no,$copy_to_date);
			mysqli_query($db,"UPDATE transactions SET copy_to_date=NULL WHERE account='$account' AND seq_no=$seq_no");
		}

		if ($sched_freq == '#')
		{
			// Normal transaction
			if ((($table == '_ctab_new_transaction') && (!empty($target_account))) ||
				(($table == "_view_account_$account") && (!empty($target_account)) && (empty($target_seq_no))))
			{
				// Create transfer
				$target_seq_no = next_seq_no($target_account);
				update_account_balances($target_account,$date);
				mysqli_query($db,"INSERT INTO transactions (account,seq_no,date,currency,payee,credit_amount,debit_amount,fund,category,memo,acct_month,source_account,source_seq_no) VALUES ('$target_account',$target_seq_no,'$date','$account_currency','$payee',$debit_amount,$credit_amount,'$fund','-transfer-','$memo','$acct_month','$account',$seq_no)");
				update_account_balances($target_account,$date);
				mysqli_query($db,"UPDATE transactions SET category='-transfer-',target_seq_no=$target_seq_no WHERE account='$account' AND seq_no=$seq_no");
			}
			elseif ((!empty($target_account)) && (!empty($target_seq_no)))
			{
				// Update links on target account in case this transactiop has changed account
				mysqli_query($db,"UPDATE transactions SET source_account='$account',source_seq_no=$seq_no WHERE account='$target_account' AND seq_no=$target_seq_no");
			}
			elseif ((!empty($source_account)) && (!empty($source_seq_no)))
			{
				// Update links on source account in case this transaction has changed account
				mysqli_query($db,"UPDATE transactions SET target_account='$account',target_seq_no=$seq_no WHERE account='$source_account' AND seq_no=$source_seq_no");
			}
			if (!headers_sent())
			{
				$record_id = encode_record_id($primary_keys);
				header("Location: $BaseURL/$RelativePath/?-action=edit&-table=$table&-recordid=$record_id&summary");
				exit;
			}
		}
		else
		{
			// Scheduled transaction
			mysqli_query($db,"UPDATE splits SET acct_month='$acct_month' WHERE account='$account' AND transact_seq_no=$seq_no");
			if ($record_sched)
			{
				record_scheduled_transaction($account,$seq_no);
				mysqli_query($db,"UPDATE transactions SET record_sched=0 WHERE account='$account' AND seq_no=$seq_no");
			}
		}
	}
}
?>
