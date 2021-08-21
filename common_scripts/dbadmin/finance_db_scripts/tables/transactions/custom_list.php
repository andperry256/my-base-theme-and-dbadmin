<?php
//==============================================================================

$db = admin_db_connect();
$params = array();
$account = $table;
if (substr($account,0,14) == '_view_account_')
{
  $account = substr($account,14);
}
$params['additional_links'] = "<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-action=go_to_date&-table=$table&account=$account\">Go&nbsp;to&nbsp;Date</a></div>\n";
if (get_table_access_level('transactions') != 'read-only')
{
  $params['additional_links'] .= "<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-action=reconcile_account&-account=$account\">Reconcile</a></div>\n";
}

$query_result = mysqli_query($db,"SELECT * FROM accounts WHERE label='$account'");
if ($row = mysqli_fetch_assoc($query_result))
{
  print("<h1>{$row['name']}</h1>\n");
}
display_table($params);

//==============================================================================
?>
