<script>
    function selectTransaction(account,dropdown)
    {
        var option_value = dropdown.options[dropdown.selectedIndex].value;
        location.href = './?-action=reconcile_account&-account=' + account +'&selection=' + encodeURIComponent(option_value);
    }
</script>
<?php
//==============================================================================

global $base_url,$relative_path;
global $local_site_dir;
global $bank_import_dir;
global $custom_pages_url;

$db = admin_db_connect();

$account = $_GET['-account'];
$where_clause = 'label=?';
$where_values = array('s',$account);
$query_result = mysqli_select_query($db,'accounts','*',$where_clause,$where_values,'');
if ($row = mysqli_fetch_assoc($query_result))
{
    $account_type = $row['type'];
    $account_name = $row['name'];
}
else
{
    $account_name = '';  // This should not occur
}

if (isset($_GET['selection']))
{
    // Save details of selected bank transaction
    $selected_rec_id = strtok($_GET['selection'],'^');
    $selected_date = strtok('^');
    $selected_amount = strtok('^');
    $match = (!empty($selected_amount))
        ? find_matching_transaction($account,$selected_date,$selected_amount)
        : null;
}

print("<h1>Reconcile Account ($account_name)</h1>\n");
print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$base_url/$relative_path/?-table=_view_account_$account\">All&nbsp;Transactions</a></div>");
print("<div style=\"clear:both\"></div>\n");

if ((isset($_GET['message'])) && (!empty($_GET['message'])))
{
    print($_GET['message']);
}

print("<form method=\"post\" action=\"$custom_pages_url/$relative_path/reconcile_account_action.php\">\n");
print("<table cellpadding=\"10\">\n");

// Build select list for bank transactions
print("<tr><td>Bank Transaction:</td><td>\n");
print("<select name=\"bank_transaction\" onchange=\"selectTransaction('$account',this)\">\n");
print("<option value=\"null\">Please select ...</option>\n");

$dirlist = scandir($bank_import_dir);
$csvlist = array();
foreach ($dirlist as $file)
{
    if ((preg_match("/^Account_$account/",$file)) && (pathinfo($file,PATHINFO_EXTENSION) == 'csv'))
    {
        $statement_date = substr(pathinfo($file,PATHINFO_FILENAME),strlen($account)+9);
        $csvlist["#$statement_date"] = true;
        krsort($csvlist);
    }
}
$count = 0;
foreach ($csvlist as $key => $value)
{
    if ($key == '#')
    {
        // Single (continuous) CSV file
        print("<option value=\"IMPORT\"");
        if ((isset($_GET['selection'])) && ($_GET['selection'] == "IMPORT"))
        {
            print(" selected");
        }
        print(">Re-Import CSV</option>\n");
    }
    else
    {
        // Separate (dated) CSV files
        $date = substr($key,1);
        print("<option value=\"IMPORT-$date\"");
        if ((isset($_GET['selection'])) && ($_GET['selection'] == "IMPORT-$date"))
        {
            print(" selected");
        }
        print(">Re-Import CSV [$date]</option>\n");
    }
    if (++$count >= 4)
    {
        break;
    }
}
print("<option value=\"BULK\"");
if ((isset($_GET['selection'])) && ($_GET['selection'] == "BULK"))
{
    print(" selected");
}
print(">Bulk Reconcile</option>\n");

$where_clause = 'reconciled=0';
$add_clause = 'ORDER BY rec_id DESC';
$query_result = mysqli_select_query($db,'bank_import','*',$where_clause,array(),$add_clause);
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
    $value = "{$row['rec_id']}^{$row['date']}^{$row['amount']}^{$row['balance']}";
    print("<option value=\"$value\"");
    if ((isset($_GET['selection'])) && ($_GET['selection'] == $value))
    {
        print(" selected");
    }
    print(">$text</option>\n");
}
print("</select>\n");
print("<br /><span class=\"small\">(N.B. To re-import CSV file, select this option on BOTH lists)</span></td></tr>\n");

// Build select list for account transactions
print("<tr><td>Account Transaction:</td><td>\n");
print("<select name=\"account_transaction\">\n");
print("<option value=\"null\">Please select ...</option>\n");
print("<option value=\"IMPORT\">Re-Import CSV</option>\n");
print("<option value=\"NONE\">Discard Bank Transaction</option>\n");
print("<option value=\"NEW\"");
if ((isset($_GET['selection'])) && ($match == 0))
{
    // No match on selected transation
    print(" selected");
}
print(">Create New Transaction</option>\n");
$where_clause = 'reconciled=0';
$add_clause = 'ORDER BY payee ASC,date ASC,seq_no ASC';
$query_result = mysqli_select_query($db,"_view_account_$account",'*',$where_clause,array(),$add_clause);
$match_count = 0;
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
    print("<option value=\"{$row['seq_no']}[$amount]\"");
    if (isset($_GET['selection']))
    {
        if ((!empty($selected_date)) && (!empty($selected_amount)) && ($match == $row['seq_no']))
        {
            // Unique match
            print(" selected");
            $match_count = 1;
        }
        elseif (substr($_GET['selection'],0,6) == 'IMPORT' )
        {
            $match_count = 0;
        }
        else
        {
            // Multiple match or no match
            $match_count = -$match;
        }
    }
    print(">$text</option>\n");
}
print("</select>\n");
if ($match_count >= 2)
{
    print("<br /><span style=\"font-weight:bold;color:orange\">WARNING</span> - There are <strong>$match_count</strong> potentially matching transactions.\n");
}
print("</td></tr>\n");

print("<tr><td>Auto-adjust:<br />&nbsp;</td><td><input type=\"checkbox\" name=\"auto_adjust\"><br />(Tick to automatically adjust the register value to match the bank transaction)</td></tr>\n");
print("<tr><td>Update schedule:<br />&nbsp;</td><td><input type=\"checkbox\" name=\"update_schedule\"><br />(Tick to automatically adjust both the register value and the associated scheduled transaction to match the bank transaction)</td></tr>\n");
print("<tr><td></td><td><input type=\"submit\" name=\"submitted\" value=\"Reconcile\"</td></tr>\n");
print("</table>\n");
print("<input type=\"hidden\" name=\"account\" value=\"$account\">\n");
print("<input type=\"hidden\" name=\"account_type\" value=\"$account_type\">\n");
print("<input type=\"hidden\" name=\"site\" value=\"$local_site_dir\">\n");
print("<input type=\"hidden\" name=\"relpath\" value=\"$relative_path\">\n");
print("</form>\n");

//==============================================================================
?>
