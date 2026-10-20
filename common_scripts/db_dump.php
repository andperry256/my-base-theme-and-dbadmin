<?php
//==============================================================================

$sub_path = $_GET['sub_path'] ?? '';
$table = $_GET['table'] ?? '';
$db_passwd = $_POST['db_passwd'] ?? '';
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
require(__DIR__.'/mysql_maint_funct.php');
if ($db_passwd != $real_db_passwd) {

    // Error message must start with 'ERROR' for checking by calling script.
    exit ("ERROR - Authentication failure\n");
}
$options = [];
foreach (['full','struct_only','save_local'] as $option) {
    if (!empty($_GET[$option])) {
        $options[$option] = true;
    }
}
if (!empty($sub_path)) {
    if (!empty($table)) {
        exit(dump_db_table($local_site_dir,$sub_path,$table,$options));
    }
    else {
        exit(dump_database($local_site_dir,$sub_path,$options));
    }
}

//==============================================================================
