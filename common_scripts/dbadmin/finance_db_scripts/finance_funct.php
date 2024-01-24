<?php
//==============================================================================
/*
  Arithmetic functions to avoid problems with decimal fractions not converting
  to exact binary fractions.
*/
//==============================================================================

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
/*
  Function update_account_balances

  This function is called when a transaction is saved, and will cause all 
  balances (main, reconciled & quoted) to be updated on the given account from
  the given date onwards.
*/
//==============================================================================

function update_account_balances($account,$start_date)
{
    $db = admin_db_connect();
    $view = "_view_account_$account";
    $where_clause = 'label=?';
    $where_values = array('s',$account);
    $query_result = mysqli_select_query($db,'accounts','*',$where_clause,$where_values,'');
    if ($row = mysqli_fetch_assoc($query_result))
    {
        $use_quoted_balance = $row['use_quoted_balance'];
    }
    else
    {
        // This should not occur
        $use_quoted_balance = false;
    }
    $where_clause = 'date<?';
    $where_values = array('s',$start_date);
    $add_clause = 'ORDER BY date DESC,seq_no DESC LIMIT 1';
    $query_result = mysqli_select_query($db,$view,'*',$where_clause,$where_values,$add_clause);
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
    $set_fields = 'no_quote';
    $set_values = array('i',0);
    $where_clause = 'reconciled=1 AND (credit_amount=0.00 OR date<=?)';
    $where_values = array('s',$date);
    mysqli_update_query($db,$view,$set_fields,$set_values,$where_clause,$where_values);
  
    $where_clause = 'date>=?';
    $where_values = array('s',$start_date);
    $add_clause = ' ORDER BY date ASC,seq_no ASC';
    $query_result = mysqli_select_query($db,$view,'*',$where_clause,$where_values,$add_clause);
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
        $set_fields = 'full_balance,reconciled_balance,quoted_balance';
        $set_values = array('d',$full_balance,'d',$reconciled_balance,'d',$quoted_balance);
        $where_clause = 'seq_no=?';
        $where_values = array('i',$row['seq_no']);
        mysqli_update_query($db,$view,$set_fields,$set_values,$where_clause,$where_values);
    }
}

//==============================================================================
/*
  Function next_seq_no

  This function returns the next sequence number for a new trasnaction on a 
  given account.
*/
//==============================================================================

function next_seq_no($account)
{
    $db = admin_db_connect();
    $where_clause = 'account=?';
    $where_values = array('s',$account);
    $add_clause = 'ORDER BY seq_no DESC LIMIT 1';
    $query_result = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,$add_clause);
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
/*
  Function next_split_no

  This function returns the next available sequence number for a new split on a
  given transaction.
*/
//==============================================================================

