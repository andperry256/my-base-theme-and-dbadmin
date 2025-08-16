<?php
//==============================================================================

$db = admin_db_connect();
$params = [];
$mode = get_viewing_mode();
if ($mode == 'mobile') {
    $where_clause = "table_name='transactions'";
    $row = mysqli_fetch_assoc(mysqli_select_query($db,'dba_table_info','grid_columns',$where_clause,[],''));
    $set_fields = 'grid_columns';
    $set_values = ['s',$row['grid_columns']];
    $where_clause = "parent_table='transactions' OR parent_table LIKE '_view_transactions%'";
    $where_values = [];
    mysqli_update_query($db,'dba_table_info',$set_fields,$set_values,$where_clause,$where_values);
}
$account = $table;
if (substr($account,0,14) == '_view_account_') {
    $account = substr($account,14);
}
$params['additional_links'] = "<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$base_url/$relative_path/?-action=go_to_date&-table=$table&account=$account\">Go&nbsp;to&nbsp;Date</a></div>\n";
if (get_table_access_level('transactions') != 'read-only') {
    $params['additional_links'] .= "<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$base_url/$relative_path/?-action=reconcile_account&-account=$account\">Reconcile</a></div>\n";
}

$where_clause = 'label=?';
$where_values = ['s',$account];
$query_result = mysqli_select_query($db,'accounts','*',$where_clause,$where_values,'');
if ($row = mysqli_fetch_assoc($query_result)) {
    print("<h1>{$row['name']}</h1>\n");
}
display_table($params);

//==============================================================================
