<?php
//==============================================================================

// Arithmetic functions to avoid problems with decimal fractions not converting
// to exact binary fractions.

function add_money($value1,$value2)
{
	return round((round($value1,2) + round($value2,2)),2);
}

function subtract_money($value1,$value2)
{
	return round((round($value1,2) - round($value2,2)),2);
}

function multiply_money($value1,$value2)
{
	return round((round($value1,2) * round($value2,2)),2);
}

//==============================================================================

function update_account_balances($account,$start_date)
{
	$db = admin_db_connect();
	$view = "_view_account_$account";
	$query_result = mysqli_query($db,"SELECT * FROM accounts WHERE label='$account'");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		$use_quoted_balance = $row['use_quoted_balance'];
	}
	else
	{
		// This should not occur
		$use_quoted_balance = false;
	}
	$query_result = mysqli_query($db,"SELECT * FROM $view WHERE date<'$start_date' ORDER BY date DESC,seq_no DESC LIMIT 1");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		$full_balance = $row['full_balance'];
		$reconciled_balance = $row['reconciled_balance'];
		if ($use_quoted_balance)
		{
			$quoted_balance = $row['quoted_balance'];
		}
		else
		{
			$quoted_balance = $full_balance;
		}
	}
	else
	{
		$full_balance = 0;
		$reconciled_balance = 0;
		$quoted_balance = 0;
	}

	/*
	Automatically clear the 'no quote' flag in any reconciled transactions that
	meet either of the following criteria:-
	1. Transaction is not a credit (i.e. is a debit or zero).
	2. Transaction is a credit and has allowed for cheque clearance time.
  The latest date of a cheque credit that can be considered cleared is equal
	to the last but one working date prior to today.
	*/
	$date = date('Y-m-d');
	for ($d=1; $d<=2; $d++)
	{
		while (true)
		{
			$date = PreviousDate($date);
			if (IsWorkingDay($date))
			{
				break;
			}
		}
	}
	mysqli_query($db,"UPDATE $view SET no_quote=0 WHERE reconciled=1 AND (credit_amount=0.00 OR date<='$date')");

	$query_result = mysqli_query($db,"SELECT * FROM $view WHERE date>='$start_date' ORDER BY date ASC,seq_no ASC");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		$full_balance = add_money($full_balance,subtract_money($row['credit_amount'],$row['debit_amount']));
		if ($row['reconciled'])
		{
			$reconciled_balance = add_money($reconciled_balance,subtract_money($row['credit_amount'],$row['debit_amount']));
		}
		if ($row['no_quote'] == 0)
		{
			if ($use_quoted_balance)
			{
				$quoted_balance = add_money($quoted_balance,subtract_money($row['credit_amount'],$row['debit_amount']));
			}
			else
			{
				$quoted_balance = 0;
			}
		}
		mysqli_query($db,"UPDATE $view SET full_balance=$full_balance,reconciled_balance=$reconciled_balance,quoted_balance=$quoted_balance WHERE seq_no={$row['seq_no']}");
	}
}

//==============================================================================

function next_seq_no($account)
{
	$db = admin_db_connect();
	$query_result = mysqli_query($db,"SELECT * FROM transactions WHERE account='$account' ORDER BY seq_no DESC LIMIT 1");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		$seq_no = $row['seq_no'] + 10;
	}
	else
	{
		$seq_no = 10;
	}
	return $seq_no;
}

//==============================================================================

function next_split_no($account,$transact_seq_no)
{
	$db = admin_db_connect();
	$query_result = mysqli_query($db,"SELECT * FROM splits WHERE account='$account' AND transact_seq_no=$transact_seq_no ORDER BY split_no DESC LIMIT 1");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		$split_no = $row['split_no'] + 10;
	}
	else
	{
		$split_no = 10;
	}
	return $split_no;
}

//==============================================================================

function unlink_transaction($account,$seq_no)
{
	// N.B. This function will only operate on a transfer target.
	$db = admin_db_connect();
	$query_result = mysqli_query($db,"SELECT * FROM transactions WHERE account='$account' AND seq_no=$seq_no");
	if (($row = mysqli_fetch_assoc($query_result)) && (!empty($row['source_account'])))
	{
		// Delete transaction if not reconciled. Otherwise update it to break the link with the transfer source.
		if (!$row['reconciled'])
		{
			mysqli_query($db,"DELETE FROM transactions WHERE account='$account' AND seq_no=$seq_no");
		}
		else
		{
			mysqli_query($db,"UPDATE transactions SET source_account='',source_seq_no='',category='-none-' WHERE account='$account' AND seq_no=$seq_no");
		}
	}
}

