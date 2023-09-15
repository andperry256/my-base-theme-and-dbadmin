<?php
//==============================================================================

global $FinanceReportDir;
global $Location;
$db = admin_db_connect();
$csv_file = 'report_'.date('Ymd').'_'.date('His').'.csv';
$ofp2 = fopen("$FinanceReportDir/$csv_file", "w");
fprintf($ofp2,"Date,Account,Payee,Fund,Category,Memo,Amount,Running Balance\n");
print("<h1>Transaction Report</h1>\n");
$account_exclusions = select_excluded_accounts('account');
$fund_exclusions = select_excluded_funds('fund');
if (isset($_GET['autodates']))
{
  if (isset($_GET['start_date']))
  {
    $_POST['start_year'] = substr($_GET['start_date'],0,4);
    $_POST['start_month'] = substr($_GET['start_date'],5,2);
    if (isset($_GET['end_date']))
    {
      $_POST['end_year'] = substr($_GET['end_date'],0,4);
      $_POST['end_month'] = substr($_GET['end_date'],5,2);
      $_POST['submitted'] = true;
      $_POST['date_range'] = 'select';
    }
  }
}

// Account filter
if (!isset($_GET['account']))
{
  $account_name = '%';
}
else
{
  $account_name = $_GET['account'];
  if ($account_name == '-none-')
  {
    print("<h2>Account - Unallocated</h2>\n");
  }
  else
  {
    print("<h2>Account - $account_name</h2>\n");
  }
}

// Fund filter
if (!isset($_GET['fund']))
{
  $fund_name = '%';
}
else
{
  $fund_name = urldecode($_GET['fund']);
  if ($fund_name == '-none-')
  {
    print("<h2>Fund - Unallocated</h2>\n");
  }
  elseif ($fund_name == '-transfer-')
  {
    print("<h2>Fund - Transfers</h2>\n");
  }
  elseif (strpos($fund_name,'%') !== false)
  {
    $superfund_name = strtok($fund_name,':');
    print("<h2>Fund - $superfund_name [ALL]</h2>\n");
  }
  else
  {
    print("<h2>Fund - $fund_name</h2>\n");
  }
}

// Category filter
if (!isset($_GET['category']))
{
  $category_name = '%';
}
else
{
  $category_name = urldecode($_GET['category']);
  if ($category_name == '-none-')
  {
    print("<h2>Category - Unallocated</h2>\n");
  }
  elseif ($category_name == '-transfer-')
  {
    print("<h2>Category - Transfers</h2>\n");
  }
  elseif (strpos($category_name,'%') !== false)
  {
    $supercategory_name = strtok($category_name,':');
    print("<h2>Category - $supercategory_name [ALL]</h2>\n");
  }
  else
  {
    print("<h2>Category - $category_name</h2>\n");
  }
}

// Payee filter
if (!isset($_GET['payee']))
{
  $payee_name = '%';
}
else
{
  $payee_name = urldecode($_GET['payee']);
  if ($payee_name == '-none-')
  {
    print("<h2>Payee - Unallocated</h2>\n");
  }
  else
  {
    print("<h2>Payee - $payee_name</h2>\n");
  }
}

// Currency filter
if (isset($_GET['currency']))
{
  $currency = $_GET['currency'];
}
else
{
  $currency = 'GBP';
}
if ($currency != 'GBP')
{
  print("<h2>Currency - $currency</h2>\n");
}