function next_split_no($account,$transact_seq_no)
{
    $db = admin_db_connect();
    $where_clause = 'account=? AND transact_seq_no=?';
    $where_values = array('s',$account,'i',$transact_seq_no);
    $add_clause = 'ORDER BY split_no DESC LIMIT 1';
    $query_result = mysqli_select_query($db,'splits','*',$where_clause,$where_values,$add_clause);
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
/*
  Function unlink_transaction

  The function breaks the link between two transactions on separate accounts.
  The sequence number of the source transaction is supplied. The target
  transaction is deleted if unreconciled and updated if reconciled.
*/
//==============================================================================

function unlink_transaction($account,$seq_no)
{
    // N.B. This function will only operate on a transfer target.
    $db = admin_db_connect();
    $where_clause = 'account=? AND seq_no=?';
    $where_values = array('s',$account,'i',$seq_no);
    $query_result = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
    if (($row = mysqli_fetch_assoc($query_result)) && (!empty($row['source_account'])))
    {
        // Delete transaction if not reconciled. Otherwise update it to break the link with the transfer source.
        if (!$row['reconciled'])
        {
            $where_clause = 'account=? AND seq_no=?';
            $where_values = array('s',$account,'i',$seq_no);
            mysqli_delete_query($db,'transactions',$where_clause,$where_values);
        }
        else
        {
            $set_fields = 'source_account,source_seq_no,category';
            $set_values = array('s','','s','','s','-none-');
            $where_clause = 'account=? AND seq_no=?';
            $where_values = array('s',$account,'i',$seq_no);
            mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
        }
    }
}

//==============================================================================
/*
  Function rationalise_transaction

  This function is called when saving a transaction and makes various
  consistency checks and updates on the record.
*/
//==============================================================================

function rationalise_transaction($account,$seq_no)
{
    $db = admin_db_connect();
    $where_clause = 'account=? AND seq_no=?';
    $where_values = array('s',$account,'i',$seq_no);
    $query_result = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
    if ($row = mysqli_fetch_assoc($query_result))
    {
        $fund = $row['fund'];
        $target_account = $row['target_account'];
        $target_seq_no = $row['target_seq_no'];
        $source_account = $row['source_account'];
        $source_seq_no = $row['source_seq_no'];
        $where_clause = 'account=? AND transact_seq_no=?';
        $where_values = array('s',$account,'i',$seq_no);
        $query_result2 = mysqli_select_query($db,'splits','*',$where_clause,$where_values,'');
        $split_count = mysqli_num_rows($query_result2);
        if (!empty($source_account))
        {
            $where_clause = 'account=? AND transact_seq_no=?';
            $where_values = array('s',$source_account,'i',$source_seq_no);
            $query_result2 = mysqli_select_query($db,'splits','*',$where_clause,$where_values,'');
            $source_split_count = mysqli_num_rows($query_result2);
        }
        else
        {
            $source_split_count = 0;
        }
    
        /*
        Update the fund to '-nosplit-' if it is '-split-' and there are no splits
        remaining. Do not act if the transaction is at the target end of a transfer,
        but if it is at the source end of a transfer then update the transaction
        record at the target end as well.
        */
        if (($split_count == 0) && ($fund == '-split-') && (empty($source_account)))
        {
            $set_fields = 'fund';
            $set_values = array('s','-nosplit-');
            $where_clause = 'account=? AND seq_no=?';
            $where_values = array('s',$account,'i',$seq_no);
            mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
            $set_fields = 'fund';
            $set_values = array('s','-nosplit-');
            $where_clause = "fund='-split-' AND source_account=? AND source_seq_no=?";
            $where_values = array('s',$account,'i',$seq_no);
            mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
        }
    
        // Check status of fund and category in relation to splits.
        if (($split_count > 0) || ($source_split_count > 0))
        {
            // Ensure that the fund and category are set to 'split' in the transaction and at the other end of any transfer.
            $set_fields = 'fund,category';
            $set_values = array('s','-split-','s','-split-');
            $where_clause = 'account=? AND seq_no=?';
            $where_values = array('s',$account,'i',$seq_no);
            mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
            if (!empty($target_account))
            {
                $set_fields = 'fund,category';
                $set_values = array('s','-split-','s','-split-');
                $where_clause = 'account=? AND seq_no=?';
                $where_values = array('s',$target_account,'i',$target_seq_no);
                mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
            }
        }
        else
        {
            // Ensure that the fund is not set to 'split' in the transaction and at the other end of any transfer.
            $set_fields = 'fund';
            $set_values = array('s','-none-');
            $where_clause = "account=? AND seq_no=? AND fund='-split-'";
            $where_values = array('s',$account,'i',$seq_no);
            mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
            if (!empty($target_account))
            {
                $set_fields = 'fund';
                $set_values = array('s','-none-');
                $where_clause = "account=? AND seq_no=? AND fund='-split-'";
                $where_values = array('s',$target_account,'i',$target_seq_no);
                mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
            }
      
            // Ensure that the category is not set to 'split' in the transaction and at the other end of any transfer.
            $set_fields = 'category';
            $set_values = array('s','-none-');
            $where_clause = "account=? AND seq_no=? AND category='-split-'";
            $where_values = array('s',$account,'s',$seq_no);
            mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
            if (!empty($target_account))
            {
                $set_fields = 'category';
                $set_values = array('s','-none-');
                $where_clause = "account=? AND seq_no=? AND category='-split-'";
                $where_values = array('s',$target_account,'s',$target_seq_no);
                mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
            }
        }
    
        // Check status of category in relation to transfers.
        if (((!empty($row['target_account'])) || (!empty($row['source_account']))) &&
             ($row['category'] != '-split-'))
        {
            $set_fields = 'category';
            $set_values = array('s','-transfer-');
            $where_clause = 'account=? AND seq_no=?';
            $where_values = array('s',$account,'s',$seq_no);
            mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
        }
        elseif ($row['category'] == '-transfer-')
        {
            $set_fields = 'category';
            $set_values = array('s','-none-');
            $where_clause = 'account=? AND seq_no=?';
            $where_values = array('s',$account,'s',$seq_no);
            mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
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
                $set_fields = 'fund';
                $set_values = array('s','-none-');
                $where_clause = 'account=? AND transact_seq_no=? AND split_no=?';
                $where_values = array('s',$account,'i',$seq_no,'i',$split_no);
                mysqli_update_query($db,'splits',$set_fields,$set_values,$where_clause,$where_values);
            }
            if ($row2['category'] == '-split-')
            {
                $set_fields = 'category';
                $set_values = array('s','-none-');
                $where_clause = 'account=? AND transact_seq_no=? AND split_no=?';
                $where_values = array('s',$account,'i',$seq_no,'i',$split_no);
                mysqli_update_query($db,'splits',$set_fields,$set_values,$where_clause,$where_values);
            }
      
            // Check status of category in relation to transfers.
            if ((!empty($row['target_account'])) || (!empty($row['source_account'])))
            {
                $set_fields = 'category';
                $set_values = array('s','-transfer-');
                $where_clause = 'account=? AND transact_seq_no=? AND split_no=?';
                $where_values = array('s',$account,'i',$seq_no,'i',$split_no);
                mysqli_update_query($db,'splits',$set_fields,$set_values,$where_clause,$where_values);
            }
            elseif ($row['category'] == '-transfer-')
            {
                $set_fields = 'category';
                $set_values = array('s','-none-');
                $where_clause = 'account=? AND transact_seq_no=? AND split_no=?';
                $where_values = array('s',$account,'i',$seq_no,'i',$split_no);
                mysqli_update_query($db,'splits',$set_fields,$set_values,$where_clause,$where_values);
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
        $set_fields = 'splits_discrepancy';
        $set_values = array('d',$splits_discrepancy);
        $where_clause = 'account=? AND seq_no=?';
        $where_values = array('s',$account,'i',$seq_no);
        mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
    }
}

//==============================================================================
/*
  Function accounting_month

  This function returns the accounting month (yyyy-mm) associated with a given
  date.
*/
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
/*
  Function year_start

  This function returns the start date of the accounting year associcated with a
  given date.
*/
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
/*
  Function year_end

  This function returns the end date of the accounting year associcated with a
  given date.
*/
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
/*
  Function copy_transaction

  This function creates a copy of a given transaction. The copy is made within
  the same account to a new given date. Any splits or transfers are replicated.
*/
//==============================================================================

function copy_transaction($account,$seq_no,$new_date)
{
    $db = admin_db_connect();
    $where_clause = 'account=? AND seq_no=?';
    $where_values = array('s',$account,'i',$seq_no);
    $query_result = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
    if ($row = mysqli_fetch_assoc($query_result))
    {
        if (!empty($row['source_account']))
        {
            // Do not allow copy on the target of a transfer
            return false;
        }
    
        // Create copy of transaction using new sequence number
        $set_fields = 'copy_to_date';
        $set_values = array('n',null);
        $where_clause = 'account=? AND seq_no=?';
        $where_values = array('s',$account,'i',$seq_no);
        mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
        mysqli_query_normal($db,"DROP TABLE IF EXISTS temp_transactions");
        mysqli_query_normal($db,"CREATE TEMPORARY TABLE temp_transactions LIKE transactions");
        mysqli_query_normal($db,"INSERT INTO temp_transactions SELECT * FROM transactions WHERE account='$account' AND seq_no=$seq_no");
        mysqli_query_normal($db,"DROP TABLE IF EXISTS temp_splits");
        mysqli_query_normal($db,"CREATE TEMPORARY TABLE temp_splits LIKE splits");
        mysqli_query_normal($db,"INSERT INTO temp_splits SELECT * FROM splits WHERE account='$account' AND transact_seq_no=$seq_no");
        $new_seq_no = next_seq_no($account);
        $new_acct_month = accounting_month($new_date);
        $set_fields = 'seq_no,date,acct_month,reconciled';
        $set_values = array('i',$new_seq_no,'s',$new_date,'s',$new_acct_month,'i',0);
        mysqli_update_query($db,'temp_transactions',$set_fields,$set_values,'',array());
        mysqli_query_normal($db,"INSERT INTO transactions SELECT * FROM temp_transactions");
        $set_fields = 'transact_seq_no,acct_month';
        $set_values = array('i',$new_seq_no,'s',$new_acct_month);
        mysqli_update_query($db,'temp_splits',$set_fields,$set_values,'',array());
        mysqli_query_normal($db,"INSERT INTO splits SELECT * FROM temp_splits");
        update_account_balances($account,$new_date);
    
        // Create transfer if required
        if (!empty($row['target_account']))
        {
            $target_account = $row['target_account'];
            $target_seq_no = next_seq_no($target_account);
            $fields = 'account,seq_no,date,payee,credit_amount,debit_amount,fund,category,memo,acct_month,source_account,source_seq_no';
            $values = array('s',$target_account,'i',$target_seq_no,'s',$new_date,'s',$row['payee'],'d',$row['debit_amount'],'d',$row['credit_amount'],'s',$row['fund'],'s','-transfer-','s',$row['memo'],'s',accounting_month($new_date),'s',$account,'i',$new_seq_no);
            mysqli_insert_query($db,'transactions',$fields,$values);
            update_account_balances($target_account,$new_date);
            $set_fields = 'category,target_seq_no';
            $set_values = array('s','-transfer-','i',$target_seq_no);
            $where_clause = 'account=? AND seq_no=?';
            $where_values = array('s',$account,'i',$new_seq_no);
            mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
        }
        return $new_seq_no;
    }
    else
    {
        return false;
    }
}

//==============================================================================
/*
  Function record_scheduled_transaction

  This function records a given scheduled in the associated account and updates
  the schedule date to the next due date.
*/
//==============================================================================

function record_scheduled_transaction($account,$seq_no)
{
    global $db_admin_url, $local_site_dir, $finance_db_id;
    $db = admin_db_connect();
    $where_clause = "account=? AND seq_no=? AND sched_freq<>'#' and sched_count<>0";
    $where_values = array('s',$account,'i',$seq_no);
    $query_result = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
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
        $set_fields = 'sched_freq,sched_count';
        $set_values = array('s','#','i',-1);
        $where_clause = 'account=? AND seq_no=?';
        $where_values = array('s',$account,'i',$new_seq_no);
        mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
        update_account_balances($account,$date);
    
        // Update schedule count if appliable
        if ($sched_count > 0)
        {
            $sched_count--;
            $set_fields = 'sched_count';
            $set_values = array('i',$sched_count);
            $where_clause = 'account=? AND seq_no=?';
            $where_values = array('s',$account,'i',$seq_no);
            mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
        }
    
        // Update scheduled transaction to next date
        $type = substr($sched_freq,0,1);
        $multiplier = (int)(ltrim($sched_freq,'MWD'));
        if ($type == 'M')
        {
            $date = AddMonths($date,$multiplier,$last_day);
        }
        elseif ($type == 'W')
        {
            $date = AddWeeks($date,$multiplier);
        }
        elseif ($type == 'D')
        {
            $date = AddDays($date,$multiplier);
        }
        $acct_month = accounting_month($date);
        $set_fields = 'date,acct_month';
        $set_values = array('s',$date,'s',$acct_month);
        $where_clause = 'account=? AND seq_no=?';
        $where_values = array('s',$account,'i',$seq_no);
        mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
        $set_fields = 'acct_month';
        $set_values = array('s',$acct_month);
        $where_clause = 'account=? AND transact_seq_no=?';
        $where_values = array('s',$account,'i',$seq_no);
        mysqli_update_query($db,'splits',$set_fields,$set_values,$where_clause,$where_values);
    
        // Send e-mail alert if required
        if (!empty($row['email_alert_id']))
        {
            $url = "$db_admin_url/finance_db_scripts/send_email_alert.php";
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
  Function record_new_scheduled_transactions

  This function is called by the finance cron script and records any scheduled
  transactions that are due.
*/
//==============================================================================

function record_new_scheduled_transactions()
{
    $db = admin_db_connect();
    $where_clause = 'date<=?';
    $where_values = array('s',date('Y-m-d'));
    $query_result = mysqli_select_query($db,'_view_scheduled_transactions','*',$where_clause,$where_values,'');
    while ($row = mysqli_fetch_assoc($query_result))
    {
        record_scheduled_transaction($row['account'],$row['seq_no']);
    }
}

//==============================================================================
/*
  Function find_matching_transaction

  This function is called by the reconciliation procedure to find a transaction
  that is most likely to match the one currently being reconciled. It will only
  provide a proper result if there is a unique match with one record.  It will
  return zero for no match found or a negated count if multiple matches are
  found.
*/
//==============================================================================

function find_matching_transaction($account,$date,$amount)
{
    $db = admin_db_connect();
    $start_date = AddDays($date,-4);
    $end_date = AddDays($date,1);
    $credit_amount = ($amount > 0) ? $amount : 0;
    $debit_amount = ($amount < 0) ? -$amount : 0;
    $where_clause = 'account=? AND date>=? AND date<=? AND credit_amount=? AND debit_amount=? and reconciled=0';
    $where_values = array('s',$account,'s',$start_date,'s',$end_date,'d',$credit_amount,'d',$debit_amount);
    $query_result = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
    $count = mysqli_num_rows($query_result);
    if (($count == 1) && ($row = mysqli_fetch_assoc($query_result)))
    {
        // Unique match found
        return ($row['seq_no']);
    }
    else
    {
        // Return zero or a negated match count.
        return -$count;
    }
}

//==============================================================================
/*
  Function delete_uncleared_cheques

  This function is called by the finance cron script and deletes any uncleared
  cheques that have gone out of date. These are added into the expired_cheques
  table for future reference.
*/
//==============================================================================

function delete_uncleared_cheques()
{
    $db = admin_db_connect();
    $cutoff_date = AddDays(date('Y-m-d'),-190);  // 6 months + small allowance
    $where_clause = 'reconciled=0 AND chq_no IS NOT NULL and chq_no>0 AND date<=?';
    $where_values = array('s',$cutoff_date);
    $query_result = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
    while ($row = mysqli_fetch_assoc($query_result))
    {   
        $fields = 'account,chq_no,date,payee,amount';
        $values = array ('s',$row['account'],'i',$row['chq_no'],'s',$row['date'],'s',$row['payee'],'d',$row['debit_amount']);
        mysqli_insert_query($db,'expired_cheques',$fields,$values);
        $where_clause = 'account=? AND chq_no=?';
        $where_values = array('s',$row['account'],'i',$row['chq_no']);
        mysqli_delete_query($db,'transactions',$where_clause,$where_values);
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
    $user = get_session_var(SV_USER);
    $result = '';
    $where_clause = ' username=?';
    $where_values = array('s',$user);
    $query_result = mysqli_select_query($db1,'admin_passwords','*',$where_clause,$where_values,'');
    if ($row = mysqli_fetch_assoc($query_result))
    {
        $query_result2 = mysqli_select_query($db2,'accounts','*','',array(),'');
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
    $user = get_session_var(SV_USER);
    $result = '';
    $where_clause = 'username=?';
    $where_values = array('s',$user);
    $query_result = mysqli_select_query($db1,'admin_passwords','*',$where_clause,$where_values,'');
    if ($row = mysqli_fetch_assoc($query_result))
    {
        $query_result2 = mysqli_select_query($db2,'funds','*','',array(),'');
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
/*
  Function initialise_archive_table_data
*/
//==============================================================================

function initialise_archive_table_data($db)
{
    global $custom_pages_path;
    global $relative_path;
    $dbname = admin_db_name();
    $directory_created = false;
    $query_result = mysqli_query_normal($db,"SHOW FULL TABLES FROM `$dbname`");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $table = $row["Tables_in_$dbname"];
        if (substr($table,0,9) == 'archived_')
        {
            if (!is_dir("$custom_pages_path/$relative_path/tables/$table"))
            {
                // Create directory and class script for the table.
                mkdir("$custom_pages_path/$relative_path/tables/$table",0755);
                $ofp = fopen("$custom_pages_path/$relative_path/tables/$table/$table.php",'w');
                fprintf($ofp,"<?php class tables_$table {} ?>\n");
                fclose($ofp);
                $directory_created = true;
            }
            if (substr($table,9,5) == 'trans')
            {
                // Create view and splits relationship for archived transactions.
                create_view_structure("_view_$table",$table,"account IS NOT NULL ORDER BY account ASC, date DESC, seq_no DESC");
                set_primary_key_on_view("$table",'account');
                set_primary_key_on_view("$table",'seq_no');
                $where_clause = "table_name='transactions'";
                $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,array(),'');
                while ($row2 = mysqli_fetch_assoc($query_result2))
                {
                    $set_fields = 'list_desktop,list_mobile';
                    $set_values = array('i',$row2['list_desktop'],'i',$row2['list_mobile']);
                    $where_clause = 'table_name=? AND field_name=?';
                    $where_values = array('s',$table,'s',$row2['field_name']);
                    mysqli_update_query($db,'dba_table_fields',$set_fields,$set_values,$where_clause,$where_values);
                }
                $splits_table = str_replace('transactions','splits',$table);
                $fields = 'table_name,relationship_name,query';
                $values = array('s',$table,'s','Splits','s',"SELECT * FROM $splits_table WHERE transact_seq_no=$seq_no");
                mysqli_insert_query($db,'dba_relationships',$fields,$values);
            }
            elseif (substr($table,9,5) == 'split')
            {
                // Create view for archived splits.
                create_view_structure("_view_$table",$table,"account IS NOT NULL ORDER BY account ASC, transact_seq_no DESC, split_no ASC");
                set_primary_key_on_view("$table",'account');
                set_primary_key_on_view("$table",'transact_seq_no');
                set_primary_key_on_view("$table",'split_no');
                $where_clause = "table_name='splits'";
                $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,array(),'');
                while ($row2 = mysqli_fetch_assoc($query_result2))
                {
                    $set_fields = 'list_desktop,list_mobile';
                    $set_values = array('i',$row2['list_desktop'],'i',$row2['list_mobile']);
                    $where_clause = 'table_name=? AND field_name=?';
                    $where_values = array('s',$table,'s',$row2['field_name']);
                    mysqli_update_query($db,'dba_table_fields',$set_fields,$set_values,$where_clause,$where_values);
                }
            }
        }
    }
    // Make all archive tables read-only.
    $set_fields = 'widget_type';
    $set_values = array('s','static');
    $where_clause = "table_name LIKE 'archived_%'";
    $where_values = array();
    mysqli_update_query($db,'dba_table_fields',$set_fields,$set_values,$where_clause,$where_values);
  
    // Output warning if new tables have been added.
    if ($directory_created)
    {
        print("<p><strong>NOTE</strong> - Please update table data for the database.</p>\n");
    }
}

//==============================================================================
/*
  Function output_archive_table_links

  This function is called by the database home page script to generate links to
  all archive transaction/split tables.
*/
//==============================================================================

function output_archive_table_links($db)
{
    $dbname = admin_db_name();
    $archive_tables = array();
    $query_result = mysqli_query_normal($db,"SHOW FULL TABLES FROM `$dbname`");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        if (substr($row["Tables_in_$dbname"],0,9) == 'archived_')
        {
            $table = $row["Tables_in_$dbname"];
            $tok = strtok($table,'_');
            $type = ucwords(strtok('_'));
            $year = strtok('_');
            $archive_tables["$year - $type"] = $table;
        }
    }
    krsort($archive_tables);
    print("<ul>\n");
    foreach ($archive_tables as $description => $table)
    {
        print("<li><a href=\"./?-table=_view_$table\">$description</a></li>\n");
    }
    print("</ul>\n");
}

//==============================================================================
?>