//==============================================================================

function rationalise_transaction($account,$seq_no)
{
	$db = admin_db_connect();
	$query_result = mysqli_query($db,"SELECT * FROM transactions WHERE account='$account' AND seq_no=$seq_no");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		$target_account = $row['target_account'];
		$target_seq_no = $row['target_seq_no'];
		$source_account = $row['source_account'];
		$source_seq_no = $row['source_seq_no'];
		$query_result2 = mysqli_query($db,"SELECT * FROM splits WHERE  account='$account' AND transact_seq_no=$seq_no");
		$split_count = mysqli_num_rows($query_result2);
		if (!empty($source_account))
		{
			$query_result2 = mysqli_query($db,"SELECT * FROM splits WHERE  account='$source_account' AND transact_seq_no=$source_seq_no");
			$source_split_count = mysqli_num_rows($query_result2);
		}
		else
			$source_split_count = 0;

		// Check status of fund and category in relation to splits.
		if (($split_count > 0) || ($source_split_count > 0))
		{
			// Ensure that the fund and category are set to 'split' in the transaction and at the other end of any transfer.
			mysqli_query($db,"UPDATE transactions SET fund='-split-',category='-split-' WHERE account='$account' AND seq_no=$seq_no");
			if (!empty($target_account))
			{
				mysqli_query($db,"UPDATE transactions SET fund='-split-',category='-split-' WHERE account='$target_account' AND seq_no=$target_seq_no");
			}
		}
		else
		{
			// Ensure that the fund is not set to 'split' in the transaction and at the other end of any transfer.
			mysqli_query($db,"UPDATE transactions SET fund='-none-' WHERE account='$account' AND seq_no=$seq_no AND fund='-split-'");
			if (!empty($target_account))
			{
				mysqli_query($db,"UPDATE transactions SET fund='-none-' WHERE account='$target_account' AND seq_no=$target_seq_no AND fund='-split-'");
			}

			// Ensure that the category is not set to 'split' in the transaction and at the other end of any transfer.
			mysqli_query($db,"UPDATE transactions SET category='-none-' WHERE account='$account' AND seq_no=$seq_no AND categort='-split-'");
			if (!empty($target_account))
			{
				mysqli_query($db,"UPDATE transactions SET category='-none-' WHERE account='$target_account' AND seq_no=$target_seq_no AND category='-split-'");
			}
		}

		// Check status of category in relation to transfers.
		if (((!empty($row['target_account'])) || (!empty($row['source_account']))) &&
		     ($row['category'] != '-split-'))
		{
			mysqli_query($db,"UPDATE transactions SET category='-transfer-' WHERE account='$account' AND seq_no=$seq_no");
		}
		elseif ($row['category'] == '-transfer-')
		{
			mysqli_query($db,"UPDATE transactions SET category='-none-' WHERE account='$account' AND seq_no=$seq_no");
		}

		// Process associated splits
		$splits_total = 0.00;
		while ($row2 = mysqli_fetch_assoc($query_result2))
		{
			$split_no = $row2['split_no'];
			$splits_total = subtract_money(add_money($splits_total,$row2['credit_amount']),$row2['debit_amount']);

			// Ensure that fund and category are not set to indicate a split.
			if ($row2['fund'] == '-split-')
			{
				mysqli_query($db,"UPDATE splits SET fund='-none-' WHERE account='$account' AND transact_seq_no=$seq_no AND split_no=$split_no");
			}
			if ($row2['category'] == '-split-')
			{
				mysqli_query($db,"UPDATE splits SET category='-none-' WHERE account='$account' AND transact_seq_no=$seq_no AND split_no=$split_no");
			}

			// Check status of category in relation to transfers.
			if ((!empty($row['target_account'])) || (!empty($row['source_account'])))
			{
				mysqli_query($db,"UPDATE splits SET category='-transfer-' WHERE account='$account' AND transact_seq_no=$seq_no AND split_no=$split_no");
			}
			elseif ($row['category'] == '-transfer-')
			{
				mysqli_query($db,"UPDATE splits SET category='-none-' WHERE account='$account' AND transact_seq_no=$seq_no AND split_no=$split_no");
			}
		}
		if ($split_count == 0)
		{
			$splits_discrepancy = 0;
		}
		else
		{
			$splits_discrepancy = subtract_money(add_money($splits_total,$row['debit_amount']),$row['credit_amount']);
		}
		mysqli_query($db,"UPDATE transactions SET splits_discrepancy=$splits_discrepancy WHERE account='$account' AND seq_no=$seq_no");
	}
}

//==============================================================================

