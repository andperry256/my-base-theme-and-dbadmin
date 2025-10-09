<?php
//==============================================================================

// Variables $local_site_dir and $relative_path must be set up beforehand
$payee = $_GET['payee'];
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
$auto = (isset($_GET['auto'])) ? '&auto' : '';
header("Location: $base_url/$relative_path/?-action=display_transaction_report&payee=$payee$auto");
exit;

//==============================================================================
