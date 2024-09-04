<?php
//==============================================================================

// Load paths and validate parameters
if (is_file('/Config/linux_pathdefs.php'))
{
    $local_site_dir = strtok(substr($_SERVER['REQUEST_URI'],1),'/');
}
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
require("$base_dir/common_scripts/dbadmin/table_funct.php");
require("$base_dir/common_scripts/dbadmin/record_funct.php");
if (empty($_GET['dbname']))
{
    exit("Database not specified");
}
$dbname = $_GET['dbname'];
if (empty($_GET['table']))
{
    exit("Table not specified");
}
$table = $_GET['table'];
require("$base_dir/common_scripts/session_funct.php");
run_session();
if (empty(get_session_var(array('dbauth',$dbname))))
{
    exit("Authentication failure");
}
if (!is_file("$base_dir/non_wp_header.php"))
{
    exit("Non-WP header file not found");
}
require("$base_dir/non_wp_header.php");

//==============================================================================
?>
<style>
    a {
        color:#fff !important;
        text-decoration: none !important;
    }
    .table-header {
        background-color: #7f7f7f;
        color: #fff;
    }
</style>
<?php
//==============================================================================

// Connect to database
foreach ($dbinfo as $id => $data)
{
    if ((($location == 'local') && ($data[0] == $dbname)) ||
        (($location == 'real') && ($data[1] == $dbname)))
    {
        $db = db_connect($id);
        break;
    }
}
if (empty($db))
{
    exit("Unable to connect to database");
}

print("<h2>Table [$table]</h2>\n");

// Build lists of primary keys and fields to display
$base_table = get_base_table($table,$db);
$display_fields = array();
$pklist = '';
$query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM $table");
while ($row = mysqli_fetch_assoc($query_result))
{
    $where_clause = 'table_name=? AND field_name=?';
    $where_values = array('s',$base_table,'s',$row['Field']);
    if ($row2 = mysqli_fetch_assoc(mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'')))
    {
        if ($row2['is_primary'])
        {
            $pklist .= "{$row2['field_name']} ASC,";
        }
        if ($row2['list_desktop'] ==  1)
        {
            $display_fields[$row2['display_order']] = $row2['field_name'];
        }
    }
}
$excluded_fields = (isset($_GET['excluded'])) ? $_GET['excluded'] : '^';

// Output the table
$pklist = rtrim($pklist,',');
if (empty($pklist))
{
    exit("Table has no primary key(s)");
}

print("<table>\n");
print("<tr>");
foreach ($display_fields as $order => $field)
{
    if (strpos($excluded_fields,"^$field^") === false)
    {
        $label = field_label($base_table,$field,$db);
        $excluded_fields_par=(urlencode("$excluded_fields$field^"));
        print("<td class=\"table-header\">");
        print("<a href=\"./display_table.php?dbname=$dbname&table=$table&excluded=$excluded_fields_par\">$label</a></td>");
    }
}
print("</tr>\n");

$add_clause = "ORDER BY $pklist";
$query_result = mysqli_select_query($db,$table,'*','',array(),$add_clause);
while ($row = mysqli_fetch_assoc($query_result))
{
    print("<tr>");
    foreach ($display_fields as $order => $field)
    {
        if (strpos($excluded_fields,"^$field^") === false)
        {
            $where_clause = 'table_name=? AND field_name=?';
            $where_values = array('s',$base_table,'s',$field);
            if (($row2 = mysqli_fetch_assoc(mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,''))) && 
                ($row2['widget_type'] == 'checkbox'))
            {
                $value = ($row[$field]) ? '[X]' : '';
            }
            else
            {
                $value = $row[$field];
            }
            print("<td>$value</td>");        
        }
    }
    print("</tr>\n");
}

print("</table>\n");

//==============================================================================
?>
