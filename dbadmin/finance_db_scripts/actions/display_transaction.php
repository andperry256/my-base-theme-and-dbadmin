<?php
//==============================================================================

global $BaseURL, $RelativePath;

$db = admin_db_connect();

$account = $_GET['account'];
$seq_no = $_GET['seq_no'];
rationalise_transaction($account,$seq_no);

$query_result = mysqli_query($db,"SELECT * FROM transactions WHERE account='$account' AND seq_no=$seq_no");
if ($row = mysqli_fetch_assoc($query_result))
{
$sched_freq = $row['sched_freq'];
if ($sched_freq == '#')
{
	print("<h1>Transaction Record (Account)</h1>\n");
	$view = "_view_account_$account";
}
else
{
	print("<h1>Transaction Record (Scheduled)</h1>\n");
	$view = "_view_scheduled_transactions";
}

$query_result2 = mysqli_query($db,"SELECT * FROM splits WHERE account='$account' AND transact_seq_no=$seq_no ORDER BY split_no ASC");
if (mysqli_num_rows($query_result2) > 0)
{
	// Splits found - clear the main fund and category
	$fund = '-split-';
	if ($row['category'] == '-transfer-')
		$category = '-transfer-';
	else
		$category = '-split-';
	mysqli_query($db,"UPDATE transactions SET fund='$fund',category='$category' WHERE account='$account' AND seq_no=$seq_no");
}
else
{
	$fund = $row['fund'];
	$category = $row['category'];
}

// Print main transaction detail
$col_1_width = '150px';
$col_3_width = '110px';
$border_style = 'border-style:solid;border-width:1px;border-color:steelblue';
print("<table width=\"100%%\" cellpadding=\"4\" cellspacing=\"5\">\n");

// Row 1 - Account Name
$query_result2 = mysqli_query($db,"SELECT * FROM accounts WHERE label='$account'");
if ($row2 = mysqli_fetch_assoc($query_result2))
	print("<tr><td width=\"$col_1_width\">Account:</td><td>{$row2['name']}</td>");
else
	print("<tr><td width=\"$col_1_width\">&nbsp;</td><td>&nbsp;</td>");
print("</tr>\n");

// Row 2 - Date
if ($sched_freq == '#')
	print("<tr><td>Date:</td><td>{$row['date']}</td></tr>\n");
else
	print("<tr><td>Date:</td><td>{$row['date']}</td></tr>\n");

// Row 3 - Payee
print("<tr><td>Payee:</td><td>{$row['payee']}</td></tr>\n");

// Row 4 (optional) - Cheque Number
if (!empty($row['chq_no']))
	print("<tr><td>Cheque No:</td><td>{$row['chq_no']}</td></tr>\n");

// Row 5 - Credit/Debit Amount
if ($row['debit_amount'] != 0)
	print("<tr><td>Debit:</td><td>{$row['debit_amount']}</td></tr>\n");
else
	print("<tr><td>Credit:</td><td>{$row['credit_amount']}</td></tr>\n");

// Row 6 - Fund
print("<tr><td>Fund:</td><td>$fund</td></tr>\n");

// Row 7 - Category
print("<tr><td>Category:</td><td>$category</td></tr>\n");

// Row 8 - Memo
print("<tr><td>Memo:</td><td>{$row['memo']}</td></tr>\n");

// Row 9 - Accounting Month
print("<tr><td>Accounting Month:</td><td>{$row['acct_month']}</td></tr>\n");

// Row 10 - Reconciled Status
print("<tr><td>Reconciled:</td><td>");
if ($row['reconciled'])
	print("YES");
else
	print("NO");
print("</td></tr>\n");

// Row 11(optional) - Target/Source Account
if (!empty($row['target_account']))
{
	$target_account_name = str_replace('_',' ',$row['target_account']);
	print("<tr><td>Target Account:</td><td>$target_account_name</td></tr>\n");
}
elseif (!empty($row['source_account']))
{
	$source_account_name = str_replace('_',' ',$row['source_account']);
	print("<tr><td>Source Account:</td><td>$source_account_name</td></tr>\n");
}

// Row 12 - Main Balance
print("<tr><td>Main Balance:</td><td>{$row['full_balance']}</td></tr>\n");

// Row 13 - Cleared Balance
print("<tr><td>Cleared Balance:</td><td>{$row['cleared_balance']}</td></tr>\n");

// Row 14 (optional) - Scheduling Frequency
if ($sched_freq != '#')
	print("<tr><td>Schedule:</td><td>$sched_freq</td></tr>\n");

// Row 15 - 'Edit Transaction' Button
if ($sched_freq == '#')
	print("<tr><td style=\"$border_style\"><a href=\"$BaseURL/$RelativePath/index.php?-action=edit&-table=_view_account_$account&seq_no=$seq_no\">Edit Transaction</a></td></tr>\n");
else
	print("<tr><td style=\"$border_style\"><a href=\"$BaseURL/$RelativePath/index.php?-action=edit&-table=_view_scheduled_transactions&account=$account&seq_no=$seq_no\">Edit Transaction</a></td></tr>\n");

// Row 16 (optional) - 'Go to Transfer' Button
if ((!empty($row['target_account'])) && (!empty($row['target_seq_no'])))
	print("<tr><td style=\"$border_style\"><a href=\"$BaseURL/$RelativePath/index.php?-action=display_transaction&account={$row['target_account']}&seq_no={$row['target_seq_no']}\">Go to Transfer</a></td></tr>\n");
elseif ((!empty($row['source_account'])) && (!empty($row['source_seq_no'])))
	print("<tr><td style=\"$border_style\"><a href=\"$BaseURL/$RelativePath/index.php?-action=display_transaction&account={$row['source_account']}&seq_no={$row['source_seq_no']}\">Go to Transfer</a></td></tr>\n");

// Row 17 - 'New Split' Button
print("<tr><td style=\"$border_style\"><a href=\"$BaseURL/$RelativePath/index.php?-action=new_split&account=$account&seq_no=$seq_no&acct_month={$row['acct_month']}\">New Split</a></td></tr>\n");

print("</table>\n");

// Print details of splits
$transaction_total = subtract_money($row['credit_amount'],$row['debit_amount']);
$split_total = 0;
$split_count = 0;
print("<h2>Splits</h2>\n");
print("<ul>\n");
$query_result2 = mysqli_query($db,"SELECT * FROM splits WHERE account='$account' AND transact_seq_no=$seq_no ORDER BY split_no ASC");
while ($row2 = mysqli_fetch_assoc($query_result2))
{
	print("<li><a href=\"$BaseURL/$RelativePath/index.php?-action=edit&-table=splits&account=$account&transact_seq_no=$seq_no&split_no={$row2['split_no']}\">");
	print("Fund: {$row2['fund']} | ");
	print("Cat: {$row2['category']} | ");
	if ($row2['credit_amount'] > 0)
	{
		$split_total = add_money($split_total,$row2['credit_amount']);
		print("Credit: {$row2['credit_amount']}");
	}
	elseif ($row2['debit_amount'] > 0)
	{
		$split_total = subtract_money($split_total,$row2['debit_amount']);
		print("Debit: {$row2['debit_amount']}");
	}
	$tempstr = str_replace('%','%%',$row2['memo']);
	print("<br />Memo: $tempstr");
	print("");
	print("</a></li>\n");
	$split_count++;
}
print("</ul>\n");
if ($split_count == 0)
{
	if (($fund == '-split-') && ($category == '-transfer-'))
		print("<p>See other side of transfer.</p>\n");
	else
		print("<p>NONE</p>\n");
}
elseif ($split_total != $transaction_total)
{
	$discrepancy = sprintf("%01.2f", subtract_money($split_total,$transaction_total));
	print("<p><b>WARNING</b> - There is a split discrepancy of $discrepancy</p>\n");
}

//==============================================================================
?>
