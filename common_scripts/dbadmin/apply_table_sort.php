<?php
//==============================================================================

// Load paths and validate parameters
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
if (empty($_GET['table']))
{
    exit("Table not specified");
}
$table = $_GET['table'];
if (empty($_GET['field']))

{
    exit("Field not specified");
}
$field = $_GET['field'];
$sort_level = (int)get_session_var("$sub_path-$table-sort-level");
$next_sort_order = array ( 'ASC'=>'DESC', 'DESC'=>'NONE', 'NONE'=>'ASC');

// Extract any existing sort settings.
$sort_field = array();
$sort_order = array();
$field_sort_level = 0;
for ($i=1; $i<=$sort_level; $i++)
{
    $sort_field[$i] = get_session_var("$sub_path-$table-sort-field-$i");
    $sort_order[$i] = get_session_var("$sub_path-$table-sort-order-$i");
    if ($sort_field[$i] == $field)
    {
        // Field is already sorted.
        $field_sort_level = $i;
    }
}

if ($field_sort_level == 0)
{
    // Add field as next level sort.
    $sort_level++;
    update_session_var("$sub_path-$table-sort-level",$sort_level);
    $sort_field[$sort_level] = $field;
    update_session_var("$sub_path-$table-sort-field-$sort_level",$field);
    $sort_order[$sort_level] = 'ASC';
    update_session_var("$sub_path-$table-sort-order-$sort_level",'ASC');
}
else
{
    // Update existing sort order for field.
    $new_sort_order = $next_sort_order[$sort_order[$field_sort_level]];
    $sort_order[$field_sort_level] = $new_sort_order;
    update_session_var("$sub_path-$table-sort-order-$field_sort_level",$new_sort_order);
    if ($new_sort_order == 'NONE')
    {
        // Remove sorting for this field. Any lower level sorting will be affected.
        $sort_level = $field_sort_level-1;
        update_session_var("$sub_path-$table-sort-level",$sort_level);
    }
}

// Output summary and link to continue.
if ($sort_level == 0)
{
    $sort_clause = '';
}
else
{
    $sort_clause = 'ORDER BY ';
    for ($i=1; $i<=$sort_level; $i++)
    {
        $sort_clause .= "{$sort_field[$i]} {$sort_order[$i]},";
    }
    $sort_clause = rtrim($sort_clause,',');
}
update_session_var("$sub_path-$table-sort-clause",$sort_clause);
header ("Location: $base_url/dbadmin/$sub_path?-table=$table");
exit;

//==============================================================================
?>