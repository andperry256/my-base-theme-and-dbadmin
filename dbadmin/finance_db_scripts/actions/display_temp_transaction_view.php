<?php
//==============================================================================

$account = $_GET['account'];
$payee = addslashes(urldecode($_GET['payee']));
$fund = urldecode($_GET['fund']);
$category = urldecode($_GET['category']);
$start_month = $_GET['start_month'];
$end_month = $_GET['end_month'];
set_temp_view_name();
create_view_structure("{$_SESSION['TEMP_VIEW']}_transactions",'transactions',"account LIKE '$account' AND payee LIKE '$payee' AND fund LIKE '$fund' AND category LIKE '$category' AND acct_month>='$start_month' AND acct_month<='$end_month' ORDER BY date ASC, account ASC, seq_no ASC");
exit("Needs fixing!!");
header("Location: index.php?-table={$_SESSION['TEMP_VIEW']}_transactions");

//==============================================================================
?>
