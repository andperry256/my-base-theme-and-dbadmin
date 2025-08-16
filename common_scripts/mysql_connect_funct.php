<?php
//==============================================================================
/*
This script is designed by be included by the mysql_connect.php script for the
given site.
*/
//==============================================================================
/*
Function db_connect

This is the main function to connect to the MySQL database associated with a
given database ID as defined in the $dbinfo array for the site. Each element of
this array has the database ID as the key and is itself an array with the
following elements:-
0 - Local database name.
1 - Online database name.
2 - Default character set (optional).

This function performs a MySQLi connection using either object orientated or
procedural style, as specified by the $mode parameter (defaults to procedural
style).

The database user name normally defaults to that defined by the constant
REAL_DB_USER, but there is the option to override this with an optional
parameter (e.g. to gain elevated access).
*/
//==============================================================================

function db_connect($dbid,$mode='p',$alt_user='')
{
    global $db_mode, $location, $dbinfo;
    $main_user = (!empty($alt_user))
        ? $alt_user
        : REAL_DB_USER;

    $local_db = strtok($dbinfo[$dbid][0],'/');
    $tok = strtok('/');
    $local_host = (!empty($tok)) ? $tok : 'localhost';

    $online_db = strtok($dbinfo[$dbid][1],'/');
    $tok = strtok('/');
    $online_host = (!empty($tok)) ? $tok : 'localhost';
    $remote_host = (!empty($tok)) ? $tok : REMOTE_DB_HOST;

    switch ($db_mode) {
        case 'normal':
            $connect_params = ($location == 'local')
              ? [ $local_host, $main_user, REAL_DB_PASSWD, $local_db ]
              : [ $online_host, $main_user, REAL_DB_PASSWD, $online_db ];
            break;
        case 'local':
            $connect_params = [ $local_host, LOCAL_DB_USER, LOCAL_DB_PASSWD, $local_db ];
            break;
        case 'remote':
            $connect_params = [ $remote_host, $main_user, REAL_DB_PASSWD, $online_db ];
            break;
    }
    $connect_error = false;
    switch ($mode) {
        case 'o':
            // Object orientated style
            $link = new mysqli( $connect_params[0], $connect_params[1], $connect_params[2], $connect_params[3] );
            if ($link->connect_errno) {
                $connect_error = true;
            }
            elseif (!empty($dbinfo[$dbid][2])) {
                $link->set_charset($dbinfo[$dbid][2]);
            }
            break;
        case 'p':
            // Procedural style
            $link = mysqli_connect( $connect_params[0], $connect_params[1], $connect_params[2], $connect_params[3] );
            if (mysqli_connect_errno()) {
                $connect_error = true;
            }
            elseif (!empty($dbinfo[$dbid][2])) {
                mysqli_set_charset($link,$dbinfo[$dbid][2]);
            }
            break;
    }
    if ($connect_error) {
        if (($location == 'local') && (function_exists('print_stack_trace_for_mysqli_error'))) {
            print_stack_trace_for_mysqli_error();
        }
        else {
            exit("Unable to establish a database connection\n");
        }
    }
    else {
        return $link;
    }
}

//==============================================================================
/*
Function wp_db_connect

This function connects to the WordPress database for the site. It always
returns an object.
*/
//==============================================================================

function wp_db_connect()
{
    return db_connect(WP_DBID,'p');
}

//==============================================================================
/*
Function db_name

This function returns the database name associated with a given database ID as
defined in the $dbinfo array for the site.
*/
//==============================================================================

function db_name($dbid)
{
    global $dbinfo, $db_mode, $location;
    switch ($db_mode) {
        case 'normal':
              if ($location == 'local') {
                  return $dbinfo[$dbid][0];
              }
              else {
                  return $dbinfo[$dbid][1];
              }
              break;
        case 'local':
              return $dbinfo[$dbid][0];
              break;
        case 'remote':
              return $dbinfo[$dbid][1];
              break;
    }
}

//==============================================================================
/*
Function itservices_db_connect
*/
//==============================================================================

if (!function_exists('itservices_db_connect')) {
    function itservices_db_connect($mode='p')
    {
        global $location, $www_root_dir;
        if (($location == 'local') && (function_exists('local_itservices_db_connect'))) {
            // Local host with a function 'local_itservices_db_connect' for the given web site.
            return local_itservices_db_connect($mode);
        }
        elseif (($location == 'local') && (is_dir("$www_root_dir/Sites/andperry.com/private_scripts"))) {
            // Local host with no function 'local_itservices_db_connect' for the given web site.
            // Connect by default to the IT Services database for andperry.com.
            switch ($mode) {
                case 'o':
                    return new mysqli( 'localhost', LOCAL_DB_USER, LOCAL_DB_PASSWD, 'local_itservices' );
                    break;
                case 'p':
                    return mysqli_connect( 'localhost', LOCAL_DB_USER, LOCAL_DB_PASSWD, 'local_itservices' );
                    break;
                default:
                    return false;
                    break;
            }
        }
        elseif (($location == 'real') && (function_exists('online_itservices_db_connect'))) {
            // Online host with a function 'online_itservices_db_connect' for the given web site.
            return online_itservices_db_connect($mode);
        }
        else {
            return false;
        }
    }
}

//==============================================================================
