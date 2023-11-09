<?php
//==============================================================================

function print_localised_superfund_balance_line($col1_style,$col2_style,$col3_style,$fund,$balance,$end_month,$currency)
{
    $description = "<span style=\"color:#00c\">Total $fund</span>";
    $fund .= ':%%';
    printf("<tr><td style=\"$col1_style\"><a href=\"index.php?-action=display_transaction_report&fund=$fund&currency=$currency\" target=\"blank\">$description</a></td>\n");
    print("<td style=\"$col2_style\">&nbsp;</td>");
    print("<td style=\"$col3_style\">");
    $link = "index.php?-action=display_transaction_report&fund=$fund&start_month=".NO_START_MONTH."&end_month=$end_month&currency=$currency";
    if ($balance >= 0)
    {
        printf("<a class=\"balance-positive\" target=\"_blank\" href=\"$link\">%01.2f</a>", $balance);
    }
    else
    {
        printf("<a class=\"balance-negative\" target=\"_blank\" href=\"$link\">%01.2f</a>", $balance);
    }
    print("</td></tr>\n");
}

//==============================================================================

function print_global_fund_balance_line($col1_style,$col2_style,$fund,$is_superfund,$balance,$end_month,$currency)
{
    if ($is_superfund)
    {
        $description = "<span style=\"color:#00c\">Total $fund</span>";
        $fund .= ':%%';
    }
    else
    {
        $description = $fund;
    }
    printf("<tr><td style=\"$col1_style\" width=\"150px\"><a href=\"index.php?-action=display_transaction_report&fund=$fund&currency=$currency\" target=\"blank\">$description</a></td>\n");
    print("<td style=\"$col2_style\" width=\"100px\">");
    $link = "index.php?-action=display_transaction_report&fund=$fund&start_month=".NO_START_MONTH."&end_month=$end_month&currency=$currency";
    if ($balance >= 0)
    {
        printf("<a class=\"balance-positive\" target=\"_blank\" href=\"$link\">%01.2f</a>", $balance);
    }
    else
    {
        printf("<a class=\"balance-negative\" target=\"_blank\" href=\"$link\">%01.2f</a>", $balance);
    }
    print("</td></tr>\n");
}

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
$fund_exclusions = select_excluded_funds('name');

$error = false;
if (isset($_POST['submitted']))
{
    switch ($_POST['balance_date'])
    {
        case 'now':
          $end_month = accounting_month(date('Y-m-d'));
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
          $end_month = sprintf("%04d-%02d",$year_value,$month_value);
          break;
    
        case 'last_year':
          $year = (int)date('Y') - 1;
          $month = date('m');
          $end_month = year_end("$year-$month-01");
          break;
    
        case 'select':
          if ((is_numeric($_POST['end_year'])) && (is_numeric($_POST['end_month'])))
          {
              // Set end year/month to an actual date.
              $end_month = sprintf("%04d-%02d",$_POST['end_year'],$_POST['end_month']);
          }
          else
          {
              $end_month = accounting_month(date('Y-m-d'));
          }
          break;
    }
}

