<?php
//==============================================================================
if (!function_exists('sync_databases')) :
//==============================================================================

function sync_databases($site_path,$sub_path)
{
    global $eol;
    global $base_dir;
    global $eol;
    global $location;
    global $main_domain;
    global $maintenance_dir;
    global $mysql_backup_dir;
    global $server_station_id;
    global $ssl_hostname;
    $eol = "<br />\n";
    print("<h1>Database Synchronisation</h1>\n");
     if ($location != 'local') {
        print("<p>This facility is currently only supported on the local server.</p>\n");
        return;
    }

    require("$maintenance_dir/db_master_location.php");
    require("/media/Data/Links/Utilities/PHP_Scripts/mysql_sync_funct.php");
    require("/$base_dir/common_scripts/mysql_maint_funct.php");
    $db = admin_db_connect();
    $db_its = itservices_db_connect();

    // Obtain local DB name.
    $where_clause = 'site_path=? AND sub_path=? AND domname=?';
    $where_values = ['s',$site_path,'s',$sub_path,'s',$main_domain];
    $query_result = mysqli_select_query($db_its,'dbases','*',$where_clause,$where_values,'');
    if ($row = mysqli_fetch_assoc(mysqli_select_query($db_its,'dbases','*',$where_clause,$where_values,''))) {
        $online_dbname = $row['dbname'];
    }
    else {
        // This should not occur.
        print("<p>Online DB record not found in IT Services database.</p>\n");
        return;
    }

    // Obtain online DB name and default sync direction.
    $where_clause = 'site_path=? AND sub_path=? AND domname=?';
    $where_values = ['s',$site_path,'s',$sub_path,'s',$server_station_id];
    if ($row = mysqli_fetch_assoc(mysqli_select_query($db_its,'dbases','*',$where_clause,$where_values,''))) {
        $local_dbname = $row['dbname'];;
        switch ($row['mode']) {
            case 'auto':
                $sync_direction = ($location == $db_master_location[$sub_path]) ? 'out' : 'in';
                    break;

            case 'master':
                $sync_direction = 'out';
                    break;

            case 'sub-master':
                $sync_direction = 'in';
                    break;
        }
    }
    else {
        // This should not occur.
        print("<p>Local DB record not found in IT Services database.</p>\n");
        return;
    }

    // Process the action.
    if (isset($_POST['submitted'])) {

        $local_mysql_backup_dir = "$mysql_backup_dir/$server_station_id/$local_dbname";
        $remote_mysql_backup_dir = "$mysql_backup_dir/$main_domain/$online_dbname";

        switch ($_POST['sync_mode']) {

            case 'in':
                db_backup($site_path,$sub_path,$main_domain,$remote_mysql_backup_dir,'',['dbname' => $online_dbname]);
                copy("$remote_mysql_backup_dir/db1.sql","$local_mysql_backup_dir/db1.sql");
                db_restore($site_path,$sub_path,$ssl_hostname,$local_mysql_backup_dir,'db1',['dbname' => $local_dbname]);
                break;

            case 'out':
                db_backup($site_path,$sub_path,$ssl_hostname,$local_mysql_backup_dir,'',['dbname' => $local_dbname]);
                copy("$local_mysql_backup_dir/db1.sql","$remote_mysql_backup_dir/db1.sql");
                db_restore($site_path,$sub_path,$main_domain,$remote_mysql_backup_dir,'db1',['dbname' => $online_dbname]);
                break;

            case 'backup':
                db_backup($site_path,$sub_path,$ssl_hostname,$local_mysql_backup_dir,'',['dbname' => $local_dbname]);
                break;

            case 'restore':
                db_restore($site_path,$sub_path,$ssl_hostname,$local_mysql_backup_dir,'db1','',['dbname' => $local_dbname]);
                break;

            case 'table-in':
                $table = $_POST['table'];
                if ((empty($table)) || (substr($table,0,2) == 'V#')) {
                    print("<p>ERROR - valid table not selected (please refresh page to try again).</p>\n");
                }
                else {
                    db_backup($site_path,$sub_path,$main_domain,$remote_mysql_backup_dir,$table,['dbname' => $online_dbname]);
                    copy("$remote_mysql_backup_dir/table_{$table}.sql","$local_mysql_backup_dir/table_{$table}.sql");
                    db_restore($site_path,$sub_path,$ssl_hostname,$local_mysql_backup_dir,"table_{$table}",['dbname' => $local_dbname]);
                    unlink("$local_mysql_backup_dir/table_{$table}.sql");
                    unlink("$remote_mysql_backup_dir/table_{$table}.sql");
                }
                break;

            case 'table-out':
                $table = $_POST['table'];
                if ((empty($table)) || (substr($table,0,2) == 'V#')) {
                    print("<p>ERROR - valid table not selected (please refresh page to try again).</p>\n");
                }
                else {
                    db_backup($site_path,$sub_path,$ssl_hostname,$local_mysql_backup_dir,$table,['dbname' => $local_dbname]);
                    copy("$local_mysql_backup_dir/table_{$table}.sql","$remote_mysql_backup_dir/table_{$table}.sql");
                    db_restore($site_path,$sub_path,$main_domain,$remote_mysql_backup_dir,"table_{$table}",['dbname' => $online_dbname]);
                    unlink("$local_mysql_backup_dir/table_{$table}.sql");
                    unlink("$remote_mysql_backup_dir/table_{$table}.sql");
                }
                break;

            case 'table-dump':
                $table = str_replace('V#','',$_POST['table']);
                if (empty($table)) {
                    print("<p>ERROR - valid table not selected (please refresh page to try again).</p>\n");
                }
                else {
                    export_table_to_csv($db, $table, "$local_mysql_backup_dir/table_{$table}.csv");
                    print("<p>Table $local_dbname.$table exported to table_{$table}.csv.</p>");
                }
                break;

            case 'table-append':
            case 'table-replace':
                $table = $_POST['table'];
                $csv_file = "$local_mysql_backup_dir/table_{$table}.csv";
                $reset_table = ($_POST['sync_mode'] == 'table-replace');
                if ((empty($table)) || (substr($table,0,2) == 'V#')) {
                    print("<p>ERROR - valid table not selected (please refresh page to try again).</p>\n");
                }
                elseif (!is_file($csv_file))
                    print("<p>ERROR - CSV file not found (please refresh page to try again).</p>\n");
                else {
                    import_table_from_csv($db, $table, "$local_mysql_backup_dir/table_{$table}.csv",$reset_table);
                    $action = ($reset_table) ? 'replaced from' : 'appended from';
                    print("<p>Table $local_dbname.$table $action table_{$table}.csv.</p>");
                }

                break;
        }
    }

    // Output the main form.
    else {
        print("<p>This facility is used to synchronise the local and online copies of the database. It may take a minute or so to complete, so please be patient.<br /><br />==== Please use with caution!! ====</p>\n");
        print("<form method=\"post\">\n");
        print("<table cellpadding=\"8\">\n");
        print("<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"in\"");
        if ($sync_direction == 'in') {
            print(" checked");
        }
        print("></td><td>Download online DB to local DB</td></tr>\n");

        print("<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"out\"");
        if ($sync_direction == 'out') {
            print(" checked");
        }
        print("></td><td>Upload local DB to online DB</td></tr>\n");
        print("<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"backup\"></td><td>Back up local DB (locally)</td></tr>\n");
        print("<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"restore\"></td><td>Restore local DB (locally)</td></tr>\n");
        print("<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"table-in\"></td><td>Download table to local DB</td></tr>\n");
        print("<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"table-out\"></td><td>Upload table to online DB</td></tr>\n");
        print("<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"table-dump\"></td><td>Dump table to CSV</td></tr>\n");
        print("<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"table-append\"></td><td>Append table from CSV</td></tr>\n");
        print("<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"table-replace\"></td><td>Replace table from CSV</td></tr>\n");
        print("<tr><td>Table:</td><td><select name=\"table\">\n");
        print("<option value=\"\">Please select...</option>\n");
        $local_dbname = admin_db_name();
        $query_result = mysqli_query_normal($db,"SHOW FULL TABLES FROM `$local_dbname` WHERE Table_type='BASE TABLE'");
        while ($row = mysqli_fetch_assoc($query_result)) {
            print("<option value=\"{$row["Tables_in_$local_dbname"]}\">{$row["Tables_in_$local_dbname"]}</option>\n");
        }
        $query_result = mysqli_query_normal($db,"SHOW FULL TABLES FROM `$local_dbname` WHERE Table_type='VIEW'");
        while ($row = mysqli_fetch_assoc($query_result)) {
            print("<option value=\"V#{$row["Tables_in_$local_dbname"]}\">[v] {$row["Tables_in_$local_dbname"]}</option>\n");
        }
        print("</select><br />");
        print("<span class=\"small\">[v] = Item is a view - valid only with dump to CSV option.</span></td></tr>");
        print("<tr><td colspan=\"2\"><input value=\"Run\" type=\"submit\"></td></tr>\n");
        print("</table>\n");
        print("<input type=\"hidden\" name=\"submitted\" value=\"TRUE\" />\n");
        print("</form>\n");
    }
}

//==============================================================================
endif;
//==============================================================================
