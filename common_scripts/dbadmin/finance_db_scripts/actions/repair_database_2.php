<?php
//==============================================================================

function show_count($value)
{
    if ($value == 0)
    {
        return "[$value]";
    }
    else
    {
        return "<span style=color:red>[$value]</span>";
    }
}

function run_or_preview_query($query,&$counter)
{
    if (isset($_GET['dry-run']))
    {
        print("<p class=\"small\">$query</p>\n");
    }
    else
    {
        $db = admin_db_connect();
        if (!mysqli_query_normal($db,$query))
        {
            print("<p class=\"small\">$query<br />ERROR - ".mysqli_error($db)."</p>\n");
        }
    }
    $counter++;
}

//==============================================================================

$db = admin_db_connect();

print("<h1>Repair Database</h1>\n");
if (isset($_GET['dry-run']))
{
    print("<p>====== Dry Run ======</p>\n");
}

// Initialise Counters
$dummy_count = 0;
$count_change_cat_to_transfer = 0;
$count_change_cat_from_transfer = 0;
$count_change_fund_from_split = 0;
$count_change_cat_from_split = 0;
$count_change_fund_to_split = 0;
$count_change_cat_to_split = 0;
$count_dead_target_links = 0;
$count_dead_source_links = 0;
$count_delete_orphan_split = 0;
$count_no_splits = 0;
$count_missing_splits = 0;
$count_surplus_splits = 0;
$count_set_acct_month = 0;
$dummy_count = 0;

