<?php
//==============================================================================
if (!function_exists('search_and_replace')) :
//==============================================================================

function search_and_replace($site_path,$sub_path)
{
    global $location, $ssl_hostname;

    print("<h1>Search and Replace</h1>\n");
    if ($location != 'local') {
        print("<p>This facility is currently only supported on the local server.</p>\n");
        return;
    }

    $db = admin_db_connect();
    $dbname = admin_db_name();

    // First submission - Run dump and prompt user with number of pending replacements.
    if (isset($_POST['submitted1'])) {
        if ($_POST['table'] == '#all#') {
            $url = "https://$ssl_hostname/$site_path/common_scripts/db_dump.php?sub_path=$sub_path&full=1";
            $dump = get_url_content($url,['db_passwd' => REAL_DB_PASSWD]);
        }
        elseif(!empty($_POST['table'])) {
            $url = "https://$ssl_hostname/$site_path/common_scripts/db_dump.php?sub_path=$sub_path&table={$_POST['table']}";
            $dump = get_url_content($url,['db_passwd' => REAL_DB_PASSWD]);
        }
        else {
            $dump = '';
            print("<p>ERROR - No table selected (please refresh page to try again).</p>\n");
        }
        if ((empty($_POST['search'])) || (is_numeric($_POST['search']))) {
            $dump = '';
            print("<p>ERROR - Search string cannot be empty or numeric (please refresh page to try again).</p>\n");
        }
        if (!empty($dump)) {
            $count = substr_count($dump,$_POST['search']);
            print("<p>Replacing [<strong>{$_POST['search']}</strong>] with [<strong>{$_POST['replace']}</strong>]<br />");
            print("<strong>$count</strong> occurrences found.</p>\n");
            print("<form method=\"post\">\n");
            print("<input type=\"hidden\" name=\"submitted2\" value=\"TRUE\" />\n");
            print("<input value=\"Run Update\" type=\"submit\">\n");
            print("</form>\n");
            update_session_var('search',$_POST['search']);
            update_session_var('replace',$_POST['replace']);
            update_session_var('dump_url',$url);
        }
    }

    // Second submission - Re-run dump and run edit.
    elseif (isset($_POST['submitted2'])) {
        $search = get_session_var('search');
        $replace = get_session_var('replace');
        $url = get_session_var('dump_url');
        $dump = get_url_content($url,['db_passwd' => REAL_DB_PASSWD]);
        $dump = str_replace($search,$replace,$dump,$count);
        $url = "https://$ssl_hostname/$site_path/common_scripts/db_update.php?sub_path=$sub_path";
        $options = [
            'db_passwd' => REAL_DB_PASSWD,
            'sql_script' => $dump,
        ];
        $dummy = get_url_content($url,$options);
        print("<p>Operation completed - $count instance(s) replaced.</p>\n");
    }

    // Initial page load with main form.
    else {
        print("<form method=\"post\">\n");
        print("<table cellpadding=\"8\">\n");
        print("<tr><td>Table:</td><td><select name=\"table\">\n");
        print("<option value=\"\">Please select...</option>\n");
        print("<option value=\"#all#\">[All]</option>\n");
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

//==============================================================================
endif;
//==============================================================================
