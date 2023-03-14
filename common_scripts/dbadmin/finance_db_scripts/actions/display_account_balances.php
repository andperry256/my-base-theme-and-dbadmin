<?php
//==============================================================================

if (!isset($db))
{
	$db = admin_db_connect();
}
$table_style = "border-spacing:0; border-collapse:collapse;";
$table_cell_style = "border:solid 1px #ccc;padding:0.2em;vertical-align:top;";
$table_cell_style_ra = $table_cell_style. "text-align:right;";
$table_cell_style_total = $table_cell_style_ra. "border-color:steelblue";
$table_filler_line = "line-height:0.7em;";
$account_exclusions = select_excluded_accounts('label');

// Initialise the balances array
$balances = array();
$query_result = mysqli_query_normal($db,"SELECT * FROM accounts WHERE label IS NOT NULL $account_exclusions");
while ($row = mysqli_fetch_assoc($query_result))
{
	$account = $row['label'];
	$query_result2 = mysqli_query_normal($db,"SELECT * FROM funds WHERE type='localised'");
	while ($row2 = mysqli_fetch_assoc($query_result2))
	{
		$fund = $row2['name'];
		$balances["$account#$fund"] = 0;
	}
	$balances["$account#other"] = 0;
}

// Calculate the balances
$query_result = mysqli_query_normal($db,"SELECT * FROM accounts WHERE label IS NOT NULL $account_exclusions ORDER BY sort_order ASC");
while ($row = mysqli_fetch_assoc($query_result))
{
	$account = $row['label'];
	$query = "SELECT * FROM transactions WHERE currency='GBP' AND account='$account' AND sched_freq='#'";
	if (defined('ACCT_BAL_END_DATE'))
	{
		$end_date = ACCT_BAL_END_DATE;
		$query .= " AND date<='$end_date'";
	}
	$query_result2 = mysqli_query_normal($db,$query);
	while ($row2 = mysqli_fetch_assoc($query_result2))
	{
		if ($row2['fund'] != '-split-')
		{
			// Add transaction amount to fund balance
			if (isset($balances["$account#{$row2['fund']}"]))
				$balances["$account#{$row2['fund']}"] = add_money($balances["$account#{$row2['fund']}"],subtract_money($row2['credit_amount'],$row2['debit_amount']));
			else
				$balances["$account#other"] = add_money($balances["$account#other"],subtract_money($row2['credit_amount'],$row2['debit_amount']));
		}
		elseif (empty($row2['source_account']))
		{
			$query_result3 = mysqli_query_normal($db,"SELECT * FROM splits WHERE account='$account' AND transact_seq_no={$row2['seq_no']}");
			while ($row3 = mysqli_fetch_assoc($query_result3))
			{
				// Add split amount to fund balance
				if (isset($balances["$account#{$row3['fund']}"]))
					$balances["$account#{$row3['fund']}"] = add_money($balances["$account#{$row3['fund']}"],subtract_money($row3['credit_amount'],$row3['debit_amount']));
				else
					$balances["$account#other"] = add_money($balances["$account#other"],subtract_money($row3['credit_amount'],$row3['debit_amount']));
			}
		}
		else
		{
			$query_result3 = mysqli_query_normal($db,"SELECT * FROM splits WHERE account='{$row2['source_account']}' AND transact_seq_no={$row2['source_seq_no']}");
			while ($row3 = mysqli_fetch_assoc($query_result3))
			{
					// Subtract split amount from source account if applicable
				if (isset($balances["$account#{$row3['fund']}"]))
					$balances["$account#{$row3['fund']}"] = add_money($balances["$account#{$row3['fund']}"],subtract_money($row3['debit_amount'],$row3['credit_amount']));
				else
					$balances["$account#other"] = add_money($balances["$account#other"],subtract_money($row3['debit_amount'],$row3['credit_amount']));
			}
		}
	}
}

// Output the information
print("<h1>Account Balances</h1>\n");
print("<table style=\"$table_style\">\n");
$query_result = mysqli_query_normal($db,"SELECT * FROM accounts WHERE label IS NOT NULl $account_exclusions ORDER BY sort_order ASC");
while ($row = mysqli_fetch_assoc($query_result))
{
	$account = $row['label'];
	$total = 0;
	$description = $row['name'];
	print("<tr><td style=\"$table_cell_style\" width=\"150px\"><a href=\"index.php?-table=_view_account_$account\" target=\"_blsnk\">$description</a></td>\n");
	foreach($balances as $key => $value)
	{
		if (strtok($key,'#') == $account)
		{
			$fund = strtok('#');
			if (round($value,2) != 0)
			{
				// Output fund name and balance
				$query_result2 = mysqli_query_normal($db,"SELECT * FROM funds WHERE name='$fund'");
				if ($row2 = mysqli_fetch_assoc($query_result2))
					print("<td style=\"$table_cell_style\" width=\"120px\">{$row2['name']}</td>");
				elseif ($fund == 'other')
					print("<td style=\"$table_cell_style\" width=\"100px\">Other</td>");
				print("<td style=\"$table_cell_style_ra\" width=\"50px\">");
				if ($fund == 'other')
				{
					if ($value >= 0)
						printf("<span class=\"balance-positive\">%01.2f</span>", $value);
					else
						printf("<span class=\"balance-negative\">%01.2f</span>", $value);
				}
				else
				{
					$link = "index.php?-action=display_transaction_report&account=$account&fund=$fund&start_month=".NO_START_MONTH."&end_month=".NO_END_MONTH;
					if ($value >= 0)
						printf("<a class=\"balance-positive\" target=\"_blank\" href=\"$link\">%01.2f</a>", $value);
					else
						printf("<a class=\"balance-negative\" target=\"_blank\" href=\"$link\">%01.2f</a>", $value);
				}
				print("</td></tr>\n");
				print("<tr><td style=\"$table_cell_style\">&nbsp;</td>\n");
				$total = add_money($total,$value);
			}
		}
	}
	// Output account total
	print("<td style=\"$table_cell_style\">Total</td><td style=\"$table_cell_style_total\">\n");
	if ($total >= 0)
		printf("<span class=\"balance-positive\">%01.2f</span>", $total);
	else
		printf("<span class=\"balance-negative\">%01.2f</span>", $total);
	print("</td></tr><tr><td style=\"$table_filler_line\">&nbsp;</td></tr>\n");
}
print("</table>\n");

//==============================================================================
?>
