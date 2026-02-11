<?php
//==============================================================================

$local_site_dir = $_POST['site'];
$account = $_POST['account'];
$account_label = $_POST['account_label'];
$account_type = $_POST['account_type'];
$relative_path = $_POST['relpath'];
$bank_transaction = $_POST['bank_transaction'];
$account_transaction = $_POST['account_transaction'];
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
require("$base_dir/mysql_connect.php");
require("$base_dir/common_scripts/date_funct.php");
require("$db_admin_dir/common_funct.php");
require("$db_admin_dir/view_funct.php");
require("$custom_pages_path/$relative_path/db_funct.php");
require("finance_funct.php");
$db = finance_db_connect();

$bank_rec_id = strtok($bank_transaction,'^');
$bank_date = strtok('^');
$bank_amount = (float)strtok('^');
$bank_balance = strtok('^');
$user_message = '';
if ($bank_amount >= 0) {
    $credit_amount = $bank_amount;
    $debit_amount = 0;
}
else {
    $debit_amount = -$bank_amount;
    $credit_amount = 0;
}
$account_seq_no = strtok($account_transaction,'[');
$account_amount = strtok(']');
$import_table = "_ctab_bank_import_$account_label";

// ==================== Transaction Import ====================

$source_file = basename($_FILES['import_file']['name']);
if ((!empty($source_file)) && (substr($source_file,0,strlen($account_label)) == $account_label)) {

    // Add to bank import table from CSV file
    if (strtolower(pathinfo($source_file,PATHINFO_EXTENSION)) == 'csv') {
        $target_file = "$base_dir/admin_data/finances/bank_import_$account_label.csv";
        if (move_uploaded_file($_FILES['import_file']['tmp_name'], $target_file)) {

            // Load CSV into array, remove header line and sort into ascending chronological order.
            $import_data = file($target_file);
            unset($import_data[0]);
            krsort($import_data);

            foreach ($import_data as $line) {

                // Extract fields from line.
                $raw_data = trim($line);
                $line_elements = str_getcsv($line,',','"');
                $date = $line_elements[0];
                $day = substr($date,0,2);
                $month = substr($date,3,2);
                $year = substr($date,6,4);
                $mysql_date = "$year-$month-$day";
                if ($account_type == 'bank') {
                    $description = $line_elements[4];
                    $debit_amount = $line_elements[5];
                    $credit_amount = $line_elements[6];
                    if ((is_numeric($debit_amount)) && (!empty($debit_amount))) {
                        $amount = -$debit_amount;
                    }
                    elseif ((is_numeric($credit_amount)) && (!empty($credit_amount))) {
                        $amount = $credit_amount;
                    }
                    else {
                        // This should not occur
                        $amount = 0;
                    }
                    $balance = $line_elements[7];
                }
                elseif ($account_type == 'credit-card') {
                    $description = $line_elements[3];
                    $amount = $line_elements[4];
                    $amount = (is_numeric($amount)) ? - $amount : 0;
                    $balance = 0;
                }
                $description = substr($description,0,31);

                //Add record to import table if not already present.
                $fields = 'raw_data,date,description,amount,balance';
                $values = ['s',$raw_data,'s',$mysql_date,'s',$description,'d',$amount,'d',$balance];
                $where_clause = 'raw_data=?';
                $where_values = ['s',$raw_data];
                mysqli_conditional_insert_query($db,$import_table,$fields,$values,$where_clause,$where_values);
            }
        }
    }
}

// ==================== Bulk Reconcile ====================

elseif ($bank_transaction == 'BULK') {
    // Load action screen for bulk reconcile.
    header("Location: $base_url/$relative_path/?-action=bulk_reconcile&account=$account");
    exit;
}

// ==================== Discard Bank Transaction ====================

elseif ($account_transaction == 'NONE') {
    // Bank transaction not to be matched
    $set_fields = 'reconciled';
    $set_values = ['i',1];
    $where_clause = 'rec_id=?';
    $where_values = ['i',$bank_rec_id];
    mysqli_update_query($db,$import_table,$set_fields,$set_values,$where_clause,$where_values);
    $user_message = "<p>Bank transaction discarded.</p>\n";
}

// ==================== New Transaction ====================

