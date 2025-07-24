<?php
//==============================================================================

$local_site_dir = $_POST['site'];
$account = $_POST['account'];

require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
require("$base_dir/mysql_connect.php");
require("$base_dir/common_scripts/date_funct.php");
require("$db_admin_dir/common_funct.php");
require("$custom_pages_path/$relative_path/db_funct.php");
require("finance_funct.php");
$db = finance_db_connect();

foreach ($_POST as $key => $dummy)
{
    if (substr($key,0,4) == 'chk_')
    {
        $bank_rec_id = (int)substr($key,4);
        $where_clause = 'rec_id=?';
        $where_values = ['i',$bank_rec_id];
        if ($row = mysqli_fetch_assoc(mysqli_select_query($db,'bank_import','*',$where_clause,$where_values,'')))
        {
            $match = find_matching_transaction($account,$row['date'],$row['amount']);
            if ($match > 0)
            {
                $where_clause = 'account=? AND seq_no=?';
                $where_values = ['s',$account,'i',$match];
                if ($row2 = mysqli_fetch_assoc(mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'')))
                {
                    // Mark the records as reconciled
                    $set_fields = 'reconciled';
                    $set_values = ['i',1];
                    $where_clause = 'rec_id=?';
                    $where_values = ['i',$bank_rec_id];
                    mysqli_update_query($db,'bank_import',$set_fields,$set_values,$where_clause,$where_values);
                    $set_fields = 'reconciled';
                    $set_values = ['i',1];
                    $where_clause = 'account=? AND seq_no=?';
                    $where_values = ['s',$account,'i',$match];
                    mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
                }
            }
            else
            {
                // This should not occur
            }
        }
    }
}
header("Location: $base_url/$relative_path/?-action=reconcile_account&-account=$account&message=$message");
exit;

//==============================================================================
?>
