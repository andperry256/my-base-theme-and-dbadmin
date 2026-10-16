<?php
//==============================================================================

$sub_path = $_GET['sub_path'] ?? '';
$db_passwd = $_POST['db_passwd'] ?? '';
$sql_script = $_POST['sql_script'] ?? '';
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
require(__DIR__.'/mysql_maint_funct.php');
if ($db_passwd != $real_db_passwd) {
    exit ("Authentication failure\n");
}
if (!empty($sub_path)) {
    update_database($local_site_dir,$sub_path,$sql_script);
}

//==============================================================================
