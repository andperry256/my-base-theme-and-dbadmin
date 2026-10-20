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
/*
Function import_table_from_csv

This function loads the data from a CSV file into a given DB table.
*/
//==============================================================================

function import_table_from_csv($db,$table,$input_file,$reset_table=false)
{
    global $eol;

    // Re-create empty table if option is set.
    if ($reset_table) {
        try {
            $query_result = mysqli_query($db, "SHOW CREATE TABLE `$table`");
        }
        catch (Exception $e) {
            if (empty($query_result)) {
                return false;
            }
        }
        $row = mysqli_fetch_row($query_result);
        mysqli_query($db,"DROP TABLE IF EXISTS `$table`");
        mysqli_query($db,$row[1]);
    }

    // Load file and get field list from first line.
    $csv_content = file($input_file);
    $field_list = trim($csv_content[0],',');
    unset($csv_content[0]);
    $sql_script = '';

    // Loop through records.
    foreach($csv_content as $line) {
        $values =  str_getcsv($line);
        $value_list = '';

        // Loop through field values.
        foreach ($values as $value) {
            $value_list .= "'" . mysqli_real_escape_string($db,$value) . "',";
        }
        $value_list = rtrim($value_list,',');

        // Run the INSERT query.
        $query_result = mysqli_query($db,"INSERT INTO `$table` ($field_list) VALUES ($value_list)");
        if ((!$query_result) && (isset($eol))) {
            print("Unable to insert record ($value_list)$eol");
        }
    }
}

//==============================================================================
/*
Function export_table_to_csv

This function dumps the data from a given DB table into a CSV file.
*/
//==============================================================================

function export_table_to_csv($db, $table, $output_file, $where_clause='')
{
    $output_csv_content = '';
    $query_result = mysqli_select_query($db,$table,'*',$where_clause,[],'');
    $ofp = fopen($output_file, 'w');

    // Generate field list as first row.
    $field_info = mysqli_fetch_fields($query_result);
    foreach ($field_info as$field) {
        $headers[] = $field->name;
    }
    fputcsv($ofp,$headers);

    // Loop through records.
    while ($row = mysqli_fetch_assoc($query_result)) {
        fputcsv($ofp,$row);
    }
    fclose($ofp);
}

//==============================================================================
/*
Function transfer_mysql_table_data

This function copies table data from one database to another. The destination
table is always initialised to a blank copy of the source table before copying
records.

The following parameters are passed:-
$db1          - Connection to source DB.
$db2          - Connection to dest DB.
$table        - Table being copied.
$select_query - Leave blank to copy whole table. Otherwise set to a select query
                to be applied to the table.
*/
//==============================================================================

function transfer_mysql_table_data($db1,$db2,$table,$select_query='')
{
    // Re-initialise table in destination DB.
    mysqli_query($db2,"DROP TABLE IF EXISTS `$table`");
    $query_result = mysqli_query($db1, "SHOW CREATE TABLE `$table`");
    $row = mysqli_fetch_row($query_result);
    $query = rtrim($row[1],';');
    mysqli_query($db2,$query);

    // Loop through records in source DB.
    if (empty($select_query)) {
        $select_query = "SELECT * FROM `$table`";
    }
    $query_result = mysqli_query($db1,$select_query);
    while ($row = mysqli_fetch_assoc($query_result)) {

        // Build and run INSERT query.
        $field_list = '';
        $value_list = '';
        foreach ($row as $field_name => $value) {
            $field_list .= "$field_name,";
            if ($value === null) {
                $value_list .= 'NULL,';
            }
            else {
                $value_list .= "'" . mysqli_real_escape_string($db1,$value) . "',";
            }
        }
        $field_list = rtrim($field_list,',');
        $value_list = rtrim($value_list,',');
        $insert_query = "INSERT INTO {$table} ({$field_list}) VALUES ({$value_list})";
        mysqli_query($db2,$insert_query);
    }
}

//==============================================================================
define('MYSQL_MAINT_FUNCT_DEFINED',true);
endif;
//==============================================================================
