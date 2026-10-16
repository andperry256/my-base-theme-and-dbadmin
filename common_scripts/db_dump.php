<?php
//==============================================================================

$sub_path = $_GET['sub_path'] ?? '';
$table = $_GET['table'] ?? '';
$full = (!empty($_GET['full']));
$struct_only = (!empty($_GET['struct_only']));
$db_passwd = $_POST['db_passwd'] ?? '';
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
require(__DIR__.'/mysql_maint_funct.php');
if ($db_passwd != $real_db_passwd) {
    exit ("Authentication failure\n");
}
$options = [];
if (!empty($sub_path)) {
    if (!empty($table)) {
        if ($struct_only) {
            $options['struct_only'] = true;
        }
        exit(dump_db_table($local_site_dir,$sub_path,$table,$options));
    }
    else {
        if ($full) {
            $options['full'] = true;
        }
        exit(dump_database($local_site_dir,$sub_path,$options));
    }
}

//==============================================================================
