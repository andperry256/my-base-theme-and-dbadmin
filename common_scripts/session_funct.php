<?php
//================================================================================
if (!function_exists('run_session')) :
//================================================================================
/*
* Function run_session
*
* This function is used both inside and outside the WordPress environment.
* When used inside WordPress it is invoked through the inclusion of this file
* within the main functions.php file for the theme. The latter must run the
* following statement immediately after the file inclusion:-
*
* add_action( 'init', 'run_session', 1);
*/
//================================================================================

function run_session()
{
    global $wpdb;
    global $global_session_vars;
    global $global_session_id;
    if ((!session_id()) && (!headers_sent())) {
        session_start();
    }
    if (!session_id()) {
        // This should not occur
        exit ("ERROR - Unable to start session");
    }
    $global_session_id = session_id();

    $db_wp = wp_db_connect();
    if (!$db_wp) {
        // This should not occur
        exit("ERROR - Unable to connect to the WP database.");
    }
    if ((isset($wpdb)) && (function_exists('have_posts'))) {
        // Running inside the WP environment
        if (is_file('/var/www/html/user_authentication.php')) {
            include('/var/www/html/user_authentication.php');
        }
        if (!isset($_SESSION['theme_mode'])) {
            $_SESSION['theme_mode'] = 'light';
        }
        $env = 'wp';
    }
    elseif (function_exists('wp_db_connect')) {
        // Running outside the WP environment
        $env = 'non-wp';
    }

    /*
    The following query checks whether the table wp_session_updates is present.
    If it is then the current session variables are all transferred to the array
    $global_session_vars and the PHP session is closed.
    If the table is not present then no action is performed and the PHP session
    left permanently open (not recommended).
    */
    $query_result = mysqli_select_query($db_wp,'wp_session_updates','*','',[],'');
    if ($query_result !== false) {
        // Transfer all updates for the current session from the database to the
        // appropriate $_SESSION variables.
        if ($env == 'wp') {
            // Inside the WordPress environment
            $where_clause = 'session_id=?';
            $where_values = ['s',$global_session_id];
            $query_result2 = mysqli_select_query($db_wp,'wp_session_updates','*',$where_clause,$where_values,'');
            while ($row2 = mysqli_fetch_assoc($query_result2)) {
                if ($row2['type'] == 'update') {
                    // Update is a variable assignment
                    if ($row2['name2'] == '#') {
                        $_SESSION[$row2['name']] = $row2['value'];
                    }
                    else {
                        if ((!isset($_SESSION[$row2['name']])) || (!is_array($_SESSION[$row2['name']]))) {
                            $_SESSION[$row2['name']] = [];
                        }
                        $_SESSION[$row2['name']][$row2['name2']] = $row2['value'];
                    }
                }
                elseif (($row2['type'] == 'delete') && (isset($_SESSION[$row2['name']]))) {
                    // Update is a variable deletion (unset)
                    if ($row2['name2'] == '#') {
                        unset($_SESSION[$row2['name']]);
                    }
                    else {
                        unset($_SESSION[$row2['name']][$row2['name2']]);
                    }
                }
            }
        }
        else {
            // Outside the WordPress environment
            $where_clause = 'session_id=?';
            $where_values = ['s',$global_session_id];
            $query_result2 = mysqli_select_query($db_wp,'wp_session_updates','*',$where_clause,$where_values,'');
            while ($row2 = mysqli_fetch_assoc($query_result2)) {
                if ($row2['type'] == 'update') {
                    // Update is a variable assignment
                    if ($row2['name2'] == '#') {
                        $_SESSION[$row2['name']] = $row2['value'];
                    }
                    else {
                        if ((!isset($_SESSION[$row2['name']])) || (!is_array($_SESSION[$row2['name']]))) {
                            $_SESSION[$row2['name']] = [];
                        }
                        $_SESSION[$row2['name']][$row2['name2']] = $row2['value'];
                    }
                }
                elseif (($row2['type'] == 'delete') && (isset($_SESSION[$row2['name']]))) {
                    // Update is a variable deletion (unset)
                    if ($row2['name2'] == '#') {
                        unset($_SESSION[$row2['name']]);
                    }
                    else {
                        unset($_SESSION[$row2['name']][$row2['name2']]);
                    }
                }
            }
        }
        $where_clause = 'session_id=?';
        $where_values = ['s',$global_session_id];
        mysqli_delete_query($db_wp,'wp_session_updates',$where_clause,$where_values);

        // Transfer all $_SESSION variables into the $global_session_vars array.
        $global_session_vars = [];
        foreach($_SESSION as $name => $value) {
            if (is_array($_SESSION[$name])) {
                $global_session_vars[$name] = [];
                foreach($_SESSION[$name] as $name2 => $value2) {
                    $global_session_vars[$name][$name2] = $value2;
                }
            }
            else {
                $global_session_vars[$name] = $value;
            }
        }

        // Close the session.
        session_write_close();
    }

    /*
      Run the 'post_run_session' function if present. It should be in the
      functions.php script for the associated child theme.
    */
    if (function_exists('post_run_session')) {
        post_run_session();
    }
}