// Determine start and end months
$error = false;
if (isset($_GET['start_month']))
{
  $start_month = $_GET['start_month'];
}
else
{
  // Default to no date limit
  $start_month = NO_START_MONTH;
}
if (isset($_GET['end_month']))
{
  $end_month = $_GET['end_month'];
}
else
{
  // Default to no date limit
  $end_month = NO_END_MONTH;
}
if (isset($_POST['submitted']))
{
  switch ($_POST['date_range'])
  {
    case 'select':
      if ((is_numeric($_POST['start_year'])) && (is_numeric($_POST['start_month'])))
      {
        // Set start year/month to an actual date. Set end date if specified as being the same.
        $start_month = sprintf("%04d-%02d",$_POST['start_year'],$_POST['start_month']);
        if (($_POST['end_year'] == 'same') && ($_POST['end_month'] = 'same'))
        {
          $end_month = $start_month;
        }
      }
      if ((is_numeric($_POST['end_year'])) && (is_numeric($_POST['end_month'])))
      {
        // Set end year/month to an actual date.
        $end_month = sprintf("%04d-%02d",$_POST['end_year'],$_POST['end_month']);
      }
      break;

    case 'this_month':
      $start_month = accounting_month(date('Y-m-d'));
      $end_month = $start_month;
      break;

    case 'last_month':
      $current_month = accounting_month(date('Y-m-d'));
      $month_value = (int)substr($current_month,5,2);
      $year_value = (int)substr($current_month,0,4);
      $month_value--;
      if ($month_value == 0)
      {
        $month_value = 12;
        $year_value--;
      }
      $start_month = sprintf("%04d-%02d",$year_value,$month_value);
      $end_month = $start_month;
      break;

    case 'this_year':
      $start_month = year_start(date('Y-m-d'));
      $end_month = year_end(date('Y-m-d'));
      break;

    case 'last_year':
      $year = (int)date('Y') - 1;
      $month = date('m');
      $start_month = year_start("$year-$month-01");
      $end_month = year_end("$year-$month-01");
      break;
  }
}
if ($start_month > $end_month)
{
  $error = true;
  print("<p><b>ERROR</b> - start month is later than end month.</p>\n");
}

