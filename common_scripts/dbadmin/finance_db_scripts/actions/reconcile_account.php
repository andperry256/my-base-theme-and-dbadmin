<?php
//==============================================================================

global $BaseURL,$RelativePath;
global $BankImportDir;

$db = admin_db_connect();
$display_form = true;

$account = $_GET['-account'];
$query_result = mysqli_query($db,"SELECT * FROM accounts WHERE label='$account'");
if ($row = mysqli_fetch_assoc($query_result))
{
	$account_type = $row['type'];
	$account_name = $row['name'];
}
else
{
	$account_name = '';  // This should not occur
}
print("<h1>Reconcile Account ($account_name)</h1>\n");
print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-table=_view_account_$account\">All&nbsp;Transactions</a></div>");
print("<div style=\"clear:both\"></div>\n");

if (isset($_POST['submitted']))
{
	$bank_rec_id = strtok($_POST['bank'],'[');
	$bank_amount = strtok(']');
	$dummy_str = strtok('[');
	$bank_balance = strtok(']');
	if ($bank_amount >= 0)
	{
		$credit_amount = $bank_amount;
		$debit_amount = 0;
	}
	else
	{
		$debit_amount = -$bank_amount;
		$credit_amount = 0;
	}
	$account_seq_no = strtok($_POST['account'],'[');
	$account_amount = strtok(']');
	if (($_POST['bank'] == 'IMPORT') && ($_POST['account'] == 'IMPORT'))
	{
		mysqli_query($db,"DELETE FROM bank_import");
	}
	elseif ($_POST['account'] == 'NONE')
	{
		// Bank transaction not to be matched
		mysqli_query($db,"UPDATE bank_import SET reconciled=1 WHERE rec_id=$bank_rec_id");
		print("<p>Bank transaction discarded.</p>\n");
	}
	elseif ($_POST['account'] == 'NEW')
	{
		$query_result = mysqli_query($db,"SELECT * FROM bank_import WHERE rec_id=$bank_rec_id");
		if ($row = mysqli_fetch_assoc($query_result))
		{
			// Create new transaction. Update payee if regex match is found.
			// N.B. Multiple matches can be made against a given payee by creating additional table entries
			// using the original payee name with variable numbers of underscores added to the end.
			$date = $row['date'];
			$payee = addslashes($row['description']);
			$query_result2 = mysqli_query($db,"SELECT * FROM payees WHERE regex_match<>'^$'");
			while ($row2 = mysqli_fetch_assoc($query_result2))
			{
				$pattern = "/{$row2['regex_match']}/i";
				if (preg_match($pattern,$payee))
				{
					$payee = addslashes(rtrim($row2['name'],'_'));
					break;
				}
			}
			$seq_no = next_seq_no($account);
			mysqli_query($db,"INSERT INTO transactions (account,seq_no,date,payee,credit_amount,debit_amount) VALUES ('$account',$seq_no,'$date','$payee',$credit_amount,$debit_amount)");
			$primary_keys = array();
			$primary_keys['account'] = $account;
			$primary_keys['seq_no'] = $seq_no;
			$record_id = encode_record_id($primary_keys);
			print("<p><a href=\"$BaseURL/$RelativePath/?-table=_view_account_$account&-action=edit&-recordid=$record_id\"><button>Go to Transaction</button></a></p>");
			$display_form = false;
		}
	}
	else
	{
		if (($_POST['bank'] == 'null') || ($_POST['account'] == 'null'))
		{
			print("<p>No action specified.</p>\n");
		}
		elseif ((round($bank_amount,2) != round($account_amount,2)) && (!isset($_POST['auto_adjust'])) && (!isset($_POST['update_schedule'])))
		{
			// Non-matching amounts
			print("<p class=\"error-text\"><b>ERROR</b> - Attempt to reconcile non-matching amounts.</p>\n");
		}
		else
		{
			// Transaction to be reconciled
			$query_result = mysqli_query($db,"SELECT * FROM bank_import WHERE rec_id=$bank_rec_id");
			if ($row = mysqli_fetch_assoc($query_result))
			{
				mysqli_query($db,"UPDATE bank_import SET reconciled=1 WHERE rec_id=$bank_rec_id");
				mysqli_query($db,"UPDATE _view_account_$account SET reconciled=1 WHERE seq_no=$account_seq_no");
				if ((isset($_POST['auto_adjust'])) || (isset($_POST['update_schedule'])))
				{
					// Change register amount to match bank transaction
					mysqli_query($db,"UPDATE _view_account_$account SET credit_amount=$credit_amount,debit_amount=$debit_amount WHERE seq_no=$account_seq_no");
					if (isset($_POST['update_schedule']))
					{
						// Update associated scheduled transaction
						$query_result2 = mysqli_query($db,"SELECT * FROM _view_account_$account WHERE seq_no=$account_seq_no");
						if ($row2 = mysqli_fetch_assoc($query_result2))
						{
							$payee = addslashes($row2['payee']);
							mysqli_query($db,"UPDATE transactions SET credit_amount=$credit_amount,debit_amount=$debit_amount WHERE account='$account' AND payee='$payee' AND sched_freq<>'#'");
						}
					}
				}
				$query_result2 = mysqli_query($db,"SELECT * FROM _view_account_$account WHERE seq_no=$account_seq_no");
				if ($row2 = mysqli_fetch_assoc($query_result2))
					update_account_balances($account,$row2['date']);
				$query_result2 = mysqli_query($db,"SELECT * FROM _view_account_$account ORDER BY date DESC, seq_no DESC LIMIT 1");
				if ($row2 = mysqli_fetch_assoc($query_result2))
				{
					print("<p>Transaction reconciled. Bank balance = $bank_balance. Register balance = {$row2['reconciled_balance']}</p>\n");
				}
			}
		}
	}
}

