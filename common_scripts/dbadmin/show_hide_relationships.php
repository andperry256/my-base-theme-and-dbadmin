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
if (empty($_GET['sub_path']))
{
    exit("Sub-path not specified");
}
$sub_path = $_GET['sub_path'];
if (empty($_GET['option']))
{
    exit("Option not specified");
}
$option = $_GET['option'];
$table = get_session_var("$sub_path-filtered-table");
if (empty($table))
{
    exit("Table not specified");
}

// Carry our action to show/hide relationships.
if ($option == 'Show')
{
    update_session_var("$sub_path-show-relationships",true);
}
elseif ($option == 'Hide')
{
    update_session_var("$sub_path-show-relationships",false);
}

update_session_var("$sub_path-sort-clause",$sort_clause);
header ("Location: $base_url/dbadmin/$sub_path?-table=$table");
exit;

//==============================================================================
?>