// BEGIN - Main loop for processing transactions
$query_result = mysqli_select_query($db,'transactions','*','',[],'');
while ($row = mysqli_fetch_assoc($query_result))
{
    $account = $row['account'];
    $seq_no = $row['seq_no'];
    $date = $row['date'];
    $fund = $row['fund'];
    $category = $row['category'];
    $acct_month = $row['acct_month'];
    $target_account = $row['target_account'];
    $target_seq_no = $row['target_seq_no'];
    $source_account = $row['source_account'];
    $source_seq_no = $row['source_seq_no'];
    if ((!empty($source_account)) && (!empty($source_seq_no)))
    {
        $where_clause = 'account=? AND transact_seq_no=?';
        $where_values = ['s',$source_account,'i',$source_seq_no];
        $query_result2 = mysqli_select_query($db,'splits','*',$where_clause,$where_values,'');
    }
    else
    {
        $where_clause = 'account=? AND transact_seq_no=?';
        $where_values = ['s',$account,'i',$seq_no];
        $query_result2 = mysqli_select_query($db,'splits','*',$where_clause,$where_values,'');
    }
    $split_count = mysqli_num_rows($query_result2);
  
    if ($split_count == 0)
    {
        // Check for a fund that wrongly indicates a split
        if ($fund == '-split-')
        {
            run_or_preview_query("UPDATE transactions SET fund='-none-' WHERE account='$account' AND seq_no=$seq_no",$count_change_fund_from_split);
        }
    
        // Check for a category that wrongly indicates a split
        if ($category == '-split-')
        {
            run_or_preview_query("UPDATE transactions SET category='-none-' WHERE account='$account' AND seq_no=$seq_no",$count_change_cat_from_split);
        }
    }
    else
    {
        // Check for a fund that needs to be changed to a split
        if ($fund != '-split-')
        {
            run_or_preview_query("UPDATE transactions SET fund='-split-' WHERE account='$account' AND seq_no=$seq_no",$count_change_fund_to_split);
        }
    
        // Check for a category that needs to be changed to a split
        if ($category != '-split-')
        {
            run_or_preview_query("UPDATE transactions SET category='-split-' WHERE account='$account' AND seq_no=$seq_no",$count_change_cat_to_split);
        }
    }
  
    // Check for a transfer that is not categorised as such
    if ( ( ((!empty($target_account)) && (!empty($target_seq_no))) ||
         ((!empty($source_account)) && (!empty($source_seq_no)))
       ) &&
       ($category != '-transfer-') &&
       ($category != '-split-'))
       {
           run_or_preview_query("UPDATE transactions SET category='-transfer-' WHERE account='$account' AND seq_no=$seq_no",$count_change_cat_to_transfer);
       }
  
    // Check for a transaction that is wrongly categorised as a transfer
      if ( ((empty($target_account)) || (empty($target_seq_no))) &&
           ((empty($source_account)) || (empty($source_seq_no))) &&
         ($category == '-transfer-'))
         {
             run_or_preview_query("UPDATE transactions SET category='-none-' WHERE account='$account' AND seq_no=$seq_no",$count_change_cat_from_transfer);
         }
  
    // Check for a dead target link
    if ((!empty($target_account)) && (!empty($target_seq_no)))
    {
        $where_clause = 'account=? AND seq_no=?';
        $where_values = ['s',$target_account,'i',$target_seq_no];
        $query_result2 = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
        if (($row2 = mysqli_fetch_assoc($query_result2)) && ($row2['source_account'] == $account) && ($row2['source_seq_no'] == $seq_no))
        {
            // No action
        }
        else
        {
            run_or_preview_query("UPDATE transactions SET target_account='',target_seq_no=NULL WHERE account='$account' AND seq_no=$seq_no",$count_dead_target_links);
            run_or_preview_query("UPDATE transactions SET category='-none-' WHERE account='$account' AND seq_no=$seq_no AND category='-transfer-'",$dummy_count);
        }
    }
  
    // Check for a dead source link
    if ((!empty($source_account)) && (!empty($source_seq_no)))
    {
        $where_clause = 'account=? AND seq_no=?';
        $where_values = ['s',$source_account,'i',$source_seq_no];
        $query_result2 = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
        if (($row2 = mysqli_fetch_assoc($query_result2)) && ($row2['target_account'] == $account) && ($row2['target_seq_no'] == $seq_no))
        {
            // No action
        }
        else
        {
            run_or_preview_query("UPDATE transactions SET source_account='',source_seq_no=NULL WHERE account='$account' AND seq_no=$seq_no",$count_dead_source_links);
            run_or_preview_query("UPDATE transactions SET category='-none-' WHERE account='$account' AND seq_no=$seq_no AND category='-transfer-'",$dummy_count);
        }
    }
  
    // Check for an empty accounting month
    if (empty($acct_month))
    {
        $acct_month = accounting_month($date);
        run_or_preview_query("UPDATE transactions SET acct_month='$acct_month' WHERE account='$account' AND seq_no=$seq_no",$count_set_acct_month);
    }
  
    // Check for -split- fund with no splits present
    $where_clause = 'account=? AND transact_seq_no=?';
    $where_values = ['s',$row['account'],'i',$row['seq_no']];
    if (($fund == '-split-') && (empty($source_account)) &&
        (mysqli_num_rows(mysqli_select_query($db,'splits','*',$where_clause,$where_values,'')) == 0))
    {
        // Update the fund to '-nosplit-' and if applicable on the other side of the transfer
        run_or_preview_query("UPDATE transactions SET fund='-nosplit-' WHERE account='{$row['account']}' AND seq_no={$row['seq_no']}",$count_no_splits);
        run_or_preview_query("UPDATE transactions SET fund='-nosplit-' WHERE fund='-split-' AND source_account='{$row['account']}' AND source_seq_no={$row['seq_no']}",$dummy_count);
    }
  
    // Check for unmatching split total
    $where_clause = 'account=? AND transact_seq_no=?';
    $where_values = ['s',$account,'i',$seq_no];
    $query_result2 = mysqli_select_query($db,'splits','*',$where_clause,$where_values,'');
    if (mysqli_num_rows($query_result2) > 0)
    {
        if (empty($source_account))
        {
            $splits_total = 0;
            while ($row2 = mysqli_fetch_assoc($query_result2))
            {
                $split_amount = subtract_money($row2['credit_amount'],$row2['debit_amount']);
                $splits_total = add_money($splits_total,$split_amount);
            }
            $transaction_amount = subtract_money($row['credit_amount'],$row['debit_amount']);
            if ($splits_total != $transaction_amount)
            {
                $missing_split_amount = subtract_money($transaction_amount,$splits_total);
                if ($missing_split_amount > 0)
                {
                    $missing_split_credit = $missing_split_amount;
                    $missing_split_debit = 0;
                }
                else
                {
                    $missing_split_credit = 0;
                    $missing_split_debit = -$missing_split_amount;
                }
                $split_seq_no = next_split_no($account,$seq_no);
                $query = "INSERT INTO splits (account,transact_seq_no,split_no,credit_amount,debit_amount,fund,category,memo,acct_month) VALUES";
                $query .= " ('$account',$seq_no,$split_seq_no,$missing_split_credit,$missing_split_debit,'TBD','-none-','Missing split','$acct_month')";
                run_or_preview_query($query,$count_missing_splits);
            }
        }
        else
        {
            run_or_preview_query("DELETE FROM splits WHERE  account='$account' AND transact_seq_no=$seq_no",$count_surplus_splits);
        }
    }
}
// END -  Main loop for processing transactions

