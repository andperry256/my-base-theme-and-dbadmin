<?php
//==============================================================================

$account = $_GET['account'];
$fund = urldecode($_GET['fund']);
$category = urldecode($_GET['category']);
$start_month = $_GET['start_month'];
$end_month = $_GET['end_month'];
set_temp_view_name();
create_view_structure(get_session_var('TEMP_VIEW')."_splits",'splits',"account LIKE '$account' AND fund LIKE '$fund' AND category LIKE '$category' AND acct_month>='$start_month' AND acct_month<='$end_month' ORDER BY account ASC, transact_seq_no ASC");
exit("Needs fixing!!");
header("Location: index.php?-table=".get_session_var('TEMP_VIEW')."_splits");

//==============================================================================
?>
