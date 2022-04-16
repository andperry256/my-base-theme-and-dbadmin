<?php
//==============================================================================

$db = admin_db_connect();
print("<h1>Archive Oldest Year</h1>\n");

if (isset($_POST['new_start_date']))
{
  $new_start_date = $_POST['new_start_date'];
  $archive_end_date = AddDays($new_start_date,-1);
  $balances = array();
  $error = false;
  $query_result = mysqli_query($db,"SELECT * from accounts");
  print("<p>");
  while (($row = mysqli_fetch_assoc($query_result)) && ($error === false))
  {
    print("Calculating balances for account {$row['name']}<br />\n");
    $account = $row['label'];
    if (!isset($balances[$account]))
    {
      $balances[$account] = array();
    }
    $query_result2 = mysqli_query($db,"SELECT * FROM transactions WHERE account='$account' AND date<'$new_start_date'");
    while ($row2 = mysqli_fetch_assoc($query_result2))
    {
      if (($row2['fund'] == '-split-') && (empty($row2['source_account'])))
      {
        // Process splits
        $query_result3 = mysqli_query($db,"SELECT * FROM splits WHERE account='$account' AND transact_seq_no={$row2['seq_no']}");
        $splits_total = 0;
        while ($row3 = mysqli_fetch_assoc($query_result3))
        {
          if (!isset($balances[$account][$row3['fund']]))
          {
            $balances[$account][$row3['fund']] = 0;
          }
          $balances[$account][$row3['fund']] = add_money($balances[$account][$row3['fund']],subtract_money($row3['credit_amount'],$row3['debit_amount']));
          $splits_total = add_money($splits_total,subtract_money($row3['credit_amount'],$row3['debit_amount']));
        }
        if ($splits_total != subtract_money($row2['credit_amount'],$row2['debit_amount']))
        {
          print("ERROR - Discrepancy in splits total for transaction {$row2['seq_no']} in account {$row['name']}<br />\n");
          $error = true;
          break;
        }
      }
      elseif ($row2['fund'] == '-split-')
      {
        // Process splits on other side of transfer
        $query_result3 = mysqli_query($db,"SELECT * FROM splits WHERE account='{$row2['source_account']}' AND transact_seq_no={$row2['source_seq_no']}");
        $splits_total = 0;
        while ($row3 = mysqli_fetch_assoc($query_result3))
        {
          if (!isset($balances[$account][$row3['fund']]))
          {
            $balances[$account][$row3['fund']] = 0;
          }
          $balances[$account][$row3['fund']] = add_money($balances[$account][$row3['fund']],subtract_money($row3['debit_amount'],$row3['credit_amount']));
          $splits_total = add_money($splits_total,subtract_money($row3['debit_amount'],$row3['credit_amount']));
        }
        if ($splits_total != subtract_money($row2['credit_amount'],$row2['debit_amount']))
        {
          print("ERROR - Discrepancy in splits total for transaction {$row2['seq_no']} in account {$row['name']}<br />\n");
          $error = true;
          break;
        }
      }
      else
      {
        // No splits
        if (!isset($balances[$account][$row2['fund']]))
        {
          $balances[$account][$row2['fund']] = 0;
        }
        $balances[$account][$row2['fund']] = add_money($balances[$account][$row2['fund']],subtract_money($row2['credit_amount'],$row2['debit_amount']));
        if ($row2['fund'] == '-nosplit-')
        {
          print("ERROR - Fund set to -nosplit- for transaction {$row2['seq_no']} in account {$row['name']}<br />\n");
          $error = true;
          break;
        }
      }
    }
  }

  // Calculate the sequence number for the new 'Balance B/F' transaction
  // for each account.
  $bbf_seq_no = array();
  $query_result = mysqli_query($db,"SELECT * from accounts");
  while (($row = mysqli_fetch_assoc($query_result)) && ($error === false))
  {
    $account = $row['label'];
    if ($row2 = mysqli_fetch_assoc(mysqli_query($db,"SELECT * FROM transactions WHERE account='$account' AND date>='$new_start_date' ORDER BY date ASC, seq_no ASC LIMIT 1")))
    {
      $bbf_seq_no[$account] = $row2['seq_no'] - 5;
    }
    elseif  ($row2 = mysqli_fetch_assoc(mysqli_query($db,"SELECT * FROM transactions WHERE account='$account' AND date<'$new_start_date' ORDER BY seq_no DESC LIMIT 1")))
    {
      $bbf_seq_no[$account] = $row2['seq_no'] + 10;
    }
    else
    {
      $bbf_seq_no[$account] = 10;
    }
    if (( $bbf_seq_no[$account] <= 0 ) ||
        (mysqli_num_rows(mysqli_query($db,"SELECT * FROM transactions WHERE account='$account' AND seq_no={$bbf_seq_no[$account]}")) != 0))
    {
      print("ERROR - Unable to set record number for 'Balance B/F' in account {$row['name']}<br />\n");
      $error = true;
      break;
    }
  }

  // Delete any zero balances
  foreach ($balances as $account => $a)
  {
    foreach ($a as $fund => $balance)
    {
      if ($balance == 0)
      {
        unset($balances[$account][$fund]);
      }
    }
  }

  if (!$error)
  {
    // Perform the update

    // Copy transaction data to archive tables
    print("Creating archive tables and copying transactions<br />\n");
    $year = substr($archive_end_date,0,4);
    $acct_month = accounting_month($archive_end_date);
    mysqli_query($db,"DROP TABLE IF EXISTS archived_transactions_$year");
    mysqli_query($db,"DROP TABLE IF EXISTS archived_splits_$year");
    mysqli_query($db,"CREATE TABLE archived_transactions_$year AS SELECT * FROM transactions WHERE date<='$archive_end_date'");
    mysqli_query($db,"CREATE TABLE archived_splits_$year LIKE splits");
    $query_result = mysqli_query($db,"SELECT * FROM archived_transactions_$year");
    while ($row = mysqli_fetch_assoc($query_result))
    {
      mysqli_query($db,"INSERT INTO archived_splits_$year SELECT * FROM splits WHERE account='{$row['account']}' AND transact_seq_no={$row['seq_no']}");
    }

    // Delete archived transactions from main tables
    print("Deleting old transactions<br />\n");
    $query_result = mysqli_query($db,"SELECT * FROM transactions WHERE date<'$new_start_date'");
    while ($row = mysqli_fetch_assoc($query_result))
    {
      mysqli_query($db,"DELETE FROM splits WHERE account='{$row['account']}' AND transact_seq_no={$row['seq_no']}");
    }
    mysqli_query($db,"DELETE FROM transactions WHERE date<'$new_start_date'");

    // Add new 'Balance B/F' transactions
    $query_result = mysqli_query($db,"SELECT * from accounts");
    while ($row = mysqli_fetch_assoc($query_result))
    {
      print("Creating 'Balance B/F' transaction for account {$row['name']}<br />\n");
      $account = $row['label'];
      $transaction_seq_no = $bbf_seq_no[$account];
      $fund_count = count($balances[$account]);
      if ($fund_count == 0)
      {
        // No previous transactions or all zero at new start date - no action required.
      }
      elseif ($fund_count == 1)
      {
        // Add transaction without splits.
        foreach ($balances[$account] as $fund => $balance)
        {
          // This loop should only be executed once.
          $credit = ($balance > 0) ? $balance : 0;
          $debit = ($balance < 0) ? -$balance : 0;
          $query = "INSERT INTO transactions (account,seq_no,date,currency,payee,credit_amount,debit_amount,acct_month,fund,category,reconciled)";
          $query .= " VALUES ('$account',$transaction_seq_no,'$new_start_date','{$row['currency']}','Balance B/F',$credit,$debit,'$acct_month','$fund','-none-',1)";
          mysqli_query($db,$query);
        }
        update_account_balances($account,$new_start_date);
      }
      else
      {
        // Add transaction with splits
        $query = "INSERT INTO transactions (account,seq_no,date,currency,payee,credit_amount,debit_amount,acct_month,fund,category,reconciled)";
        $query .= " VALUES ('$account',$transaction_seq_no,'$new_start_date','{$row['currency']}','Balance B/F',0,0,'$acct_month','-split-','-split-',1)";
        mysqli_query($db,$query);
        $splits_total = 0;
        $split_no = 10;
        foreach ($balances[$account] as $fund => $balance)
        {
          $credit = ($balance > 0) ? $balance : 0;
          $debit = ($balance < 0) ? -$balance : 0;
          $query = "INSERT INTO splits (account,transact_seq_no,split_no,credit_amount,debit_amount,fund,category,acct_month)";
          $query .= " VALUES ('$account',$transaction_seq_no,$split_no,$credit,$debit,'$fund','-none-','$acct_month')";
          mysqli_query($db,$query);
          $split_no += 10;
          $splits_total = add_money($splits_total,subtract_money($credit,$debit));
        }
        $credit = ($splits_total > 0) ? $splits_total : 0;
        $debit = ($splits_total < 0) ? -$splits_total : 0;
        mysqli_query($db,"UPDATE transactions SET credit_amount=$credit,debit_amount=$debit WHERE account='$account' AND seq_no=$transaction_seq_no");
        update_account_balances($account,$new_start_date);
      }
    }
    print("Operation completed.</p>\n");
  }
}
else
{
  $this_year = (int)date('Y');
  for ($year=START_YEAR; $year<$this_year; $year++)
  {
    $year_start = sprintf("%04d-%02d-%02d",$year,YEAR_START_MONTH,MONTH_START_DAY);
    $next_year_start = sprintf("%04d-%02d-%02d",$year+1,YEAR_START_MONTH,MONTH_START_DAY);
    if (mysqli_num_rows(mysqli_query($db,"SELECT * FROM transactions WHERE date>='$year_start' AND date<'$next_year_start'")) > 20)
    {
      // Full year found
      $new_start_date = $next_year_start;
      $archive_end_date = AddDays($new_start_date,-1);
      break;
    }
  }
  if (!isset($new_start_date))
  {
    exit("Error - this should not occur!!");
  }
  print("<p>You are about to archive all transactions to $archive_end_date. Are you sure?</p>\n");
  print("<form method=\"post\">\n");
  print("<input type=\"submit\" value=\"Continue\">\n");
  print("<input type=\"hidden\" name=\"new_start_date\" value=\"$new_start_date\">\n");
  print("</form>\n");
}

//==============================================================================
?>