elseif ($account_transaction == 'NEW') {
    $where_clause = 'rec_id=?';
    $where_values = ['i',$bank_rec_id];
    $query_result = mysqli_select_query($db,$import_table,'*',$where_clause,$where_values,'');
    if ($row = mysqli_fetch_assoc($query_result)) {
        // Create new transaction. Update payee if regex match is found.
        // N.B. Multiple matches can be made against a given payee by creating additional table entries
        // using the original payee name with variable numbers of underscores added to the end.
        $date = $row['date'];
        $payee = $row['description'];
        $where_clause = "regex_match<>'^$'";
        $query_result2 = mysqli_select_query($db,'payees','*',$where_clause,[],'');
        while ($row2 = mysqli_fetch_assoc($query_result2)) {
            $pattern = "/{$row2['regex_match']}/i";
            if (preg_match($pattern,$payee)) {
                $payee = rtrim($row2['name'],'_');
                break;
            }
        }
        $seq_no = next_seq_no($account);
        $fields = 'account,seq_no,date,payee,credit_amount,debit_amount,bank_import_id';
        $values = ['s',$account,'i',$seq_no,'s',$date,'s',$payee,'d',$credit_amount,'d',$debit_amount,'i',$bank_rec_id];
        mysqli_insert_query($db,'transactions',$fields,$values);

        // Go to the record edit screen.
        $primary_keys = [];
        $primary_keys['account'] = $account;
        $primary_keys['seq_no'] = $seq_no;
        $record_id = encode_record_id($primary_keys);
        header("Location: $base_url/$relative_path/?-table=_view_account_$account&-action=edit&-recordid=$record_id");
        exit;
    }
}

// ==================== No Action ====================

elseif (!empty($source_file)) {
    $user_message = "<p>Invalid import file (name must begin with account label).</p>\n";
}
elseif (($bank_transaction == 'null') || ($account_transaction == 'null')) {
    $user_message = "<p>No action specified.</p>\n";
}

// ==================== Non-matching Amounts ====================

elseif ((round($bank_amount,2) != round($account_amount,2)) && (!isset($_POST['auto_adjust'])) && (!isset($_POST['update_schedule']))) {
    $user_message = "<p class=\"error-text\"><b>ERROR</b> - Attempt to reconcile non-matching amounts.</p>\n";
}

// ==================== Reconcile Transaction ====================

else {
    // Transaction to be reconciled
    $where_clause = 'rec_id=?';
    $where_values = ['i',$bank_rec_id];
    if ((is_numeric($bank_rec_id)) &&
        ($row = mysqli_fetch_assoc(mysqli_select_query($db,$import_table,'*',$where_clause,$where_values,'')))) {
        $set_fields = 'reconciled';
        $set_values = ['i',1];
        $where_clause = 'rec_id=?';
        $where_values = ['i',$bank_rec_id];
        mysqli_update_query($db,$import_table,$set_fields,$set_values,$where_clause,$where_values);
        $set_fields = 'reconciled';
        $set_values = ['i',1];
        $where_clause = 'seq_no=?';
        $where_values = ['i',$account_seq_no];
        mysqli_update_query($db,"_view_account_$account",$set_fields,$set_values,$where_clause,$where_values);
        if ((isset($_POST['auto_adjust'])) || (isset($_POST['update_schedule']))) {
            // Change register amount to match bank transaction
            $set_fields = 'credit_amount,debit_amount';
            $set_values = ['d',$credit_amount,'d',$debit_amount];
            $where_clause = 'seq_no=?';
            $where_values = ['i',$account_seq_no];
            mysqli_update_query($db,"_view_account_$account",$set_fields,$set_values,$where_clause,$where_values);
            if (isset($_POST['update_schedule'])) {
                // Update associated scheduled transaction
                $where_clause = 'seq_no=?';
                $where_values = ['i',$account_seq_no];
                $query_result2 = mysqli_select_query($db,"_view_account_$account",'*',$where_clause,$where_values,'');
                if ($row2 = mysqli_fetch_assoc($query_result2)) {
                    $payee = $row2['payee'];
                    $set_fields = 'credit_amount,debit_amount';
                    $set_values = ['d',$credit_amount,'d',$debit_amount];
                    $where_clause = "account=? AND payee=? AND sched_freq<>'#'";
                    $where_values = ['s',$account,'s',$payee];
                    mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
                }
            }
        }
        $where_clause = 'seq_no=?';
        $where_values = ['i',$account_seq_no];
        $query_result2 = mysqli_select_query($db,"_view_account_$account",'*',$where_clause,$where_values,'');
        if ($row2 = mysqli_fetch_assoc($query_result2)) {
            update_account_balances($account,$row2['date']);
        }
        $add_clause = 'ORDER BY date DESC, seq_no DESC LIMIT 1';
        $query_result2 = mysqli_select_query($db,"_view_account_$account",'*','',[],$add_clause);
        if ($row2 = mysqli_fetch_assoc($query_result2)) {
            $user_message = "<p>Transaction reconciled. Bank balance = $bank_balance. Register balance = {$row2['reconciled_balance']}</p>\n";
        }
    }
}

// ==================== End of Main Options ====================

// Reload main reconcile screen.
$message = urlencode($user_message);
header("Location: $base_url/$relative_path/?-action=reconcile_account&-account=$account&message=$message");
exit;

//==============================================================================