if ($display_form)
{
	$query_result = mysqli_query($db,"SELECT * FROM bank_import");
	if (mysqli_num_rows($query_result) == 0)
	{
		// If bank import table is empty, then populate it with data from appropriate import file
		$import_data = array();
		$import_data = file("$BankImportDir/Account_$account.csv");
		$first_line_skipped = false;
		foreach ($import_data as $line)
		{
			if (!$first_line_skipped)
				$first_line_skipped = true;
			else
			{
				$line_elements = array();
				$line_elements = str_getcsv($line,',','"');
				$date = $line_elements[0];
				$day = substr($date,0,2);
				$month = substr($date,3,2);
				$year = substr($date,6,4);
				$mysql_date = "$year-$month-$day";
				if ($account_type == 'bank')
				{
					$description = $line_elements[4];
					$debit_amount = $line_elements[5];
					$credit_amount = $line_elements[6];
					if (!empty($debit_amount))
						$amount = -$debit_amount;
					elseif (!empty($credit_amount))
					{
						$amount = $credit_amount;
					}
					$balance = $line_elements[7];
				}
				elseif ($account_type == 'credit-card')
				{
					$description = $line_elements[3];
					$amount = $line_elements[4];
					$amount = - $amount;
					$balance = 0;
				}
				$description = addslashes($description);
				$description = substr($description,0,31);
				mysqli_query($db,"INSERT INTO bank_import (date,description,amount,balance) VALUES ('$mysql_date','$description',$amount,$balance)");
			}
		}
	}

	print("<form method=\"post\">\n");
	print("<table cellpadding=\"10\">\n");

	// Build select list for bank transactions
	print("<tr><td>Bank Transaction:</td><td>\n");
	print("<select name=\"bank\">\n");
	print("<option value=\"null\">Please select ...</option>\n");
	print("<option value=\"IMPORT\">Re-Import CSV</option>\n");
	$query_result = mysqli_query($db,"SELECT * FROM bank_import WHERE reconciled=0 ORDER BY rec_id DESC");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		$text = $row['date'].' | '.$row['description'].' | ';
		$amount = $row['amount'];
		if (($account_type == 'bank') && ($amount > 0))
			$text .= sprintf("C %01.2f",$amount);
		elseif (($account_type == 'bank') && ($amount < 0))
			$text .= sprintf("D %01.2f",-$amount);
		elseif (($account_type == 'credit-card') && ($amount > 0))
			$text .= sprintf("Pmt %01.2f",$amount);
		elseif (($account_type == 'credit-card') && ($amount < 0))
			$text .= sprintf("Chg %01.2f",-$amount);
		if ($row['balance'] != 0)
			$text .= ' | '.$row['balance'];
		print("<option value=\"{$row['rec_id']}[{$row['amount']}]_[{$row['balance']}]\">$text</option>\n");
	}
	print("</select>\n");
	print("<br /><span class=\"small\">(N.B. To re-import CSV file, select this option on BOTH lists)</span></td></tr>\n");

	// Build select list for account transactions
	print("<tr><td>Account Register:</td><td>\n");
	print("<select name=\"account\">\n");
	print("<option value=\"null\">Please select ...</option>\n");
	print("<option value=\"IMPORT\">Re-Import CSV</option>\n");
	print("<option value=\"NONE\">Discard Bank Transaction</option>\n");
	print("<option value=\"NEW\">Create New Transaction</option>\n");
	$query_result = mysqli_query($db,"SELECT * FROM _view_account_$account WHERE reconciled=0 ORDER BY payee ASC,date ASC,seq_no ASC");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		$date = $row['date'];
		$chq_no = $row['chq_no'];
		$payee = $row['payee'];
		$text = "$date | ";
		if (!empty($chq_no))
			$text .= "$chq_no | ";
		$text .= "$payee | ";
		$credit_amount = $row['credit_amount'];
		$debit_amount = $row['debit_amount'];
		if ($credit_amount != 0)
		{
			$text .= "C $credit_amount";
			$amount = $credit_amount;
		}
		elseif ($debit_amount != 0)
		{
			$text .= "D $debit_amount";
			$amount = -$debit_amount;
		}
		else
			$text = '';
		if (!empty($text))
			print("<option value=\"{$row['seq_no']}[$amount]\">$text</option>\n");
	}
	print("</select>\n");
	print("</td></tr>\n");

	print("<tr><td>Auto-adjust:<br />&nbsp;</td><td><input type=\"checkbox\" name=\"auto_adjust\"><br />(Tick to automatically adjust the register value to match the bank transaction)</td></tr>\n");
	print("<tr><td>Update schedule:<br />&nbsp;</td><td><input type=\"checkbox\" name=\"update_schedule\"><br />(Tick to automatically adjust both the register value and the associated scheduled transaction to match the bank transaction)</td></tr>\n");
	print("<tr><td></td><td><input type=\"submit\" name=\"submitted\" value=\"Reconcile\"</td></tr>\n");
	print("</table>\n");
	print("</form>\n");
}

//==============================================================================
?>