//================================================================================
/*
* Session variable handling functions
*
* This section includes the following functions:-
* 1. session_var_is_set
* 2. get_session_var
* 3. update_session_var
* 4. delete_session_var
*
* Each function determines whether actual $_SESSION variables are in force
* or whether they have been saved to $global_session_vars and the PHP session
* closed.
*
* Each function takes a parameter '$name' which can be one of:-
* 1. A single name to index a simple array.
* 2. An array of two names to index a 2-dimensional array.
*/
//================================================================================

function session_var_is_set($name)
{
    global $global_session_vars;
    if (!is_array($name)) {
        $name = [$name,''];
    }

    if (count($name) != 2) {
        return false;
    }
    elseif (isset($global_session_vars)) {
        if (empty($name[1])) {
            return isset($global_session_vars[$name[0]]);
        }
        else {
            return isset($global_session_vars[$name[0]][$name[1]]);
        }
    }
    elseif (empty($name[1])) {
        return isset($_SESSION[$name[0]]);
    }
    else {
        return isset($_SESSION[$name[0]][$name[1]]);
    }
}

//================================================================================

function get_session_var($name)
{
    global $global_session_vars;
    if (!is_array($name)) {
        $name = [$name,''];
    }

    if (count($name) != 2) {
        return false;
    }
    elseif (isset($global_session_vars)) {
        if (empty($name[1])) {
            return $global_session_vars[$name[0]] ?? false;
        }
        else {
            return $global_session_vars[$name[0]][$name[1]] ?? false;
        }
    }
    elseif (empty($name[1])) {
        return $_SESSION[$name[0]] ?? false;
    }
    else {
        return $_SESSION[$name[0]][$name[1]] ?? false;
    }
}

//================================================================================

function update_session_var($name,$value)
{
    $value = strval($value);
    global $global_session_vars;
    global $global_session_id;
    $db_wp = wp_db_connect();
    if (!$db_wp) {
        // This should not occur
        exit("ERROR - Unable to connect to the WP database.");
    }
    if (!is_array($name)) {
        $name = [$name,''];
    }

    if (count($name) != 2) {
        return false;
    }
    elseif (isset($global_session_vars)) {
        $timestamp = time();
        $old_timestamp = $timestamp - 86400;  // 24 hours ago
        $where_clause = 'timestamp<?';
        $where_values = ['i',$old_timestamp];
        mysqli_delete_query($db_wp,'wp_session_updates',$where_clause,$where_values);
        if (empty($name[1])) {
            $global_session_vars[$name[0]] = $value;
            $fields = 'session_id,name,value,type,timestamp';
            $values = ['s',$global_session_id,'s',$name[0],'s',$value,'s','update','i',$timestamp];
            $where_clause = 'session_id=? AND name=?';
            $where_values = ['s',$global_session_id,'s',$name[0]];
        }
        else {
            if ((!isset($global_session_vars[$name[0]])) || (!is_array($global_session_vars[$name[0]]))) {
                $global_session_vars[$name[0]] = [];
            }
            $global_session_vars[$name[0]][$name[1]] = $value;
            $fields = 'session_id,name,name2,value,type,timestamp';
            $values = ['s',$global_session_id,'s',$name[0],'s',$name[1],'s',$value,'s','update','i',$timestamp];
            $where_clause = 'session_id=? AND name=? AND name2=?';
            $where_values = ['s',$global_session_id,'s',$name[0],'s',$name[1]];
        }
        if (mysqli_conditional_insert_query($db_wp,'wp_session_updates',$fields,$values,$where_clause,$where_values) === NOINSERT) {
            $set_fields = 'value,type';
            $set_values = ['s',$value,'s','update'];
            mysqli_update_query($db_wp,'wp_session_updates',$set_fields,$set_values,$where_clause,$where_values);
        }
    }
    elseif (empty($name[1])) {
        $_SESSION[$name[0]] = $value;
    }
    else {
        if (!isset($_SESSION[$name[0]])) {
            $_SESSION[$name[0]] = [];
        }
        $_SESSION[$name[0]][$name[1]] = $value;
    }
}