if (((isset($_POST['submitted'])) || (isset($_GET['start_month'])) || (isset($_GET['end_month']))) && (!$error))
{
  mysqli_query_normal($db,"DROP TABLE IF EXISTS report");
  mysqli_query_normal($db,"CREATE TEMPORARY TABLE report LIKE transaction_report");

  $where_clause = "currency=? AND account LIKE ? $account_exclusions $fund_exclusions AND fund LIKE ? AND category LIKE ? AND payee LIKE ? AND sched_freq='#'";
  $where_values = array('s',$currency,'s',$account_name,'s',$fund_name,'s',$category_name,'s',$payee_name);
  $query_result = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
  while ($row = mysqli_fetch_assoc($query_result))
  {
    // Add transaction with matching parameters into the table
    if (!empty($row['chq_no']))
    {
      $chq_no = $row['chq_no'];
    }
    else
    {
      $chq_no = 'NULL';
    }
    $fields = "account,seq_no,split_no,date,chq_no,payee,credit_amount,debit_amount,fund,category,memo,acct_month,reconciled,target_account,source_account";
    $values = array('s',$row['account'],'i',$row['seq_no'],'i',0,'s',$row['date'],'i',$chq_no,'s',$row['payee'],'d',$row['credit_amount'],'d',$row['debit_amount'],'s',$row['fund'],'s',$row['category'],'s',$row['memo'],'s',$row['acct_month'],'i',$row['reconciled'],'s',$row['target_account'],'s',$row['source_account']);
    mysqli_insert_query($db,'report',$fields,$values);
  }

  // Process associated splits as required
  if (($fund_name != '%') || ($category_name != '%'))
  {
    // Fund and/or category has been specified. Process any splits relating
    // to the given fund/category.
    $where_clause = "fund LIKE ? $fund_exclusions AND category LIKE ?";
    $where_values = array('s',$fund_name,'s',$category_name);
    $query_result = mysqli_select_query($db,'splits','*',$where_clause,$where_values,'');
    while ($row = mysqli_fetch_assoc($query_result))
    {
      // Check for transaction directly related to the split
      $where_clause = "currency=? AND account LIKE ? $account_exclusions $fund_exclusions AND account=? AND seq_no=?";
      $where_values = array('s',$currency,'s',$account_name,'s',$row['account'],'i',$row['transact_seq_no']);
      $query_result2 = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
      if (($row2 = mysqli_fetch_assoc($query_result2)) && ($row2['sched_freq'] == '#'))
      {
        // Add split with matching parameters into the table
        $fields = 'account,seq_no,split_no,credit_amount,debit_amount,fund,category,memo,acct_month';
        $values = array('s',$row['account'],'i',$row['transact_seq_no'],'i',$row['split_no'],'d',$row['credit_amount'],'d',$row['debit_amount'],'s',$row['fund'],'s',$row['category'],'s',$row['memo'],'s',$row['acct_month']);
        mysqli_insert_query($db,'report',$fields,$values);
        // Add record fields from parent transaction
        if (!empty($row2['chq_no']))
        {
          $chq_no = $row2['chq_no'];
        }
        else
        {
          $chq_no = 'NULL';
        }
        $set_fields = 'date,chq_no,payee,reconciled,target_account,source_account';
        $set_values = array('s',$row2['date'],'i',$chq_no,'s',$row2['payee'],'i',$row2['reconciled'],'s',$row2['target_account'],'s',$row2['source_account']);
        $where_clause = 'account=? AND seq_no=? AND split_no=?';
        $where_values = array('s',$row['account'],'i',$row['transact_seq_no'],'i',$row['split_no']);
        mysqli_update_query($db,'report',$set_fields,$set_values,$where_clause,$where_values);
      }

      // Check for transaction at opposite end of transfer
      $where_clause = "currency=? AND account LIKE ? $account_exclusions $fund_exclusions AND source_account=? AND source_seq_no=?";
      $where_values = array('s',$currency,'s',$account_name,'s',$row['account'],'i',$row['transact_seq_no']);
      $query_result2 = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
      if (($row2 = mysqli_fetch_assoc($query_result2)) && ($row2['sched_freq'] == '#'))
      {
        // Add split with matching parameters into the table
        $fields = 'account,seq_no,split_no,credit_amount,debit_amount,fund,category,memo,acct_month';
        $values = array('s',$row['account'],'i',$row['transact_seq_no'],'i',$row['split_no'],'d',-$row['credit_amount'],'d',-$row['debit_amount'],'s',$row['fund'],'s',$row['category'],'s',$row['memo'],'s',$row['acct_month']);
        mysqli_insert_query($db,'report',$fields,$values);
        // Add record fields from parent transaction
        if (!empty($row2['chq_no']))
        {
          $chq_no = $row2['chq_no'];
        }
        else
        {
          $chq_no = 'NULL';
        }
        $set_fields = 'date,chq_no,payee,reconciled,target_account,source_account';
        $set_values = array('s',$row2['date'],'i',$chq_no,'s',$row2['payee'],'i',$row2['reconciled'],'s',$row2['target_account'],'s',$row2['source_account']);
        $where_clause = 'account=? AND seq_no=? AND split_no=?';
        $where_values = array('s',$row['account'],'i',$row['transact_seq_no'],'i',$row['split_no']);
        mysqli_update_query($db,'report',$set_fields,$set_values,$where_clause,$where_values);
      }
    }
  }
  elseif ($account_name != '%')
  {
    // Account has been specified. All funds and categories are included.
    // Process all associated splits.
    $where_clause = "account IS NOT NULL $fund_exclusions";
    $query_result = mysqli_select_query($db,'splits','*',$where_clause,array(),'');
    while ($row = mysqli_fetch_assoc($query_result))
    {
      // Check for transaction directly related to the split
      $where_clause = "currency=? AND account LIKE ? $account_exclusions $fund_exclusions AND account=? AND seq_no=?";
      $where_values = array('s',$currency,'s',$account_name,'s',$row['account'],'i',$row['transact_seq_no']);
      $query_result2 = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
      if (($row2 = mysqli_fetch_assoc($query_result2)) && ($row2['sched_freq'] == '#'))
      {
        // Add split with matching parameters into the table
        $fields = 'account,seq_no,split_no,credit_amount,debit_amount,fund,category,memo,acct_month';
        $values = array('s',$row['account'],'i',$row['transact_seq_no'],'i',$row['split_no'],'d',$row['credit_amount'],'d',$row['debit_amount'],'s',$row['fund'],'s',$row['category'],'s',$row['memo'],'s',$row['acct_month']);
        mysqli_insert_query($db,'report',$fields,$values);
        // Add record fields from parent transaction
        if (!empty($row2['chq_no']))
        {
          $chq_no = $row2['chq_no'];
        }
        else
        {
          $chq_no = 'NULL';
        }
        $set_fields = 'date,chq_no,payee,reconciled,target_account,source_account';
        $set_values = array('s',$row2['date'],'i',$chq_no,'s',$row2['payee'],'i',$row2['reconciled'],'s',$row2['target_account'],'s',$row2['source_account']);
        $where_clause = 'account=? AND seq_no=? AND split_no=?';
        $where_values = array('s',$row['account'],'i',$row['transact_seq_no'],'i',$row['split_no']);
        mysqli_update_query($db,'report',$set_fields,$set_values,$where_clause,$where_values);
      }
    }
  }

  // Re-order table
  mysqli_query_normal($db,"ALTER TABLE report ORDER BY acct_month ASC, date ASC, seq_no ASC, split_no ASC");

  // Create link to CSV report.
  print("<p><a href=\"$BaseURL/admin_data/finances/$csv_file\" target=\"_blank\">CSV Report</a> (opens in new window)</p>\n");

  // Print report
  $running_total = 0;
  $running_balance = 0;
  $last_accounting_month = '';
  $table_cell_style = "vertical-align:top;padding:3px;";
  $table_cell_style_ra = $table_cell_style. " text-align:right;";
  $table_header_style = $table_cell_style." font-weight:bold;";
  $table_header_style_ra = $table_header_style." text-align:right;";

  $where_clause = 'acct_month<=?';
  $where_values = array('s',$end_month);
  $query_result = mysqli_select_query($db,'report','*',$where_clause,$where_values,'');
  if (mysqli_num_rows($query_result) == 0)
  {
    // Empty result
    print("<p style=\"color:blue\">No transactions found</p>\n");
  }
  else
  {
    $add_clause = 'ORDER BY acct_month ASC, date ASC, seq_no ASC, split_no ASC';
    $query_result = mysqli_select_query($db,'report','*','',array(),$add_clause);
    $row_count = mysqli_num_rows($query_result);
    $row_no = 0;
    while (($row = mysqli_fetch_assoc($query_result)) || ($row_no < $row_count))
    {
      if ($row_count == 0)
      {
        // Empty result
        print("<p style=\"color:blue\">No transactions found</p>\n");
        break;
      }
      elseif (($row_no == $row_count) || ($row['acct_month'] > $end_month))
      {
        // Set loop terminator
        $accounting_month = '#';
      }
      else
      {
        $accounting_month = $row['acct_month'];
      }
      if (($accounting_month < $start_month) && ($accounting_month != '#'))
      {
        // Prior to start month
        $row_no++;
        if (($row['fund'] == '-split-') && (($fund_name != '%') || ($category_name != '%')))
        {
          // Transaction is a split but the fund and/or category has been specified - no action
        }
        elseif (($row['category'] == '-transfer-') && ($account_name == '%') && ($payee_name == '%'))
        {
          // Transaction is a transfer and the account and payee have not been specified - no action
        }
        else
        {
          // Transaction would have been counted, so update running balance
          $running_balance = add_money($running_balance,subtract_money($row['credit_amount'],$row['debit_amount']));
        }
        if ($row_no == $row_count)
        {
          print("<p style=\"color:blue\">No transactions found</p>\n");
          break;
        }
      }
      elseif (($row['fund'] == '-split-') && (($fund_name != '%') || ($category_name != '%')))
      {
        // Transaction is a split but the fund and/or category has been specified
        $row_no++;
      }
      elseif (($row['category'] == '-transfer-') && ($account_name == '%') && ($payee_name == '%'))
      {
        // Transaction is a transfer and the account and payee have not been specified
        $row_no++;
      }
      else
      {
        if ($accounting_month != $last_accounting_month)
        {
          // New month
          if (!empty($last_accounting_month))
          {
            // Monthly total / end of table
            print("<tr><td style=\"$table_cell_style\"><b>Total</b></td>");
            print("<td style=\"$table_cell_style\">&nbsp;</td>");
            print("<td style=\"$table_cell_style\">&nbsp;</td>");
            print("<td style=\"$table_cell_style_ra\">");
            if ($monthly_total >= 0)
            {
              printf("%01.2f", $monthly_total);
            }
            else
            {
              printf("<span style=\"color:red\">%01.2f</span>", $monthly_total);
            }
            $running_total = add_money($running_total,$monthly_total);
            print("</td>");
            print("<td style=\"$table_cell_style\">&nbsp;</td></tr>\n");
            print("</table>\n");

            if ($accounting_month == '#')
            {
              // Final iteration of loop (beyond last record of query)
              print("<h2>Grand Total</h2>\n");
              print("<table>\n");
              print("<tr> <td style=\"$table_header_style\" width=\"100px\">&nbsp;</td>");
              print("<td style=\"$table_header_style\" width=\"150px\">&nbsp</td>");
              print("<td style=\"$table_header_style\" width=\"300px\">&nbsp</td>");
              print("<td style=\"$table_header_style_ra\" width=\"100px\">Amount</td>\n");
              print("<td style=\"$table_header_style_ra\" width=\"100px\">&nbsp;</td></tr>\n");
              print("<td style=\"$table_cell_style\" colspan=\"3\">&nbsp;</td>");
              print("<td style=\"$table_cell_style_ra\">");
              if ($running_total >= 0)
              {
                printf("%01.2f", $running_total);
              }
              else
              {
                printf("<span style=\"color:red\">%01.2f</span>", $running_total);
              }
              print("</table>\n");
              break;
            }
          }
          $month_description = MonthName((int)substr($accounting_month,5,2)).' '.substr($accounting_month,0,4);
          print("<h2>$month_description</h2>\n");

          // New table header
          print("<table>\n");
          print("<tr> <td style=\"$table_header_style\" width=\"100px\">Date</td>");
          print("<td style=\"$table_header_style\" width=\"150px\">Account</td>");
          print("<td style=\"$table_header_style\" width=\"300px\">Details</td>");
          print("<td style=\"$table_header_style_ra\" width=\"100px\">Amount</td>\n");
          print("<td style=\"$table_header_style_ra\" width=\"100px\">Running Balance</td></tr>\n");
          $last_accounting_month = $accounting_month;
          $monthly_total = 0;
        }
        // Date
        print("<tr><td style=\"$table_cell_style\">{$row['date']}");
        if ($accounting_month != accounting_month($row['date']))
        {
          print('[*]');
        }
        print("</td>");
        $where_clause = 'label=?';
        $where_values = array('s',$row['account']);
        $query_result2 = mysqli_select_query($db,'accounts','*',$where_clause,$where_values,'');

        // Account
        if ($row2 = mysqli_fetch_assoc($query_result2))
        {
          $account_description = $row2['name'];
        }
        else
        {
          $account_description = '';  // This should never occur
        }
        $split_no = $row['split_no'];
        if ($split_no != 0)
        {
          $account_description = "$account_description [split]";
        }
        print("<td style=\"$table_cell_style\">$account_description</td>");

        // Description
        $account = $row['account'];
        $seq_no = $row['seq_no'];
        if ($split_no == 0)
        {
          $primary_keys = array();
          $primary_keys['account'] = $account;
          $primary_keys['seq_no'] = $seq_no;
          $record_id = encode_record_id($primary_keys);
          unset($primary_keys);
          $edit_link = "index.php?-action=edit&-table=transactions&-recordid=$record_id";
        }
        else
        {
          $primary_keys = array();
          $primary_keys['account'] = $account;
          $primary_keys['transact_seq_no'] = $seq_no;
          $primary_keys['split_no'] = $split_no;
          $record_id = encode_record_id($primary_keys);
          unset($primary_keys);
          $edit_link = "index.php?-action=edit&-table=splits&-recordid=$record_id";
        }
        print("<td style=\"$table_cell_style\"><a href=\"$edit_link\" target=\"_blank\">");
        print("{$row['payee']}<br />[F] {$row['fund']}<br />[C] {$row['category']}");
        if (!empty($row['memo']))
        {
          $tempstr= str_replace('%','%%',$row['memo']);
          print("<br />[M] $tempstr");
        }
        print("</a></td>");

        // Amount
        $credit_amount = $row['credit_amount'];
        $debit_amount = $row['debit_amount'];
        if ($debit_amount != 0)
        {
          printf("<td style=\"$table_cell_style_ra\"><span style=\"color:red\">%01.2f</span></td>",-$debit_amount);
          if (($account_name != '%' ) && ($fund_name == '%') && ($category_name == '%') && ($row['category'] == '-split-') && (empty($row['source_account'])))
          {
            // Balances to be updated when processing splits.
            $update_balances = false;
          }
          else
          {
            $monthly_total = subtract_money($monthly_total,$debit_amount);
            $running_balance = subtract_money($running_balance,$debit_amount);
            $update_balances = true;
          }
          $amount = -$debit_amount;
        }
        else
        {
          printf("<td style=\"$table_cell_style_ra\">%01.2f</td>",$credit_amount);
          if (($account_name != '%' ) && ($fund_name == '%') && ($category_name == '%') && ($row['category'] == '-split-') && (empty($row['source_account'])))
          {
            // Balances to be updated when processing splits.
            $update_balances = false;
          }
          else
          {
            $monthly_total = add_money($monthly_total,$credit_amount);
            $running_balance = add_money($running_balance,$credit_amount);
            $update_balances = true;
          }
          $amount = $credit_amount;
        }
        if (!$update_balances)
        {
          print("<td style=\"$table_cell_style_ra\">&nbsp;</td>");
        }
        elseif ($running_balance < 0)
        {
          printf("<td style=\"$table_cell_style_ra\"><span style=\"color:red\">%01.2f</span></td>",$running_balance);
        }
        else
        {
          printf("<td style=\"$table_cell_style_ra\">%01.2f</td>",$running_balance);
        }

        print("</td></tr>\n");

        // Add line to CSV file
        $tempstr= str_replace('%','%%',$row['memo']);
        fprintf($ofp2,"{$row['date']},\"$account_description\",\"{$row['payee']}\",\"{$row['fund']}\",\"{$row['category']}\",\"$tempstr\",".sprintf("%1.2f",$amount).",".sprintf("%1.2f",$running_balance)."\n");
        $row_no++;
      }
    }
    print("</table>\n");
  }
}