function accounting_month($date)
{
	$year=(int)substr($date,0,4);
	$month=(int)substr($date,5,2);
	$day=(int)substr($date,8,2);
	if ($day < MONTH_START_DAY)
	{
		$month --;
		if ($month < 1)
		{
			$year --;
			$month = 12;
		}
	}
	return sprintf("%04d-%02d",$year,$month);
}

//==============================================================================

function year_start($date)
{
	$accounting_month = accounting_month($date);
	$month = (int)substr($accounting_month,5,2);
	$year = (int)substr($accounting_month,0,4);
	if ($month < YEAR_START_MONTH)
	{
		$year--;
	}
	return sprintf("%04d-%02d",$year,YEAR_START_MONTH);
}

//==============================================================================

function year_end($date)
{
	$accounting_month = accounting_month($date);
	$month = (int)substr($accounting_month,5,2);
	$year = (int)substr($accounting_month,0,4);
	if ($month >= YEAR_START_MONTH)
	{
		$month+=11;
		if ($month > 12)
		{
			$month -= 12;
			$year++;
		}
		return sprintf("%04d-%02d",$year,$month);
	}
	else
	{
		return sprintf("%04d-%02d",$year,YEAR_START_MONTH-1);
	}
}

//==============================================================================

function copy_transaction($account,$seq_no,$new_date)
{
	$db = admin_db_connect();
	$query_result = mysqli_query($db,"SELECT * FROM transactions WHERE account='$account' AND seq_no=$seq_no");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		if (!empty($row['source_account']))
		{
			// Do not allow copy on the target of a transfer
			return false;
		}

		// Create copy of transaction using new sequence number
		mysqli_query($db,"DELETE TABLE IF EXISTS temp_transactions");
		mysqli_query($db,"CREATE TEMPORARY TABLE temp_transactions LIKE transactions");
		mysqli_query($db,"INSERT INTO temp_transactions SELECT * FROM transactions WHERE account='$account' AND seq_no=$seq_no");
		mysqli_query($db,"DELETE TABLE IF EXISTS temp_splits");
		mysqli_query($db,"CREATE TEMPORARY TABLE temp_splits LIKE splits");
		mysqli_query($db,"INSERT INTO temp_splits SELECT * FROM splits WHERE account='$account' AND transact_seq_no=$seq_no");
		$new_seq_no = next_seq_no($account);
		$new_acct_month = accounting_month($new_date);
		mysqli_query($db,"UPDATE temp_transactions SET seq_no=$new_seq_no,date='$new_date',acct_month='$new_acct_month',reconciled=0");
		mysqli_query($db,"INSERT INTO transactions SELECT * FROM temp_transactions");
		mysqli_query($db,"UPDATE temp_splits SET transact_seq_no=$new_seq_no,acct_month='$new_acct_month'");
		mysqli_query($db,"INSERT INTO splits SELECT * FROM temp_splits");
		update_account_balances($account,$new_date);

		// Create transfer if required
		if (!empty($row['target_account']))
		{
			$target_account = $row['target_account'];
			$target_seq_no = next_seq_no($target_account);
			$payee = addslashes($row['payee']);
			$memo = addslashes($row['memo']);
			$query = "INSERT INTO transactions (account,seq_no,date,payee,credit_amount,debit_amount,fund,category,memo,acct_month,source_account,source_seq_no)";
			$query .= " VALUES ('$target_account',$target_seq_no,'$new_date','$payee',{$row['debit_amount']},{$row['credit_amount']},'{$row['fund']}','-transfer-','$memo','$acct_month','$account',$new_seq_no)";
			mysqli_query($db,$query);
			update_account_balances($target_account,$new_date);
			mysqli_query($db,"UPDATE transactions SET category='-transfer-',target_seq_no=$target_seq_no WHERE account='$account' AND seq_no=$new_seq_no");
		}
		return $new_seq_no;
	}
	else
	{
		return false;
	}
}

//==============================================================================

