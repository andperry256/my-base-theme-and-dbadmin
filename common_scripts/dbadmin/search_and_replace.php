<?php
//==============================================================================
if (!function_exists('search_and_replace')) :
//==============================================================================

function search_and_replace($local_db_name)
{
    global $location, $server_station_id, $maintenance_dir;
    $dummy = '))';  // To prevent false positive in syntax checker

    print("<h1>Search and Replace</h1>\n");
    $db = admin_db_connect();
    if ($location == 'local') {
        $db_its = itservices_db_connect();
        $where_clause = 'dbname=? AND domname=?';
        $where_values = ['s',$local_db_name,'s',$server_station_id];
        $query_result = mysqli_select_query($db_its,'dbases','*',$where_clause,$where_values,'');
        if ($row = mysqli_fetch_assoc($query_result)) {
            $where_clause = 'site_path=? AND sub_path=?';
            $where_values = ['s',$row['site_path'],'s',$row['sub_path']];
            if ($row2 = mysqli_fetch_assoc(mysqli_select_query($db_its,'db_sets','*',$where_clause,$where_values,''))) {
                $user = $row2['username'];
            }
            else {
                exit("Unable to get username for DB - this should not occur.");
            }
            $dbname = admin_db_name();
            $password = REAL_DB_PASSWD;
            $dumpfile = "$maintenance_dir/temp.sql";
            if (isset($_POST['submitted1'])) {
                if ($_POST['table'] == '#all#') {
                    $command = "mysqldump --host localhost --user=$user --password=$password $dbname 1>\"$dumpfile\"";
                }
                elseif(!empty($_POST['table'])) {
                    $command = "mysqldump --host localhost --user=$user --password=$password $dbname {$_POST['table']} 1>\"$dumpfile\"";
                }
                else {
                    $command = '';
                    print("<p>ERROR - No table selected (please refresh page to try again).</p>");
                }
                if ((empty($_POST['search'])) || (is_numeric($_POST['search']))) {
                    $command = '';
                    print("<p>ERROR - Search string cannot be empty or numeric (please refresh page to try again).</p>");
                }
                if (!empty($command)) {
                    exec($command);
                    $command = "/Utilities/php_script explode_mysql_dump $dumpfile -ow";
                    exec($command);
                    $content = file($dumpfile);
                    $count = 0;
                    foreach ($content as $line) {
                        if (substr($line,0,1) == "(") {
                            $count += substr_count($line,$_POST['search']);
                        }
                    }
                    print("<p>Replacing [<strong>{$_POST['search']}</strong>] with [<strong>{$_POST['replace']}</strong>]<br />");
                    print("<strong>$count</strong> occurrences found.</p>\n");
                    print("<form method=\"post\">\n");
                    print("<input type=\"hidden\" name=\"submitted2\" value=\"TRUE\" />\n");
                    print("<input value=\"Run Update\" type=\"submit\">\n");
                    print("</form>\n");
                    update_session_var('search',$_POST['search']);
                    update_session_var('replace',$_POST['replace']);
                }
            }
            elseif (isset($_POST['submitted2'])) {
                $content = file($dumpfile);
                $search = get_session_var('search');
                $replace = get_session_var('replace');
                $count = 0;
                $ofp = fopen($dumpfile,'w');
                foreach ($content as $line) {
                    if (substr($line,0,1) == "(") {
                        $line = str_replace($search,$replace,$line,$sub_count);
                        $count += $sub_count;
                    }
                    $line = str_replace('%','%%',$line);
                    fprintf($ofp,$line);
                }
                fclose($ofp);
                $command = "mysql --host localhost --user=$user --password=$password -D $dbname < \"$dumpfile\"";
                exec($command);
                unlink($dumpfile);
                print("<p>Operation completed - $count instance(s) replaced.</p>\n");
            }
            else {
                print("<form method=\"post\">\n");
                print("<table cellpadding=\"8\">\n");
                print("<tr><td>Table:</td><td><select name=\"table\">\n");
                print("<option value=\"\">Please select...</option>\n");
                print("<option value=\"#all#\">[All]</option>\n");
                $dbname = admin_db_name();
                $query_result = mysqli_query_normal($db,"SHOW FULL TABLES FROM `$dbname` WHERE Table_type='BASE TABLE'");
                while ($row = mysqli_fetch_assoc($query_result)) {
                    print("<option value=\"{$row["Tables_in_$dbname"]}\">{$row["Tables_in_$dbname"]}</option>\n");
                }
                print("</select></td></tr>\n");
                print("<tr><td>Search:</td><td><input type=\"text\" name=\"search\" size=\"60\"></td></tr>\n");
                print("<tr><td>Replace with:</td><td><input type=\"text\" name=\"replace\" size=\"60\"></td></tr>\n");
                print("<tr><td colspan=\"2\"><input value=\"Continue\" type=\"submit\"></td></tr>\n");
                print("</table>\n");
                print("<input type=\"hidden\" name=\"submitted1\" value=\"TRUE\" />\n");
                print("</form>\n");
            }
        }
        else {
            print("<p>Unable to locate database record in sites database.</p>\n");
        }
    }
    else {
        print("<p>This facility is currently only supported on the local server.</p>\n");
    }
}

//==============================================================================
endif;
//==============================================================================
