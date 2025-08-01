<?php
//==============================================================================

$db = admin_db_connect();
global $presets;
$params = [];
$presets = [];
if (substr($table,0,14) == '_view_account_') {
    // Cause the account field to be preset on creating a new record
    $account = substr($table,14);
    $presets['account'] = $account;
    $where_clause = 'label=?';
    $where_values = ['s',$account];
    $query_result = mysqli_select_query($db,'accounts','*',$where_clause,$where_values,'');
    $presets['date'] = date('Y-m-d');
    if ($row = mysqli_fetch_assoc($query_result)) {
        $presets['currency'] = $row['currency'];
    }
    $params['presets'] = encode_record_id($presets);
    $params['additional_links'] = '';
    if (get_table_access_level('transactions') != 'read-only') {
        $params['additional_links'] .= "<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$base_url/$relative_path/?-action=reconcile_account&-account=$account\">Reconcile</a></div>\n";
    }
}
handle_record('new',$params)

//==============================================================================
?>
