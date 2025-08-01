<?php
//==============================================================================

$db = admin_db_connect();
if (!isset($record_id)) {
    exit("Record ID not specified - this should not occur");
}
$primary_keys = decode_record_id($record_id);
$account = $primary_keys['account'];
$transact_seq_no = $primary_keys['transact_seq_no'];

$primary_keys2 = [];
$primary_keys2['account'] = $account;
$primary_keys2['seq_no'] = $transact_seq_no;
$record_id2 = encode_record_id($primary_keys2);

$params = [];
$params['additional_links'] = "<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$base_url/$relative_path/?-action=edit&-table=_view_account_$account&-recordid=$record_id2&summary\">Parent&nbsp;Transaction</a></div>\n";
handle_record('edit',$params);

//==============================================================================
?>
