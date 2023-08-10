<?php
//==============================================================================

$db = admin_db_connect();
if (!isset($record_id))
{
  exit("Record ID not specified - this should not occur");
}
$primary_keys = decode_record_id($record_id);
$account = $primary_keys['account'];
$seq_no = $primary_keys['seq_no'];
rationalise_transaction($account,$seq_no);
$where_clause = ' account=? AND seq_no=?';
$where_values = array('s',$account,'i',$seq_no);
$query_result = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
if ($row = mysqli_fetch_assoc($query_result))
{
  if (!isset($_GET['summary']))
  {
    // Display record edit screen
    $params = array();
    $params['additional_links'] = "<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-action=edit&-table=$table&-recordid=$record_id&summary\">Summary&nbsp;&amp;&nbsp;Splits</a></div>\n";
    if (get_table_access_level('transactions') != 'read-only')
    {
      $params['additional_links'] .= "<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-action=reconcile_account&-account=$account\">Reconcile</a></div>\n";
    }
    if (!empty($row['target_account']))
    {
      $primary_keys2 = array();
      $primary_keys2['account'] = $row['target_account'];
      $primary_keys2['seq_no'] = $row['target_seq_no'];
      $record_id2 = encode_record_id($primary_keys2);
      $params['additional_links'] .= "<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-action=edit&-table=_view_account_{$row['target_account']}&-recordid=$record_id2\">Target&nbsp;Transaction</a></div>\n";
    }
    if (!empty($row['source_account']))
    {
      $primary_keys2 = array();
      $primary_keys2['account'] = $row['source_account'];
      $primary_keys2['seq_no'] = $row['source_seq_no'];
      $record_id2 = encode_record_id($primary_keys2);
      $params['additional_links'] .= "<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-action=edit&-table=_view_account_{$row['source_account']}&-recordid=$record_id2\">Source&nbsp;Transaction</a></div>\n";
    }
    handle_record('edit',$params);
  }
  else
  {
    // Display summary & splits screen

    $sched_freq = $row['sched_freq'];
    if ($sched_freq == '#')
    {
    	print("<h1>Transaction Record (Account)</h1>\n");
      print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-action=list&-table=_view_account_$account\">Show All</a></div>");
      print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-action=new&-table=_view_account_$account\">New Record</a></div>");
      print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-action=reconcile_account&-account=$account\">Reconcile</a></div>");
      if (!empty($row['target_account']))
      {
        $primary_keys2 = array();
        $primary_keys2['account'] = $row['target_account'];
        $primary_keys2['seq_no'] = $row['target_seq_no'];
        $record_id2 = encode_record_id($primary_keys2);
        print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-action=edit&-table=_view_account_{$row['target_account']}&-recordid=$record_id2&summary\">Target Transaction</a></div>");
      }
      if (!empty($row['source_account']))
      {
        $primary_keys2 = array();
        $primary_keys2['account'] = $row['source_account'];
        $primary_keys2['seq_no'] = $row['source_seq_no'];
        $record_id2 = encode_record_id($primary_keys2);
        print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-action=edit&-table=_view_account_{$row['source_account']}&-recordid=$record_id2&summary\">Source Transaction</a></div>");
      }
      print("<div style=\"clear:both\"></div>\n");
    	$view = "_view_account_$account";
    }
    else
    {
    	print("<h1>Transaction Record (Scheduled)</h1>\n");
    	$view = "_view_scheduled_transactions";
    }

    $where_clause = 'account=? AND transact_seq_no=?';
    $where_values = array('s',$account,'i',$seq_no);
    $add_clause = 'ORDER BY split_no ASC';
    $query_result2 = mysqli_select_query($db,'splits','*',$where_clause,$where_values,$add_clause);
    if (mysqli_num_rows($query_result2) > 0)
    {
    	// Splits found - clear the main fund and category
    	$fund = '-split-';
    	if ($row['category'] == '-transfer-')
    	{
    		$category = '-transfer-';
    	}
    	else
  	{
        	$category = '-split-';
      	}
    	mysqli_query_normal($db,"UPDATE transactions SET fund='$fund',category='$category' WHERE account='$account' AND seq_no=$seq_no");
    }
    else
    {
    	$fund = $row['fund'];
    	$category = $row['category'];
    }

    // Print main transaction detail
    $splits_discrepancy = $row['splits_discrepancy'];
    if ($splits_discrepancy != 0)
    {
    	print("<p><b>WARNING</b> - There is a split discrepancy of {$row['splits_discrepancy']}</p>\n");
    }
    print("<table>\n");

    // Row 1 - Account Name
    $where_clause = 'label=?';
    $where_values = array('s',$account);
    $query_result2 = mysqli_select_query($db,'accounts','*',$where_clause,$where_values,'');
    if ($row2 = mysqli_fetch_assoc($query_result2))
    {
      print("<tr><td>Account:</td><td>{$row2['name']}</td>");
      $use_quoted_balance = $row2['use_quoted_balance'];
    }
    else
    {
      print("<tr><td>&nbsp;</td><td>&nbsp;</td>");
      $use_quoted_balance = 0;
    }
    print("</tr>\n");

    // Row 2 - Date
    if ($sched_freq == '#')
    {
      print("<tr><td>Date:</td><td>{$row['date']}</td></tr>\n");
    }
    else
    {
      print("<tr><td>Date:</td><td>{$row['date']}</td></tr>\n");
    }

    // Row 3 - Payee
    print("<tr><td>Payee:</td><td>{$row['payee']}</td></tr>\n");

    // Row 4 (optional) - Cheque Number
    if (!empty($row['chq_no']))
    {
      print("<tr><td>Cheque No:</td><td>{$row['chq_no']}</td></tr>\n");
    }

    // Row 5 - Credit/Debit Amount
    if ($row['debit_amount'] != 0)
    {
      print("<tr><td>Debit:</td><td>{$row['debit_amount']}</td></tr>\n");
    }
    else
    {
      print("<tr><td>Credit:</td><td>{$row['credit_amount']}</td></tr>\n");
    }

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
    {
      print("YES");
    }
    else
    {
      print("NO");
    }
    print("</td></tr>\n");

    // Row 11 (optional) - Target/Source Account
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

    // Row 13 - Reconciled Balance
    print("<tr><td>Reconciled Balance:</td><td>{$row['reconciled_balance']}</td></tr>\n");

    // Row 14 - Quoted Balance
    if ($use_quoted_balance)
    {
      print("<tr><td>Quoted Balance:</td><td>{$row['quoted_balance']}</td></tr>\n");
    }

    // Row 15 (optional) - Scheduling Frequency
    if ($sched_freq != '#')
    {
      print("<tr><td>Schedule:</td><td>$sched_freq</td></tr>\n");
    }

    // Row 16 - 'Edit Transaction' Button
    if ($sched_freq == '#')
    {
      print("<tr><td><a href=\"$BaseURL/$RelativePath/index.php?-action=edit&-table=_view_account_$account&-recordid=$record_id\"><button>Edit Transaction</button></a></td></tr>\n");
    }
    else
    {
      print("<tr><td><a href=\"$BaseURL/$RelativePath/index.php?-action=edit&-table=_view_scheduled_transactions&-recordid=$record_id\"><button>Edit Transaction</button></a></td></tr>\n");
    }

    // Row 17 (optional) - 'Go to Transfer' Button
    if ((!empty($row['target_account'])) && (!empty($row['target_seq_no'])))
    {
      print("<tr><td><a href=\"$BaseURL/$RelativePath/index.php?-action=edit&-table=transactions&-recordid=$record_id2&summary\"><button>Go to Transfer</button></a></td></tr>\n");
    }
    elseif ((!empty($row['source_account'])) && (!empty($row['source_seq_no'])))
    {
      print("<tr><td><a href=\"$BaseURL/$RelativePath/index.php?-action=edit&-table=transactions&-recordid=$record_id2&summary\"><button>Go to Transfer</button></a></td></tr>\n");
    }

    // Row 18 - 'New Split' Button
    $presets = array();
    $presets['account'] = $account;
    $presets['transact_seq_no'] = $seq_no;
    $presets['acct_month'] = $row['acct_month'];
    $presets_par = encode_record_id($presets);
    $return_url = cur_url_par();
    print("<tr><td><a href=\"$BaseURL/$RelativePath/index.php?-action=new&-table=splits&-presets=$presets_par&-returnurl=$return_url\"><button>New Split</button></a></td></tr>\n");

    print("</table>\n");

    // Print details of splits
    $transaction_total = subtract_money($row['credit_amount'],$row['debit_amount']);
    $split_total = 0;
    $split_count = 0;
    print("<h2>Splits</h2>\n");
    print("<ul>\n");
    $where_clause = 'account=? AND transact_seq_no=?';
    $where_values = array('s',$account,'i',$seq_no);
    $add_clause = 'ORDER BY split_no ASC';
    $query_result2 = mysqli_select_query($db,'splits','*',$where_clause,$where_values,$add_clause);
    while ($row2 = mysqli_fetch_assoc($query_result2))
    {
      $split_pks = array();
      $split_pks['account'] = $account;
      $split_pks['transact_seq_no'] = $seq_no;
      $split_pks['split_no'] = $row2['split_no'];
      $record_id = encode_record_id($split_pks);
    	print("<li><a href=\"$BaseURL/$RelativePath/index.php?-action=edit&-table=splits&-recordid=$record_id&-returnurl=$return_url\">");
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
    if (($split_count != 0) && ($splits_discrepancy != 0))
    {
    	print("<li>Discrepancy: $splits_discrepancy</li>\n");
    }
    print("</ul>\n");
    if ($split_count == 0)
    {
    	if (($fund == '-split-') && ($category == '-transfer-'))
      {
        print("<p>See other side of transfer.</p>\n");
      }
    	else
      {
        print("<p>NONE</p>\n");
      }
    }
  }
}
else
{
  print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-action=list&-table=_view_account_$account\">Show All</a></div>");
  print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-action=new&-table=_view_account_$account\">New Record</a></div>");
  print("<div style=\"clear:both\"></div>\n");
  print("<p>Record not found.</p>\n");
}

//==============================================================================
?>
