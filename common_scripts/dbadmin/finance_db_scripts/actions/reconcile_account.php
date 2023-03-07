<?php
//==============================================================================

global $BaseURL,$RelativePath;
global $local_site_dir;
global $BankImportDir;
global $CustomPagesURL;

$db = admin_db_connect();

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

if ((isset($_GET['message'])) && (!empty($_GET['message'])))
{
	print($_GET['message']);
}

$query_result = mysqli_query($db,"SELECT * FROM bank_import");
if (mysqli_num_rows($query_result) == 0)
{
}

print("<form method=\"post\" action=\"$CustomPagesURL/$RelativePath/reconcile_account_action.php\">\n");
print("<table cellpadding=\"10\">\n");

// Build select list for bank transactions
print("<tr><td>Bank Transaction:</td><td>\n");
print("<select name=\"bank_transaction\">\n");
print("<option value=\"null\">Please select ...</option>\n");
print("<option value=\"IMPORT\">Re-Import CSV</option>\n");
$query_result = mysqli_query($db,"SELECT * FROM bank_import WHERE reconciled=0 ORDER BY rec_id DESC");
while ($row = mysqli_fetch_assoc($query_result))
{
	$text = $row['date'].' | '.$row['description'].' | ';
	$amount = $row['amount'];
	if (($account_type == 'bank') && ($amount > 0))
	{
		$text .= sprintf("C %01.2f",$amount);
	}
	elseif (($account_type == 'bank') && ($amount < 0))
	{
		$text .= sprintf("D %01.2f",-$amount);
	}
	elseif (($account_type == 'credit-card') && ($amount > 0))
	{
		$text .= sprintf("Pmt %01.2f",$amount);
	}
	elseif (($account_type == 'credit-card') && ($amount < 0))
	{
		$text .= sprintf("Chg %01.2f",-$amount);
	}
	if ($row['balance'] != 0)
	{
		$text .= ' | '.$row['balance'];
	}
	print("<option value=\"{$row['rec_id']}[{$row['amount']}]_[{$row['balance']}]\">$text</option>\n");
}
print("</select>\n");
print("<br /><span class=\"small\">(N.B. To re-import CSV file, select this option on BOTH lists)</span></td></tr>\n");

// Build select list for account transactions
print("<tr><td>Account Transaction:</td><td>\n");
print("<select name=\"account_transaction\">\n");
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
	{
		$text .= "$chq_no | ";
	}
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
	{
		$text .= "* Zero *";
		$amount = 0;
	}
	print("<option value=\"{$row['seq_no']}[$amount]\">$text</option>\n");
}
print("</select>\n");
print("</td></tr>\n");

print("<tr><td>Auto-adjust:<br />&nbsp;</td><td><input type=\"checkbox\" name=\"auto_adjust\"><br />(Tick to automatically adjust the register value to match the bank transaction)</td></tr>\n");
print("<tr><td>Update schedule:<br />&nbsp;</td><td><input type=\"checkbox\" name=\"update_schedule\"><br />(Tick to automatically adjust both the register value and the associated scheduled transaction to match the bank transaction)</td></tr>\n");
print("<tr><td></td><td><input type=\"submit\" name=\"submitted\" value=\"Reconcile\"</td></tr>\n");
print("</table>\n");
print("<input type=\"hidden\" name=\"account\" value=\"$account\">\n");
print("<input type=\"hidden\" name=\"account_type\" value=\"$account_type\">\n");
print("<input type=\"hidden\" name=\"site\" value=\"$local_site_dir\">\n");
print("<input type=\"hidden\" name=\"relpath\" value=\"$RelativePath\">\n");
print("</form>\n");

//==============================================================================
?>