// Generate form to input date range
print("<div style=\"height:8px\">&nbsp;</div>\n");
print("<form method=\"post\">\n");
print("<table cellpadding=\"5\">\n");
print("<tr><td><input type=\"radio\" name=\"date_range\" value=\"select\" checked></td><td>Select range</td></tr>\n");
print("<tr><td><input type=\"radio\" name=\"date_range\" value=\"this_month\"></td><td>This month</td></tr>\n");
print("<tr><td><input type=\"radio\" name=\"date_range\" value=\"last_month\"></td><td>Last month</td></tr>\n");
print("<tr><td><input type=\"radio\" name=\"date_range\" value=\"this_year\"></td><td>This year</td></tr>\n");
print("<tr><td><input type=\"radio\" name=\"date_range\" value=\"last_year\"></td><td>Last year</td></tr>\n");
print("</table>\n");
print("<table cellpadding=\"5\">\n");
print("<tr><td>Start Month:</td>\n");
print("<td><select name=\"start_month\">\n");
print("<option value=\"all\" selected>All Dates</option>\n");
for ($month = 1; $month <= 12; $month++)
{
  $month_name = monthName($month);
  print("<option value=\"$month\">$month_name</option>\n");
}
print("</select></td>\n");
print("<td><select name=\"start_year\">\n");
print("<option value=\"all\" selected>All Dates</option>\n");
$this_year = (int)date('Y');
for ($year = START_YEAR; $year <= $this_year; $year++)
{
  print("<option value=\"$year\">$year</option>\n");
}
print("</select></td></tr>\n");
print("<tr><td>End Month:</td>\n");
print("<td><select name=\"end_month\">\n");
print("<option value=\"same\" selected>Same as Start</option>\n");
print("<option value=\"all\">All Dates</option>\n");
for ($month = 1; $month <= 12; $month++)
{
  $month_name = monthName($month);
  print("<option value=\"$month\">$month_name</option>\n");
}
print("</select></td>\n");
print("<td><select name=\"end_year\">\n");
print("<option value=\"same\" selected>Same as Start</option>\n");
print("<option value=\"all\">All Dates</option>\n");
$this_year = (int)date('Y');
for ($year = START_YEAR; $year <= $this_year; $year++)
{
  print("<option value=\"$year\">$year</option>\n");
}
print("</select></td></tr>\n");
print("<tr><td>&nbsp;</td><td colspan=\"2\"><input type=\"submit\" name=\"submitted\" value=\"Show\"></td></tr>\n");

print("</table>\n");
print("</form>\n");

//==============================================================================
?>
