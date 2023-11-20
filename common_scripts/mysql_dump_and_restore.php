<?php
//==============================================================================

function dump_db_table($dbid,$table)
{
    global $dbinfo;
    $db = db_connect($dbid);

    if (mysqli_num_rows(mysqli_query_strict($db,"SELECT * FROM $table")) == 0)
    {
        return '';
    }
    // Compile list of field types (numeric/string)
    $field_types = array();
    $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM $table");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $field_types[count($field_types)] = query_field_type($db,$table,$row['Field']);
    }
    
    $sql_script = "--\n-- Table structure for table `$table`\n--\n";
    $sql_script .= "DROP TABLE IF EXISTS `$table`;\n";
    $row = mysqli_fetch_row(mysqli_query($db,"SHOW CREATE TABLE $table"));
    $create_statement = $row[1];
    $charset = $dbinfo[$dbid][2];
    if (!empty($charset))
    {
        $create_statement = preg_replace('/CHARSET=[A-Za-z0-9]* /',"CHARSET=$charset ",$create_statement);
        $create_statement = preg_replace('/COLLATE=[A-Za-z0-9_]*/',"COLLATE=$charset".'_general_ci',$create_statement);
    }
    $sql_script .= "$create_statement;\n"; 
    $sql_script .= "--\n-- Dumping data for table `$table`\n--\n";
    $sql_script .= "LOCK TABLES `$table` WRITE;\n";
    $sql_script .= "/*!40000 ALTER TABLE `$table` DISABLE KEYS */;\n";
    
    $query_result = mysqli_query($db,"SELECT * FROM $table");
    $column_count = mysqli_num_fields($query_result);
    $sql_script .= "INSERT INTO `$table` VALUES\n";

    // Loop through table records
    while ($row = mysqli_fetch_row($query_result))
    {
        $sql_script .= '(';

        // Loop through record fields
        for ($i=0; $i<$column_count; $i++)
        {
            if ($row[$i] === null)
            {
                // Null
                $sql_script .= 'NULL';
            }
            elseif (($field_types[$i] == 'i') || ($field_types[$i] == 'd'))
            {
                // Number
                $sql_script .= "{$row[$i]}";
            }
            elseif (empty($row[$i]))
            {
                // Empty string
                $sql_script .= "''";
            }
            else
            {
                // Non-empty string
                $sql_script .= "'".mysqli_real_escape_string($db,$row[$i])."'";
            }
            $sql_script .= ',';
        }
        $sql_script = rtrim($sql_script,',');  // Remove comma from after last field
        $sql_script .= "),\n";
    }
    $sql_script = rtrim($sql_script,",\n");  // Remove comma from after last record
    $sql_script .= ";\n";
    $sql_script .= "/*!40000 ALTER TABLE `$table` ENABLE KEYS */;\n";
    $sql_script .= "UNLOCK TABLES;\n";
    return $sql_script;
}

//==============================================================================

function mysql_db_dump($dbid,$full_backup=false)
{
    global $dbinfo, $local_site_dir, $location, $site_mysql_backup_dir;
    $dbname = ($location == 'local')
    ? $dbinfo[$dbid][0]
    : $dbinfo[$dbid][1];
    $db_subpath = $dbinfo[$dbid][3];
    if (!is_dir("$site_mysql_backup_dir/$dbname"))
    {
        mkdir("$site_mysql_backup_dir/$dbname",0775);
    }
    $db = db_connect($dbid);

    // Compile lists of normal tables and noSync tables
    $normal_tables = array();
    $nosync_tables = array();
    $nosync_table_list = file_get_contents("https://remote.andperry.com/nosync_tables.php?dbname=$dbname");
    $query_result = mysqli_query($db,"SHOW FULL TABLES FROM `$dbname` WHERE Table_type='BASE TABLE'");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        if ((!$full_backup) && (strpos($nosync_table_list,"^{$row["Tables_in_$dbname"]}^") !== false))
        {
            $nosync_tables[count($nosync_tables)] = $row["Tables_in_$dbname"];
        }
        else
        {
            $normal_tables[count($normal_tables)] = $row["Tables_in_$dbname"];
        }
    }
    asort($normal_tables);
    asort($nosync_tables);

    // Run main dump
    $backup_filename = ($full_backup) ? 'db1s' : 'db1';
    $ofp = fopen("$site_mysql_backup_dir/$dbname/$backup_filename.sql",'w');
    foreach ($normal_tables as $table)
    {
        fprintf($ofp,str_replace('%','%%',dump_db_table($dbid,$table)));
    }
    fclose($ofp);
    print("Main database dump created for [$dbname]<br />\n");

    // Dump any noSync tables
    foreach ($nosync_tables as $table)
    {
        $ofp = fopen("$site_mysql_backup_dir/$dbname/table_$table.sql",'w');
        fprintf($ofp,str_replace('%','%%',dump_db_table($dbid,$table)));
        fclose($ofp);
        print("NoSync table dump created for [$table] on [$dbname]<br />\n");
    }
}