function record_scheduled_transaction($account,$seq_no)
{
	global $DBAdminURL, $local_site_dir, $FinanceDBId;
	$db = admin_db_connect();
	$query_result = mysqli_query($db,"SELECT * FROM transactions WHERE account='$account' AND seq_no=$seq_no AND sched_freq<>'#' and sched_count<>0");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		$date = $row['date'];
		$acct_month = $row['acct_month'];
		$sched_freq = $row['sched_freq'];
		$sched_count = $row['sched_count'];
		$last_day = $row['last_day'];

		// Copy scheduled transaction to new transaction and remove schedule from
		// the latter
		$new_seq_no = copy_transaction($account,$seq_no,$date);
		mysqli_query($db,"UPDATE transactions SET sched_freq='#',sched_count=-1 WHERE account='$account' AND seq_no=$new_seq_no");
		update_account_balances($account,$date);

		// Update schedule count if appliable
		if ($sched_count > 0)
		{
			$sched_count--;
			mysqli_query($db,"UPDATE transactions SET sched_count=$sched_count WHERE account='$account' AND seq_no=$seq_no");
		}

		// Update scheduled transaction to next date
		$type = substr($sched_freq,0,1);
		$multiplier = (int)(ltrim($sched_freq,'MW'));
		$year = (int)substr($date,0,4);
		$month = (int)substr($date,5,2);
		$day = (int)substr($date,8,2);
		if ($type == 'M')
		{
			// Frequency in months (N.B. 12 months is the largest interval used).
			$acct_month_year = (int)substr($acct_month,0,4);
			$acct_month_month = (int)substr($acct_month,5,2);
			$month += $multiplier;
			if ($month > 12)
			{
				$month -= 12;
				$year++;
			}
			$acct_month_month += $multiplier;
			if ($acct_month_month > 12)
			{
				$acct_month_month -= 12;
				$acct_month_year++;
			}
			$new_acct_month = sprintf("%04d-%02d",$acct_month_year,$acct_month_month);
		}
		elseif ($type == 'W')
		{
			// Frequency in weeks (N.B. 4 weeks is the largest interval used).
			$days_in_month = DaysInMonth($month,$year);
			$day += ($multiplier * 7);
			if ($day > $days_in_month)
			{
				$day -= $days_in_month;
				$month++;
				if ($month > 12)
				{
					$month = 1;
					$year++;
				}
			}
		}
		$days_in_month = DaysInMonth($month,$year);
		if (($day > $days_in_month) || (($day >= 28) && ($last_day)))
		{
			$day = $days_in_month;
		}
		$date = sprintf("%04d-%02d-%02d",$year,$month,$day);
		if (isset($new_acct_month))
		{
			$acct_month = $new_acct_month;
		}
		else
		{
			$acct_month = accounting_month($date);
		}
		mysqli_query($db,"UPDATE transactions SET date='$date',acct_month='$acct_month' WHERE account='$account' AND seq_no=$seq_no");
		mysqli_query($db,"UPDATE splits SET acct_month='$acct_month' WHERE account='$account' AND transact_seq_no=$seq_no");


		// Send e-mail alert if required
		if (!empty($row['email_alert_id']))
		{
			$url = "$DBAdminURL/finance_db_scripts/send_email_alert.php";
			$url .= "?site=$local_site_dir&recid={$row['email_alert_id']}&dt={$row['date']}";
			if ($row['debit_amount'] > 0)
			{
				$dummy = file_get_contents("$url&amt={$row['debit_amount']}");
			}
			else
			{
				$dummy = file_get_contents("$url&amt={$row['credit_amount']}");
			}
		}
	}
}

//==============================================================================
/*
Function select_excluded_accounts

This function generates the clause to be inserted into a MySQL query in order
to exclude those accounts that are above the current user access level. The
parameter $field_name indicates the field containing the account name in the
query which is to be applied.
*/
//==============================================================================

function select_excluded_accounts($field_name)
{
	$db1 = main_admin_db_connect();
	$db2 = admin_db_connect();
	$user = get_session_var('user');
	$result = '';
	$query_result = mysqli_query($db1,"SELECT * FROM admin_passwords WHERE username='$user'");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		$query_result2 = mysqli_query($db2,"SELECT * FROM accounts");
		while ($row2 = mysqli_fetch_assoc($query_result2))
		{
			if ($row2['access_level'] > $row['access_level'])
			{
					$result .= " AND $field_name<>'{$row2['label']}'";
			}
		}
	}
	return $result;
}

//==============================================================================
/*
Function select_excluded_funds

This function generates the clause to be inserted into a MySQL query in order
to exclude those funds that are above the current user access level. The
parameter $field_name indicates the field containing the fund name in the
query which is to be applied.
*/
//==============================================================================

function select_excluded_funds($field_name)
{
	$db1 = main_admin_db_connect();
	$db2 = admin_db_connect();
	$user = get_session_var('user');
	$result = '';
	$query_result = mysqli_query($db1,"SELECT * FROM admin_passwords WHERE username='$user'");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		$query_result2 = mysqli_query($db2,"SELECT * FROM funds");
		while ($row2 = mysqli_fetch_assoc($query_result2))
		{
			if ($row2['access_level'] > $row['access_level'])
			{
					$result .= " AND $field_name<>'{$row2['name']}'";
			}
		}
	}
	return $result;
}

//==============================================================================
?>
