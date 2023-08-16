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
		  $where_clause = 'label=?';
		  $where_values = array('s',$value[0]);
		  $query_result = mysqli_select_query($db,'accounts','*',$where_clause,$where_values,'');
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

	  $where_clause = 'account=? AND seq_no=?';
	  $where_values = array('s',$account,'i',$seq_no);
	  $query_result = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
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
		  $where_clause = 'account=? AND seq_no=?';
		  $where_values = array('s',$target_account,'i',$target_seq_no);
		  $query_result = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
			if (($row = mysqli_fetch_assoc($query_result)) &&
			    ($row['reconciled'] ) &&
			    (($row['credit_amount']!= $debit_amount) || ($row['debit_amount']!= $credit_amount)))
			{
				return report_error("Amount conflicts with reconciled record at other end of transfer.");
			}
		}
		if (!empty($source_account))
		{
		  $where_clause = 'account=? AND seq_no=?';
		  $where_values = array('s',$source_account,'i',$source_seq_no);
		  $query_result = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
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
		$payee = $record->FieldVal('payee');
		$credit_amount = $record->FieldVal('credit_amount');
		$debit_amount = $record->FieldVal('debit_amount');
		$auto_total = $record->FieldVal('auto_total');
		$fund = $record->FieldVal('fund');
		$category = $record->FieldVal('category');
		$memo = $record->FieldVal('memo');
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

	  $where_clause = 'account=? AND seq_no=?';
	  $where_values = array('s',$account,'i',$seq_no);
	  $query_result = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
		if ($row = mysqli_fetch_assoc($query_result))
		{
			$date = $row['date'];
			$payee = $row['payee'];
		}

		$primary_keys = array();
		$primary_keys['account'] = $account;
		$primary_keys['seq_no'] = $seq_no;

		if ($delete_record)
		{
			// Delete record
			delete_record_on_save($record);
			return;
		}
		$old_account = $record->OldPKVal('account');
		$old_seq_no = $record->OldPKVal('seq_no');

		// Re-link any splits if the transaction primary keys have changed.
		// Can leave the split sequence numbers intact as they are specific to the individual transaction.
		if ((!empty($old_account)) && (($account != $old_account) || ($seq_no != $old_seq_no)))
		{
			$set_fields = 'account,transact_seq_no';
		  $set_values = array('s',$account,'i',$seq_no);
		  $where_clause = 'account=? AND transact_seq_no=?';
		  $where_values = array('s',$old_account,'i',$old_seq_no);
		  mysqli_update_query($db,'splits',$set_fields,$set_values,$where_clause,$where_values);
		}

		// Get account currency
	  $where_clause = 'label=?';
	  $where_values = array('s',$account);
	  $query_result = mysqli_select_query($db,'accounts','*',$where_clause,$where_values,'');
		if ($row = mysqli_fetch_assoc($query_result))
		{
			$account_currency = $row['currency'];
		}
		else
		{
			exit("This should not occur");
		}

		// Add payee to payees table
	  $where_clause = 'name=?';
	  $where_values = array('s',$payee);
	  $query_result = mysqli_select_query($db,'payees','*',$where_clause,$where_values,'');
		if (mysqli_num_rows($query_result) == 0)
		{
			$fields = 'name';
		  $values = array('s','$payee');
		  mysqli_insert_query($db,'payees',$fields,$values);
		}

		// Adjust credit/debit amounts as necessary.
		if ($auto_total)
		{
			$total = 0;
		  $where_clause = 'account=? AND transact_seq_no=?';
		  $where_values = array('s',$account,'i',$seq_no);
		  $query_result = mysqli_select_query($db,'splits','*',$where_clause,$where_values,'');
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
		  $where_clause = 'name=?';
		  $where_values = array('s',$fund);
		  $query_result = mysqli_select_query($db,'funds','*',$where_clause,$where_values,'');
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
		  $where_clause = 'name=?';
		  $where_values = array('s',$category);
		  $query_result = mysqli_select_query($db,'categories','*',$where_clause,$where_values,'');
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
		  $where_clause1 = 'name=?';
		  $where_values1 = array('s',$payee);
			$where_clause2 = 'label=?';
			$where_values2 = array('s',$account);
			if (($row = mysqli_fetch_assoc(mysqli_select_query($db,'payees','*',$where_clause1,$where_values1,''))) && (!empty($row['default_fund'])))
			{
				$fund = $row['default_fund'];
			}
			elseif (($row = mysqli_fetch_assoc(mysqli_select_query($db,'accounts','*',$where_clause2,$where_values2,''))) && (!empty($row['default_fund'])))
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
		  $where_clause = 'name=?';
		  $where_values = array('s',$payee);
		  $query_result = mysqli_select_query($db,'payees','*',$where_clause,$where_values,'');
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
			$set_fields = 'default_fund';
		  $set_values = array('s',$fund);
		  $where_clause = 'name=?';
		  $where_values = array('s',$payee);
		  mysqli_update_query($db,'payees',$set_fields,$set_values,$where_clause,$where_values);
		}
		if (($save_defaults) && (substr($category,0,1) != '-'))
		{
			$set_fields = 'default_cat';
		  $set_values = array('s',$category);
		  $where_clause = 'name=?';
		  $where_values = array('s',$payee);
		  mysqli_update_query($db,'payees',$set_fields,$set_values,$where_clause,$where_values);
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
			$fields = 'account,seq_no,date,currency,payee,credit_amount,debit_amount,fund,category,memo,acct_month,target_account,sched_freq,sched_count,save_defaults';
		  $values = array('s',$account,'i',$seq_no,'s',$date,'s',$account_currency,'s',$payee,'d',$credit_amount,'d',$debit_amount,'s',$fund,'s',$category,'s',$memo,'s',$acct_month,'s',$target_account,'s',$sched_freq,'i',$sched_count,'i',$save_defaults);
			if (!empty($chq_no))
			{
				$fields .= ',chq_no,';
				$values[count($values)] = 'i';
				$values[count($values)] = $chq_no;
			}
			mysqli_insert_query($db,'transactions',$fields,$values);
		}
		else
		{
			// Re-update record
			$set_fields = 'seq_no,acct_month,currency,credit_amount,debit_amount,auto_total,fund,category,save_defaults';
		  $set_values = array('i',$seq_no,'s',$acct_month,'s',$account_currency,'d',$credit_amount,'d',$debit_amount,'i',0,'s',$fund,'s',$category,'i',0);
		  $where_clause = 'account=? AND seq_no=?';
		  $where_values = array('s',$account,'i',$seq_no);
		  mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
			if ($sched_freq != '#')
			{
				$set_fields = 'reconciled_balance,full_balance';
			  $set_values = array('d','0.00','d','0.00');
			  $where_clause = 'account=? AND seq_no=?';
			  $where_values = array('s',$account,'i',$seq_no);
			  mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
			}
		}

		// Handle auto-reconciliation if record has been created via the reconcile screen
		if ($bank_import_id != 0 )
		{
		  $where_clause = 'rec_id=?';
		  $where_values = array('i',$bank_import_id);
		  $query_result = mysqli_select_query($db,'bank_import','*',$where_clause,$where_values,'');
			if (($row = mysqli_fetch_assoc($query_result)) &&
			    (($row['amount'] = $credit_amount) || ($row['amount'] = -$debit_amount)))
			{
				$set_fields = 'reconciled';
			  $set_values = array('i',1);
			  $where_clause = 'account=? AND seq_no=?';
			  $where_values = array('s',$account,'i',$seq_no);
			  mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
				$set_fields = 'reconciled';
				$set_values = array('i',1);
			  $where_clause = 'rec_id=?';
			  $where_values = array('i',$bank_import_id);
			  mysqli_update_query($db,'bank_import',$set_fields,$set_values,$where_clause,$where_values);
			}
			$set_fields = 'bank_import_id';
		  $set_values = array('i',0);
		  $where_clause = 'account=? AND seq_no=?';
		  $where_values = array('s',$account,'i',$seq_no);
		  mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
		}

		// Update account balances
		update_account_balances($account,$date);

		// Update appropriate fields on other side of transfer
		if ((!empty($target_account)) && (!empty($target_seq_no)))
		{
			$set_fields = 'date,credit_amount,debit_amount,fund,acct_month';
		  $set_values = array('s',$date,'d',$debit_amount,'d',$credit_amount,'s',$fund,'s',$acct_month);
		  $where_clause = 'account=? AND seq_no=?';
		  $where_values = array('s',$target_account,'i',$target_seq_no);
		  mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
			update_account_balances($target_account,$date);
		}
		if ((!empty($source_account)) && (!empty($source_seq_no)))
		{
			$set_fields = 'date,credit_amount,debit_amount,fund,acct_month';
		  $set_values = array('s',$date,'d',$debit_amount,'d',$credit_amount,'s',$fund,'s',$acct_month);
		  $where_clause = 'account=? AND seq_no=?';
		  $where_values = array('s',$source_account,'i',$source_seq_no);
		  mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
			update_account_balances($source_account,$date);
		}

		if (!empty($copy_to_date))
		{
			// Make copy of transaction
			copy_transaction($account,$seq_no,$copy_to_date);
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
				$fields = 'account,seq_no,date,currency,payee,credit_amount,debit_amount,fund,category,memo,acct_month,source_account,source_seq_no';
			  $values = array('s',$target_account,'i',$target_seq_no,'s',$date,'s',$account_currency,'s',$payee,'d',$debit_amount,'d',$credit_amount,'s',$fund,'s','-transfer-','s',$memo,'s',$acct_month,'s',$account,'i',$seq_no);
			  mysqli_insert_query($db,'transactions',$fields,$values);
				update_account_balances($target_account,$date);
				$set_fields = 'category,target_seq_no';
			  $set_values = array('s','-transfer-','i',$target_seq_no);
			  $where_clause = 'account=? AND seq_no=?';
			  $where_values = array('s',$account,'i',$seq_no);
			  mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
			}
			elseif ((!empty($target_account)) && (!empty($target_seq_no)))
			{
				// Update links on target account in case this transaction has changed account
				$set_fields = 'source_account,source_seq_no';
			  $set_values = array('s',$account,'i',$seq_no);
			  $where_clause = 'account=? AND seq_no=?';
			  $where_values = array('s',$target_account,'i',$target_seq_no);
			  mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
			}
			elseif ((!empty($source_account)) && (!empty($source_seq_no)))
			{
				// Update links on source account in case this transaction has changed account
				$set_fields = 'target_account,target_seq_no';
			  $set_values = array('s',$account,'i',$seq_no);
			  $where_clause = 'account=? AND seq_no=?';
			  $where_values = array('s',$source_account,'i',$source_seq_no);
			  mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
			}
			if ((!headers_sent()) && ($action != 'update') && ($action != 'copy'))
			{
				$record_id = encode_record_id($primary_keys);
				header("Location: $BaseURL/$RelativePath/?-action=edit&-table=$table&-recordid=$record_id&summary");
				exit;
			}
		}
		else
		{
			// Scheduled transaction
			$set_fields = 'acct_month';
		  $set_values = array('s',$acct_month);
		  $where_clause = 'account=? AND transact_seq_no=?';
		  $where_values = array('s',$account,'i',$seq_no);
		  mysqli_update_query($db,'splits',$set_fields,$set_values,$where_clause,$where_values);
			if ($record_sched)
			{
				record_scheduled_transaction($account,$seq_no);
				$set_fields = 'record_sched';
			  $set_values = array('i',0);
			  $where_clause = 'account=? AND seq_no=?';
			  $where_values = array('s',$account,'i',$seq_no);
			  mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
			}
		}
	}
}
?>
