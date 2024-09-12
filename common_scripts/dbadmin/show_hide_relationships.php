<?php
//==============================================================================

// Load paths and validate parameters.
if (is_file('/Config/linux_pathdefs.php'))
{
    $local_site_dir = strtok(substr($_SERVER['REQUEST_URI'],1),'/');
}
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
require("$base_dir/common_scripts/session_funct.php");
run_session();
$sub_path = $_GET['sub_path'];
if (empty($_GET['sub_path']))
{
    exit("Sub-path not specified");
}
$table = $_GET['table'];
if (empty($_GET['table']))

{
    exit("Table not specified");
}
$option = $_GET['option'];
if (empty($_GET['option']))
{
    exit("Option not specified");
}

// Carry our action to show/hide relationships.
if ($option == 'Show')
{
    update_session_var("$sub_path-$table-show-relationships",'*');
}
elseif ($option == 'Hide')
{
    update_session_var("$sub_path-$table-show-relationships",'');
}

update_session_var("$sub_path-$table-sort-clause",$sort_clause);
header ("Location: $base_url/dbadmin/$sub_path?-table=$table");
exit;

//==============================================================================
?>