if ((isset($_POST['submitted'])) && (!$error))
{
    $currency=$_POST['currency'];
    $end_date = MonthName((int)substr($end_month,5,2)).' '.substr($end_month,0,4);
    print("<h1>Fund Balances");
    if (!isset($off_screen))
    {
        print(" - $end_date");
    }
    print("</h1>\n");
    if ($currency != 'GBP')
    {
        print("<h2>Currency - $currency</h2>\n");
    }
  
    // Process localised funds
    print("<h2>Localised Funds</h2>\n");
    $balances = array();
    $add_clause = 'ORDER by sort_order ASC';
    $query_result = mysqli_select_query($db,'accounts','*','',array(),$add_clause);
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $balances[$row['label']] = 0;
    }
    print("<table style=\"$table_style\">\n");
    $superfund = '';
    $superfund_balance = 0;
    $where_clause = "type='localised' $fund_exclusions";
    $query_result = mysqli_select_query($db,'funds','*',$where_clause,array(),'');
    while ($row = mysqli_fetch_assoc($query_result))
    {
        foreach ($balances as $label => $value)
        {
            $balances[$label] = 0;
        }
        $fund = $row['name'];
        $last_superfund = $superfund;
        if (strpos($fund,':') !== false)
        {
            $superfund = strtok($fund,':');
        }
        else
        {
            $superfund = '';
        }
        if ($last_superfund != $superfund)
        {
            if ($last_superfund != '')
            {
                // Have just completed adding up totals for a given superfund
                print_localised_superfund_balance_line($table_cell_style,$table_cell_style,$table_cell_style_ra,$last_superfund,$superfund_balance,$end_month,$currency);
                print("</td></tr><tr><td style=\"$table_filler_line\">&nbsp;</td></tr>\n");
            }
            $superfund_balance = 0;
        }
    
        print("<tr><td style=\"$table_cell_style\" width=\"100px\"><a href=\"index.php?-action=display_transaction_report&fund=$fund&currency=$currency\" target=\"blank\">$fund</a></td>\n");
        $total = 0;
        $where_clause = "currency=? AND fund=? AND acct_month<=? AND sched_freq='#'";
        $where_values = array('s',$currency,'s',$fund,'s',$end_month);
        $query_result2 = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
        while ($row2 = mysqli_fetch_assoc($query_result2))
        {
            // Add transaction amount to account balance
            $balances[$row2['account']] = add_money($balances[$row2['account']],subtract_money($row2['credit_amount'],$row2['debit_amount']));
            $superfund_balance = add_money($superfund_balance,subtract_money($row2['credit_amount'],$row2['debit_amount']));
        }
        $where_clause = 'fund=? AND acct_month<=?';
        $where_values = array('s',$fund,'s',$end_month);
        $query_result2 = mysqli_select_query($db,'splits','*',$where_clause,$where_values,'');
        while ($row2 = mysqli_fetch_assoc($query_result2))
        {
            $where_clause = 'currency=? AND account=? AND seq_no=? AND acct_month<=?';
            $where_values = array('s',$currency,'s',$row2['account'],'i',$row2['transact_seq_no'],'s',$end_month);
            $query_result3 = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
            if (($row3 = mysqli_fetch_assoc($query_result3)) && ($row3['sched_freq'] == '#'))
            {
                // Add split amount to only/source account balance
                $balances[$row3['account']] = add_money($balances[$row3['account']],subtract_money($row2['credit_amount'],$row2['debit_amount']));
                $superfund_balance = add_money($superfund_balance,subtract_money($row2['credit_amount'],$row2['debit_amount']));
            }
            $where_clause = 'currency=? AND source_account=? AND source_seq_no=? AND acct_month<=?';
            $where_values = array('s',$currency,'s',$row2['account'],'i',$row2['transact_seq_no'],'s',$end_month);
            $query_result3 = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
            if (($row3 = mysqli_fetch_assoc($query_result3)) && ($row3['sched_freq'] == '#'))
            {
                // Subtract split amount from target account balance
                $balances[$row3['account']] = add_money($balances[$row3['account']],subtract_money($row2['debit_amount'],$row2['credit_amount']));
                $superfund_balance = add_money($superfund_balance,subtract_money($row2['debit_amount'],$row2['credit_amount']));
            }
        }
        foreach($balances as $label => $value)
        {
            if (round($value,2) != 0)
            {
                // Output account name and balance
                $where_clause = 'label=?';
                $where_values = array('s',$label);
                $query_result2 = mysqli_select_query($db,'accounts','*',$where_clause,$where_values,'');
                if ($row2 = mysqli_fetch_assoc($query_result2))
                {
                    print("<td style=\"$table_cell_style\" width=\"120px\">{$row2['name']}</td>");
                }
                print("<td style=\"$table_cell_style_ra\" width=\"50px\">");
                $link = "index.php?-action=display_transaction_report&account=$label&fund=$fund&start_month=".NO_START_MONTH."&end_month=$end_month&currency=$currency";
                if ($value >= 0)
                {
                    printf("<a class=\"balance-positive\" target=\"_blank\" href=\"$link\">%01.2f</a>", $value);
                }
                else
                {
                    printf("<a class=\"balance-negative\" target=\"_blank\" href=\"$link\">%01.2f</a>", $value);
                }
                print("</td></tr>\n");
                print("<tr><td style=\"$table_cell_style\">&nbsp;</td>\n");
                $total = add_money($total,$value);
            }
        }
        // Output fund total
        print("<td style=\"$table_cell_style\">Total</td><td style=\"$table_cell_style_total\">\n");
        if ($total >= 0)
        {
            printf("<span class=\"balance-positive\">%01.2f</span>", $total);
        }
        else
        {
            printf("<span class=\"balance-negative\">%01.2f</span>", $total);
        }
        print("</td></tr><tr><td style=\"$table_filler_line\">&nbsp;</td></tr>\n");
    }
    if ($last_superfund != '')
    {
        // Superfund total from last funds in list
        print_localised_superfund_balance_line($table_cell_style,$table_cell_style,$table_cell_style_ra,$last_superfund,$superfund_balance,$end_month,$currency);
        print("</td></tr><tr><td style=\"$table_filler_line\">&nbsp;</td></tr>\n");
    }
    print("</table>\n");
  
    // Process global funds
    print("<h2>Global Funds</h2>\n");
    print("<table style=\"$table_style\">\n");
    $superfund = '';
    $superfund_balance = 0;
    $where_clause = "type='global' $fund_exclusions";
    $query_result = mysqli_select_query($db,'funds','*',$where_clause,array(),'');
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $fund = $row['name'];
        $last_superfund = $superfund;
        if (strpos($fund,':') !== false)
        {
            $superfund = strtok($fund,':');
        }
        else
        {
            $superfund = '';
        }
        if ($last_superfund != $superfund)
        {
            if ($last_superfund != '')
            {
                // Have just completed adding up totals for a given superfund
                print_global_fund_balance_line($table_cell_style,$table_cell_style_ra,$last_superfund,true,$superfund_balance,$end_month,$currency);
            }
            $superfund_balance = 0;
        }
        $balance = 0;
        $where_clause = "currency=? AND fund=? AND acct_month<=? AND sched_freq='#'";
        $where_values = array('s',$currency,'s',$fund,'s',$end_month);
        $query_result2 = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
        while ($row2 = mysqli_fetch_assoc($query_result2))
        {
            // Add transaction amount to fund balance
            $balance = add_money($balance,subtract_money($row2['credit_amount'],$row2['debit_amount']));
            $superfund_balance = add_money($superfund_balance,subtract_money($row2['credit_amount'],$row2['debit_amount']));
        }
        $where_clause = "fund=? AND category<>'-transfer-' AND acct_month<=?";
        $where_values = array('s',$fund,'s',$end_month);
        $query_result2 = mysqli_select_query($db,'splits','*',$where_clause,$where_values,'');
        while ($row2 = mysqli_fetch_assoc($query_result2))
        {
            $where_clause = 'currency=? AND account=? AND seq_no=? AND acct_month<=?';
            $where_values = array('s',$currency,'s',$row2['account'],'i',$row2['transact_seq_no'],'s',$end_month);
            $query_result3 = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
            if (($row3 = mysqli_fetch_assoc($query_result3)) && ($row3['sched_freq'] == '#'))
            {
                // Add split amount to fund balance
                $balance = add_money($balance,subtract_money($row2['credit_amount'],$row2['debit_amount']));
                $superfund_balance = add_money($superfund_balance,subtract_money($row2['credit_amount'],$row2['debit_amount']));
            }
        }
        // Print the line
        print_global_fund_balance_line($table_cell_style,$table_cell_style_ra,$fund,false,$balance,$end_month,$currency);
    }
    if ($last_superfund != '')
    {
        // Superfund total from last funds in list
        print_global_fund_balance_line($table_cell_style,$table_cell_style_ra,$last_superfund,true,$superfund_balance,$end_month,$currency);
    }
    print("</table>\n");
    print("<h2>&nbsp;</h2>\n");
}
else
{
    print("<h1>Fund Balances</h1>\n");
}

