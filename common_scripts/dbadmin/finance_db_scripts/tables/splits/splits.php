<?php

class tables_splits
{
	function acct_month__validate($record, $value)
	{
		if (empty($value))
			return true;
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
		{
			return true;
		}
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

	function afterDelete($record)
	{
		$account = $record->FieldVal('account');
		$transact_seq_no = $record->FieldVal('transact_seq_no');
		rationalise_transaction($account,$transact_seq_no);
	}

	function beforeSave($record)
	{
		$action = $record->action;
		$table = $record->table;
		$credit_amount = $record->FieldVal('credit_amount');
		$debit_amount = $record->FieldVal('debit_amount');
		if (($credit_amount != 0) && ($debit_amount != 0))
		{
			return report_error("Credit and debit amounts both specified.");
		}
	}

	function afterSave($record)
	{
		global $BaseURL, $RelativePath;

		$db = admin_db_connect();
		$action = $record->action;
		$table = $record->table;

		$account = $record->FieldVal('account');
		$transact_seq_no = $record->FieldVal('transact_seq_no');
		$credit_amount = $record->FieldVal('credit_amount');
		$debit_amount = $record->FieldVal('debit_amount');
		$auto_amount = $record->FieldVal('auto_amount');
		$fund = $record->FieldVal('fund');
		$category = $record->FieldVal('category');
		$memo = addslashes($record->FieldVal('memo'));
		$acct_month = $record->FieldVal('acct_month');
		$old_split_no = $record->FieldVal('split_no');
		if ($action == 'new')
		{
			$split_no = next_split_no($account,$transact_seq_no);
			$delete_record = false;
		}
		else
		{
			$split_no = $record->FieldVal('split_no');
			$delete_record = $record->FieldVal('delete_record');
		}

		if ($delete_record)
		{
			delete_record_on_save($record);
			return;
		}

	  $where_clause = 'account=? AND seq_no=?';
	  $where_values = array('s',$account,'i',$transact_seq_no);
	  $query_result = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
		if ($row = mysqli_fetch_assoc($query_result))
		{
			$date = $row['date'];
			$payee = $row['payee'];
			$parent_amount = $row['credit_amount'] - $row['debit_amount'];
			$source_account = $row['source_account'];
			$target_account = $row['target_account'];
		}

		// Adjust credit/debit amounts as necessary.
		if ($auto_amount)
		{
			$split_amount = $parent_amount;
		  $where_clause = 'account=? AND transact_seq_no=? AND split_no<>?';
		  $where_values = array('s',$account,'i',$transact_seq_no,'i',$split_no);
		  $query_result = mysqli_select_query($db,'splits','*',$where_clause,$where_values,'');
			while ($row = mysqli_fetch_assoc($query_result))
			{
				// Subtract value of other aplit from total
				$split_amount = add_money($split_amount,subtract_money($row['debit_amount'],$row['credit_amount']));
			}
			if ($split_amount >= 0)
			{
				$credit_amount = $split_amount;
				$debit_amount = 0;
			}
			else
			{
				$debit_amount = -$split_amount;
				$credit_amount = 0;
			}
		}
		elseif ($credit_amount < 0)
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


		// Set fund and/or category to global default where other default values
		// cannot be established. There is no check against the payee defaults
		// as in the processing of a transaction.
		if ($fund == '-default-')
		{
			$fund = 'General';
		}
		if ($category == '-default-')
		{
			$category = '-none-';
		}

		if (empty($acct_month))
		{
			$year=(int)substr($date,0,4);
			$month=(int)substr($date,5,2);
			$day=(int)substr($date,8,2);
			if ($day <= 5)
			{
				$month --;
				if ($month < 1)
				{
					$year --;
					$month = 12;
				}
			}
			$acct_month = sprintf("%04d-%02d",$year,$month);
		}

		if ((!empty($target_account)) && (empty($target_account)))
		{
			// Create remote record for transfer
		}

		// Update parent transaction
		rationalise_transaction($account,$transact_seq_no);

		// Re-update record
		mysqli_query_normal($db,"UPDATE splits SET split_no=$split_no,credit_amount=$credit_amount,debit_amount=$debit_amount,auto_amount=0,fund='$fund',category='$category',acct_month='$acct_month' WHERE account='$account' AND transact_seq_no=$transact_seq_no AND split_no=$old_split_no");

		if (!headers_sent())
		{
			$transaction_pks = array();
			$transaction_pks['account'] = $account;
			$transaction_pks['seq_no'] = $transact_seq_no;
			$record_id = encode_record_id($transaction_pks);
			header("Location: $BaseURL/$RelativePath/?-action=edit&-table=_view_account_$account&-recordid=$record_id&summary");
			exit;
		}
	}
}
?>
