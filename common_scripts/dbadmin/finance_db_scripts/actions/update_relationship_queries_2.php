<?php
//==============================================================================

global $db_admin_dir,$custom_pages_path,$relative_path;
$db = admin_db_connect();
print("<h1>Update Relationship Queries</h1>\n");
$sql_scripts = file("$db_admin_dir/finance_db_scripts/relationships.sql");
mysqli_delete_query($db,'dba_relationships','1',[]);
$count = 0;
foreach ($sql_scripts as $line) {
    if (mysqli_query_normal($db,$line)) {
        $count++;
    }
}
if (is_file("$custom_pages_path/$relative_path/relationships.sql")) {
    $sql_scripts = file("$custom_pages_path/$relative_path/relationships.sql");
    foreach ($sql_scripts as $line) {
        if ((substr($line,0,1) != '#') && (mysqli_query_normal($db,$line))) {
            $count++;
        }
    }
}
print("<p>$count Entries added to relationships table</p>\n");

//==============================================================================
?>