if (!isset($off_screen))
{
    // Generate form to input balance date
    print("<form method=\"post\">\n");
    print("<table cellpadding=\"5\">\n");
    print("<tr><td><input type=\"radio\" name=\"balance_date\" value=\"now\" checked></td><td>Now</td></tr>\n");
    print("<tr><td><input type=\"radio\" name=\"balance_date\" value=\"last_month\"></td><td>Last month</td></tr>\n");
    print("<tr><td><input type=\"radio\" name=\"balance_date\" value=\"last_year\"></td><td>Last year</td></tr>\n");
    print("<tr><td><input type=\"radio\" name=\"balance_date\" value=\"select\"></td><td>Select</td></tr>\n");
    print("</table>\n");
    print("<table cellpadding=\"5\">\n");
    print("<tr><td>Month:</td>\n");
    print("<td><select name=\"end_month\">\n");
    print("<option value=\"now\" selected>Now</option>\n");
    for ($month = 1; $month <= 12; $month++)
    {
        $month_name = monthName($month);
        print("<option value=\"$month\">$month_name</option>\n");
    }
    print("</select></td>\n");
    print("<td><select name=\"end_year\">\n");
    print("<option value=\"now\" selected>Now</option>\n");
    $this_year = (int)date('Y');
    for ($year = START_YEAR; $year <= $this_year; $year++)
    {
        print("<option value=\"$year\">$year</option>\n");
    }
    print("</select></td></tr>\n");
    print("<tr><td>Currency:</td>\n");
    print("<td colspan=2><select name=\"currency\">\n");
    $add_clause = 'ORDER BY id ASC';
    $query_result = mysqli_select_query($db,'currencies','*','',array(),$add_clause);
    while($row = mysqli_fetch_assoc($query_result))
    {
        $id = $row['id'];
        print("<option value=\"$id\"");
        if ($id == 'GBP')
        {
            print(" SELECTED");
        }
        print(">$id</option>\n");
    }
    print("</select></td>\n");
    print("<tr><td>&nbsp;</td><td colspan=\"2\"><input type=\"submit\" name=\"submitted\" value=\"Show\"></td></tr>\n");
  
    print("</table>\n");
    print("</form>\n");
}

//==============================================================================
?>
