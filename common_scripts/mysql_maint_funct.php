    <?php
//==============================================================================
if (!defined('MYSQL_MAINT_FUNCT_DEFINED')):
//==============================================================================
/*
Function generate_table_dump

This function generates an SQL script to regenerate a given table, much like
that in the output from the mysqldump command. The SQL script is returned as
a string.

The following parameters are passed:-

$db         - Link to the connected database

$table      - Name of the table

$options    - (optional) - Add the following array elements for options:
              'batch_size' => n     - where n is the number of rows to group
                                      together in an INSERT statement.
              'struct_only' => true - to create the dump for the table
                                      structure only (i.e. no data).

Return - Table dump SQL script.
*/
//==============================================================================

function generate_table_dump($db,$table,$options=[])
{
    $sql_script = "";
    $batch_size = $options['batch_size'] ?? 200;

    // Retrieve the table creation structure.
    try {
        $result = mysqli_query($db, "SHOW CREATE TABLE `$table`");
    }
    catch (Exception $e) {
        if (empty($result)) {
            return false;
        }
    }
    $row = mysqli_fetch_row($result);

    // Add DROP TABLE and CREATE TABLE statements.
    $sql_script .= "DROP TABLE IF EXISTS `$table`;\n";
    $sql_script .= $row[1].";\n\n";
    mysqli_free_result($result);
    if (!empty($options['struct_only'])) {
        return $sql_script;
    }

    // Retrieve all rows from the table.
    $result = mysqli_query($db, "SELECT * FROM `$table`");
    if (!$result) {
        return $sql_script;
    }

    // Loop through rows and construct INSERT statements.
    $num_fields = mysqli_num_fields($result);
    while ($row = mysqli_fetch_row($result)) {
        $row_string = "(";
        for ($i = 0; $i < $num_fields; $i++) {
            if (!isset($row[$i])) {$row_string .= "NULL";
            }
            else {
                $escaped_value = mysqli_real_escape_string($db, $row[$i]);
                $row_string .= "'" . $escaped_value . "'";
            }

            if ($i < ($num_fields - 1)) {$row_string .= ", ";
            }
        }
        $row_string .= ")";
        $rows[] =$row_string;

        // End of batch reached - output INSERT statement.
        if (count($rows) >= $batch_size) {
            $sql_script .= "INSERT INTO `$table` VALUES \n" . implode(", \n", $rows) . ";\n";
            $rows = []; // Clear the batch array
        }
    }

    // Output INSERT statement for any residual rows.
    if (!empty($rows)) {
        $sql_script .= "INSERT INTO `$table` VALUES \n" . implode(", \n", $rows) . ";\n";
    }

    mysqli_free_result($result);
    return $sql_script;
}

//==============================================================================
/*
Function dump_database

This function is called to back up a given database, given the site and the
database sub-path. The following parameters
are passed:

$local_site_dir - Site path as defined in the IT services database.

$db_sub_path    - Database sub-path as defined in the IT services database.

$options        - (optional) - Add the following array elements for options:
                  'full'       => true - to include nosync tables in dump
                  'save_local' => true - to save a copy of the dump in the local
                                         MySQL backup directory, as well as
                                         returning it as a result.

Return - Database dump SQL script.
*/
//==============================================================================

function dump_database($local_site_dir,$db_sub_path,$options=[])
{
    global $www_root_dir;
    if (!empty($www_root_dir)) {
        include("$www_root_dir/$local_site_dir/mysql_connect.php");
    }
    else {
        include(__DIR__.'/../mysql_connect.php');
    }
    $dbid = $sub_path_lookup[$db_sub_path];
    $db = db_connect($dbid);
    $db_its = itservices_db_connect();
    $dbname = (!empty($www_root_dir)) ? $dbinfo[$dbid][0] : $dbinfo[$dbid][1];
    $sql_script = '';

    // Compile list of nosync tables.
    $nosync_tables = [];
    if (empty($options['full'])) {
        $where_clause = 'site_path=? AND sub_path=?';
        $where_values = ['s',$local_site_dir,'s',$db_sub_path];
        $add_clause = 'ORDER BY table_name ASC';
        $query_result = mysqli_select_query($db_its,'nosync_tables','*',$where_clause,$where_values,$add_clause);
        while ($row = mysqli_fetch_assoc($query_result)) {
            $nosync_tables[] = $row['table_name'];
        }
    }

    // Set up main query and add any exclusions for nosync tables.
    $query = "SHOW FULL TABLES FROM `$dbname` WHERE Table_Type='BASE TABLE'";
    foreach ($nosync_tables as $table) {
        $query .= " AND `Tables_in_{$dbname}` <> '$table'";
    }

    // Loop through tables to be backed up, creating the SQL dump.
    $query_result = mysqli_query($db,$query);
    while ($row = mysqli_fetch_assoc($query_result)) {
        $sql_script .= generate_table_dump($db,$row["Tables_in_{$dbname}"])."\n";
    }

    // Save local copy of dump if required
    if (!empty($options['save_local'])) {
        include(__DIR__.'/common_pathdefs.php');
        if (!is_dir("$site_mysql_backup_dir/$dbname")) {
            mkdir("$site_mysql_backup_dir/$dbname,0775");
        }
        file_put_contents("$site_mysql_backup_dir/$dbname/db1.sql",$sql_script);
    }
    return $sql_script;
}