//================================================================================

function delete_session_var($name)
{
    global $global_session_vars;
    global $global_session_id;
    $db_wp = wp_db_connect();
    if (!$db_wp) {
        // This should not occur
        exit("ERROR - Unable to connect to the WP database.");
    }
    if (!is_array($name)) {
        $name = [$name,''];
    }

    if (count($name) != 2) {
        return false;
    }
    elseif (isset($global_session_vars)) {
        $timestamp = time();
        if (empty($name[1])) {
            if (isset($global_session_vars[$name[0]])) {
                unset($global_session_vars[$name[0]]);
            }
            $fields = 'session_id,name,type,timestamp';
            $values = ['s',$global_session_id,'s',$name[0],'s','delete','i',$timestamp];
            $where_clause = 'session_id=? AND name=?';
            $where_values = ['s',$global_session_id,'s',$name[0]];
        }
        else {
            if (isset($global_session_vars[$name[0]][$name[1]])) {
                unset($global_session_vars[$name[0]][$name[1]]);
            }
            $fields = 'session_id,name,name2,type,timestamp';
            $values = ['s',$global_session_id,'s',$name[0],'s',$name[1],'s','delete','i',$timestamp];
            $where_clause = 'session_id=? AND name=? AND name2=?';
            $where_values = ['s',$global_session_id,'s',$name[0],'s',$name[1]];
        }
        if (mysqli_conditional_insert_query($db_wp,'wp_session_updates',$fields,$values,$where_clause,$where_values) === NOINSERT) {
            $set_fields = 'type';
            $set_values = ['s','update'];
            mysqli_update_query($db_wp,'wp_session_updates',$set_fields,$set_values,$where_clause,$where_values);
        }
    }
    elseif ((empty($name[1])) && (isset($_SESSION[$name[0]]))) {
        unset($_SESSION[$name[0]]);
    }
    elseif ((!empty($name[1])) && (isset($_SESSION[$name[0]][$name[1]]))) {
        unset($_SESSION[$name[0]][$name[1]]);
    }
}

//================================================================================

function clear_session_update_records($name)
{
    global $global_session_vars;
    global $global_session_id;
    $db_wp = wp_db_connect();
    if (!$db_wp) {
        // This should not occur
        exit("ERROR - Unable to connect to the WP database.");
    }
    if (!is_array($name)) {
        $name = [$name,null];
    }

    if (count($name) != 2) {
        return false;
    }
    if ($name[1] === null) {
        $where_clause = 'session_id=? AND name=?';
        $where_values = ['s',$global_session_id,'s',$name[0]];
    }
    else {
        $where_clause = 'session_id=? AND name=? AND name2=?';
        $where_values = ['s',$global_session_id,'s',$name[0],'s',$name[1]];
    }
    mysqli_delete_query($db_wp,'wp_session_updates',$where_clause,$where_values);
}

//================================================================================
endif;
//================================================================================
?>
