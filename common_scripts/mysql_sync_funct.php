<?php
//================================================================================
/*
These functions perform database backup, restore and sync operations by invoking
Bash commands. Interaction with the remove server is achieved by using SSH with
private/public key authentication. The availability of these function eliminates
the need for direct remote MySQL access, which may not be possible in certain
situations

They are only valid for use on a local server.
*/
//================================================================================
if (!defined('MYSQL_SYNC_FUNCT_DEFINED')):
//================================================================================

function run_db_sync_command($command,$message='',$verbose=false)
{
    global $eol;
    global $mysql_error_log;
    global $password2;
    if ($verbose) {
        print(str_replace($password2,'****',$command).$eol);
    }
    exec($command);
    if (strpos($command,'2>') !== false) {
        output_mysql_error_log();
    }
    if (!empty($message)) {
        print("$message.$eol");
    }
}

//================================================================================

function output_mysql_error_log()
{
    global $eol;
    global $mysql_error_log;
    if (is_file($mysql_error_log)) {
        $contents = file($mysql_error_log);
        foreach ($contents as $line) {
            if ((empty($line)) ||
                (strpos($line,"Using a password on the command line") !== false)) {
                // No action
            }
            else {
                print("$line$eol");
            }
        }
        unlink($mysql_error_log);
    }
}

//==============================================================================
/*
Function sync_local_and_remote_dbs

This function is called to perform a full sync between and online database and
the corresponding local database, with the option to run it in either direction.
Any designated nosync tables for the database will be excluded in the operation.
*/
//==============================================================================

function sync_local_and_remote_dbs($local_site_dir,$dbid,$user,$password,$direction,$verbose=false)
{
    include(__DIR__.'/mysql_sync_funct_includes.php');
    switch ($direction) {

        case 'down':
            // Run MySQL dump on online server
            $cmd = "$ssh_command \"/home/$cpanel_user/common_bash/run_mysqldump $main_domain $user $password $online_db db1\"";
            run_db_sync_command($cmd,"Database $online_db backed up on online server",$verbose);

            // Copy SQL script
            $source = "$cpanel_user@$main_domain:/home/$cpanel_user/mysql_backup/$online_db/db1.sql";
            $dest = "$local_mysql_backup_dir/$local_db/";
            $cmd = "$rsync_command $source $dest";
            run_db_sync_command($cmd,'Database dump copied to local server',$verbose);

            // Run MySQL update on local machine
            $cmd = "mysql --host=localhost --user=$user --password=$password -D $local_db < \"$local_sql_script\" 2>$mysql_error_log";
            run_db_sync_command($cmd,"Database $local_db restored on local server",$verbose);
            break;

        case 'up':
            // Run MySQL dump on local machine
            $cmd = "mysqldump --host=localhost --user=$user --password=$password $local_db 1>\"$local_sql_script\" 2>$mysql_error_log";
            run_db_sync_command($cmd,'Database backed up on local server',$verbose);

            // Copy SQL script
            $source = "$local_mysql_backup_dir/$local_db/db1.sql";
            $dest = "$cpanel_user@$main_domain:/home/$cpanel_user/mysql_backup/$online_db/";
            $cmd = "$rsync_command $source $dest";
            run_db_sync_command($cmd,'Database dump copied to online server',$verbose);

            // Run MySQL update on online server
            $cmd = "$ssh_command \"/home/$cpanel_user/common_bash/run_mysql $main_domain $user $password $online_db db1\"";
            run_db_sync_command($cmd,'Database restored on online server',$verbose);
            break;
    }
}

//==============================================================================
/*
Function backup_remote_db

This function backs up a full database from the remote server. It will exclude
designated nosync tables for the database, unless the $full_backup parameter is
set to true.
*/
//==============================================================================

function backup_remote_db($local_site_dir,$dbid,$user,$password,$full_backup=false,$verbose=false)
{
    include(__DIR__.'/mysql_sync_funct_includes.php');

    // Run MySQL dump on online server
    $cmd = "$ssh_command \"/home/$cpanel_user/common_bash/run_mysqldump $main_domain $user $password $online_db db1\"";
    if ($full_backup) {
        $cmd .= ' -full';
    }
    run_db_sync_command($cmd,"Database $online_db backed up on online server",$verbose);

    // Copy SQL script
    $source = "$cpanel_user@$main_domain:/home/$cpanel_user/mysql_backup/$online_db/db1.sql";
    $dest = "$remote_mysql_backup_dir/$online_db/";
    $cmd = "$rsync_command $source $dest";
    run_db_sync_command($cmd,'Database dump copied to local server',$verbose);
}

//==============================================================================
/*
Function backup_remote_db_table

This function backs up an individual table from a remote database.
*/
//==============================================================================

function backup_remote_db_table($local_site_dir,$dbid,$user,$password,$table,$verbose=false)
{
    include(__DIR__.'/mysql_sync_funct_includes.php');

    // Run MySQL dump on online server
    $cmd = "$ssh_command \"/home/$cpanel_user/common_bash/run_mysqldump $main_domain $user $password $online_db table_$table -t $table\"";
    run_db_sync_command($cmd,"Database $online_db table $table backed up on online server",$verbose);

    // Copy SQL script
    $source = "$cpanel_user@$main_domain:/home/$cpanel_user/mysql_backup/$online_db/table_$table.sql";
    $dest = "$remote_mysql_backup_dir/$online_db/";
    $cmd = "$rsync_command $source $dest";
    run_db_sync_command($cmd,'Table dump copied to local server',$verbose);
}

//==============================================================================
/*
Function update_remote_db

This function updates/restores a remote database from a given SQL script.
*/
//==============================================================================

function update_remote_db($local_site_dir,$dbid,$user,$password,$script,$verbose=false)
{
    include(__DIR__.'/mysql_sync_funct_includes.php');

    // Copy SQL script
    $source = "$remote_mysql_backup_dir/$online_db/$script.sql";
    $dest = "$cpanel_user@$main_domain:/home/$cpanel_user/mysql_backup/$online_db/";
    $cmd = "$rsync_command $source $dest";
    run_db_sync_command($cmd,'SQL script copied to online server',$verbose);

    // Run MySQL update on online server
    $cmd = "$ssh_command \"/home/$cpanel_user/common_bash/run_mysql $main_domain $user $password $online_db $script\"";
    run_db_sync_command($cmd,'Database updated/restored on online server',$verbose);
}

//==============================================================================
define( 'MYSQL_SYNC_FUNCT_DEFINED', true );
endif;
//==============================================================================