//==============================================================================
/*
Function dump_db_table

This function is called to back up a given database table, given the site and
the database sub-path. The following parameters are passed:

$local_site_dir - Site path as defined in the IT services database.

$db_sub_path    - Database sub-path as defined in the IT services database.

$table          - Name of the table.

$options        - (optional) - Add the following array elements for options:
                  'struct_only' => true - to create the dump for the table
                                          structure only (i.e. no data).
                  'save_local' => true  - to save a copy of the dump in the local
                                          MySQL backup directory, as well as
                                          returning it as a result.

Return - Table dump SQL script.
*/
//==============================================================================

function dump_db_table($local_site_dir,$db_sub_path,$table,$options=[])
{
    global $www_root_dir;
    if (!empty($www_root_dir)) {
        include("$www_root_dir/$local_site_dir/mysql_connect.php");
    }
    else {
        include(__DIR__.'/../mysql_connect.php');
    }
    $dbid = $sub_path_lookup[$db_sub_path];
    $db = db_connect($dbid);
    $dbname = (!empty($www_root_dir)) ? $dbinfo[$dbid][0] : $dbinfo[$dbid][1];

    // Generate dump
    $sql_script = generate_table_dump($db,$table,$options);

    // Save local copy of dump if required
    if (!empty($options['save_local'])) {
        include(__DIR__.'/common_pathdefs.php');
        if (!is_dir("$site_mysql_backup_dir/$dbname")) {
            mkdir("$site_mysql_backup_dir/$dbname,0775");
        }
        file_put_contents("$site_mysql_backup_dir/$dbname/table_{$table}.sql",$sql_script);
    }
    return $sql_script;
}

//==============================================================================
/*
Function import_db_dump

This function is called to import an SQL script into a given database, much
the same as would be done by the mysql command.

The following parameters are passed:-

$db         - Link to the connected database.

$sql_script - String containing the SQL script.
*/
//==============================================================================

function import_db_dump($db,$sql_script)
{
    mysqli_multi_query($db,$sql_script);
    do {
        if ($result = mysqli_store_result($db)) {
            mysqli_free_result($result);
        }
        if (!mysqli_more_results($db)) {
            break;
        }
        if (!mysqli_next_result($db)) {
            return $false;
        }
    }
    while (true);
    return true;
}

//==============================================================================
/*
Function update_database

This function is called to update a given database from an SQL script, given the
site and the database sub-path. The following parameters are passed:

$local_site_dir - Site path as defined in the IT services database.

$db_sub_path    - Database sub-path as defined in the IT services database.

$sql_script     - String containing the SQL script.
*/
//==============================================================================

function update_database($local_site_dir,$db_sub_path,$sql_script)
{
    global $www_root_dir;
    if (!empty($www_root_dir)) {
        include("$www_root_dir/$local_site_dir/mysql_connect.php");
    }
    else {
        include(__DIR__.'/../mysql_connect.php');
    }
    $dbid = $sub_path_lookup[$db_sub_path];
    $db = db_connect($dbid);
    $dbname = (!empty($www_root_dir)) ? $dbinfo[$dbid][0] : $dbinfo[$dbid][1];

    // Run the dump import
    import_db_dump($db,$sql_script);
}

//==============================================================================
define('MYSQL_MAINT_FUNCT_DEFINED',true);
endif;
//==============================================================================