//==============================================================================

function mysql_db_restore($dbid,$full_restore=false)
{
    global $dbinfo, $local_site_dir, $location, $site_mysql_backup_dir;
    $dbname = ($location == 'local')
        ? $dbinfo[$dbid][0]
        : $dbinfo[$dbid][1];
    $db_subpath = $dbinfo[$dbid][3];
    if (is_file("$site_mysql_backup_dir/$dbname/db1.sql"))
    {
        $db = db_connect($dbid);

        // Preform main restore
        $sql_script = file_get_contents("$site_mysql_backup_dir/$dbname/db1.sql");
        mysqli_multi_query($db,$sql_script);
        print("Database restored for [$dbname]<br />\n");

        // Restore noSync tables if option selected
        if ($full_restore)
        {
            $dirlist = scandir("$site_mysql_backup_dir/$dbname");
            foreach ($dirlist as $file)
            {
                if (preg_match('/^table_[A-Za-z0-9_-]*\.sql$/',$file))
                {
                    $table = substr(pathinfo($file,PATHINFO_FILENAME),6);
                    $sql_script = file_get_contents("$site_mysql_backup_dir/$dbname/$file");
                    mysqli_multi_query($db,$sql_script);
                    print("NoSync table restored for [$table] on [$dbname]<br />\n");
                }
            }
        }
    }
    else
    {
        print("Unable to locate db1.sql script for [$dbname]<br />\n");
    }
}

//==============================================================================
// Main entry point
//==============================================================================

require("allowed_hosts.php");
if ((is_file('/Config/linux_pathdefs.php')) && (!isset($_GET['site'])))
{
    exit("Site not specified");
}
elseif (isset($_GET['site']))
{
    $local_site_dir = $_GET['site'];
}
if (is_file("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php"))
{
    require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
}
else
{
    exit("Path definitions script not found");
}
if ((!isset($allowed_hosts[$_SERVER['REMOTE_ADDR']])) && (!is_local_ip($_SERVER['REMOTE_ADDR'])))
{
    exit("Authentication failure");
}
elseif (!isset($local_site_dir))
{
    exit("Site not specified");
}
elseif (!isset($_GET['action']))
{
    exit("Action not specified");
}
elseif (!isset($_GET['dbname']))
{
    exit("Database not specified");
}

foreach ($dbinfo as $dbid => $info)
{
    if (($info[0] == $_GET['dbname']) || ($info[1] == $_GET['dbname']))
    {
        switch ($_GET['action'])
        {
            case 'dump':
                $full_backup = (isset($_GET['full']));
                mysql_db_dump($dbid,$full_backup);
                exit;

            case 'restore':
                $full_restore = (isset($_GET['full']));
                mysql_db_restore($dbid,$full_restore);
                exit;

            default:
                exit("Invalid action");
        }
    }
}
exit("Database [{$_GET['dbname']}] not found for site [$local_site_dir]\n");

//==============================================================================
?>