// BEGIN - Main loop for processing splits
$query_result = mysqli_select_query($db,'splits','*','',[],'');
while ($row = mysqli_fetch_assoc($query_result))
{
    $account = $row['account'];
    $transact_seq_no = $row['transact_seq_no'];
    $split_no = $row['split_no'];
    $fund = $row['fund'];
    $category = $row['category'];
    $acct_month = $row['acct_month'];
    $where_clause = 'account=? AND seq_no=?';
    $where_values = ['s',$account,'i',$transact_seq_no];
    $query_result2 = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
    if ($row2 = mysqli_fetch_assoc($query_result2))
    {
        $parent_fund = $row2['fund'];
        $parent_category = $row2['category'];
        $parent_date = $row2['date'];
        $target_account = $row2['target_account'];
        $target_seq_no = $row2['target_seq_no'];
        $source_account = $row2['source_account'];
        $source_seq_no = $row2['source_seq_no'];
    
        // Check for a transfer that is not categorised as such
        if ( ( ((!empty($target_account)) && (!empty($target_seq_no))) ||
               ((!empty($source_account)) && (!empty($source_seq_no)))
           ) &&
           ($category != '-transfer-') &&
           ($category != '-split-'))
           {
               run_or_preview_query("UPDATE splits SET category='-transfer-' WHERE account='$account' AND transact_seq_no=$transact_seq_no AND split_no=$split_no",$count_change_cat_to_transfer);
           }
    
        // Check for a split that is wrongly categorised as a transfer
        if ( ((empty($target_account)) || (empty($target_seq_no))) &&
             ((empty($source_account)) || (empty($source_seq_no))) &&
           ($category == '-transfer-'))
           {
               run_or_preview_query("UPDATE splits SET category='-none-' WHERE account='$account' AND transact_seq_no=$transact_seq_no AND split_no=$split_no",$count_change_cat_from_transfer);
           }
    
        // Check for an empty accounting month
        if (empty($acct_month))
        {
            $acct_month = accounting_month($parent_date);
            run_or_preview_query("UPDATE splits SET acct_month='$acct_month' WHERE account='$account' AND transact_seq_no=$transact_seq_no AND split_no=$split_no",$count_set_acct_month);
        }
    }
    else
    {
        run_or_preview_query("DELETE FROM splits WHERE account='$account' AND transact_seq_no=$transact_seq_no AND split_no=$split_no",$count_delete_orphan_split);
    }
}
// END - Main loop for processing splits

if (isset($_GET['dry-run']))
{
    print("<p>The following errors will be corrected:-</p>\n");
}
else
{
    print("<p>The following errors were corrected:-</p>\n");
}
print("<table cellpadding=\"3\">\n");
print("<tr><td>Transfers not set to <i>-transfer-</i> category</td><td>".show_count($count_change_cat_to_transfer)."</td></tr>\n");
print("<tr><td>Non-transfers set to <i>-transfer-</i> category</td><td>".show_count($count_change_cat_from_transfer)."</td></tr>\n");
print("<tr><td>Fund set to <i>-split</i>- when not required</td><td>".show_count($count_change_fund_from_split)."</td></tr>\n");
print("<tr><td>Category set to <i>-split-</i> when not required</td><td>".show_count($count_change_cat_from_split)."</td></tr>\n");
print("<tr><td>Fund not set to <i>-split-</i> when required</td><td>".show_count($count_change_fund_to_split)."</td></tr>\n");
print("<tr><td>Category not set to <i>-split-</i> when required</td><td>".show_count($count_change_cat_to_split)."</td></tr>\n");
print("<tr><td>Dead target links</td><td>".show_count($count_dead_target_links)."</td></tr>\n");
print("<tr><td>Dead source links</td><td>".show_count($count_dead_source_links)."</td></tr>\n");
print("<tr><td>Orphan splits</td><td>".show_count($count_delete_orphan_split)."</td></tr>\n");
print("<tr><td>No splits</td><td>".show_count($count_no_splits)."</td></tr>\n");
print("<tr><td>Missing splits</td><td>".show_count($count_missing_splits)."</td></tr>\n");
print("<tr><td>Surplus splits</td><td>".show_count($count_surplus_splits)."</td></tr>\n");
print("<tr><td>Accounting month missing</td><td>".show_count($count_set_acct_month)."</td></tr>\n");
print("</table>\n");

if (isset($_GET['dry-run']))
{
    print("<p><a href=\"index.php?-action=repair_database_2\"><button>Run Repair</button></a></p>\n");
}
else
{
    print("<p><a href=\"index.php?-action=repair_database_2&dry-run\"><button>Repeat Dry Run</button></a></p>\n");
}

//==============================================================================
?>
