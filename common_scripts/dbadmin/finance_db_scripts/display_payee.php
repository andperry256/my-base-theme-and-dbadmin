<?php
//==============================================================================

// Variables $local_site_dir and $RelativePath must be set up beforehand
$payee = $_GET['payee'];
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
header("Location: $BaseURL/$RelativePath/?-action=display_transaction_report&payee=$payee");
exit;

//==============================================================================
?>
