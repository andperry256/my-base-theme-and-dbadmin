<?php
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

function run_single_db_sync($dbid,$user,$password,$direction,$verbose=false)
{
    global $cpanel_user;
    global $dbinfo;
    global $eol;
    global $local_mysql_backup_dir;
    global $main_domain;
    global $mysql_error_log;
    global $online_port;
    global $password2;
    global $private_key_path;
    global $server_station_id;

    $password2 = $password;
    $eol = isset($_SERVER['REMOTE_ADDR']) ? "<br />\n" : "\n";
    $local_mysql_backup_dir = "/media/Data/Users/Common/Documents/MySQL_Backup/$server_station_id";
    $rsync_command = "rsync -r -e \"ssh -p $online_port -l $cpanel_user -i $private_key_path\"";
    $ssh_command = "ssh -p $online_port -l $cpanel_user -i $private_key_path $main_domain";
    $local_db = $dbinfo[$dbid][0];
    $online_db = $dbinfo[$dbid][1];
    $local_sql_script = "$local_mysql_backup_dir/$local_db/db1.sql";
    $mysql_error_log = "$local_mysql_backup_dir/$local_db/mysql_errors.log";

    switch ($direction) {

        case 'down':
            # Run MySQL dump on online server
            $cmd = "$ssh_command \"/home/$cpanel_user/common_bash/run_mysqldump $main_domain $user $password $online_db db1\"";
            run_db_sync_command($cmd,"Database $online_db backed up on online server",$verbose);

            # Copy SQL script
            $source = "$cpanel_user@$main_domain:/home/$cpanel_user/mysql_backup/$online_db/db1.sql";
            $dest = "$local_mysql_backup_dir/$local_db/";
            $cmd = "$rsync_command $source $dest";
            run_db_sync_command($cmd,'Database dump copied to local server',$verbose);

            # Run MySQL update on local machine
            $cmd = "mysql --host=localhost --user=$user --password=$password -D $local_db < \"$local_sql_script\" 2>$mysql_error_log";
            run_db_sync_command($cmd,"Database $local_db restored on local server",$verbose);
            break;

        case 'up':
            # Run MySQL dump on local machine
            $cmd = "mysqldump --host=localhost --user=$user --password=$password $local_db 1>\"$local_sql_script\" 2>$mysql_error_log";
            run_db_sync_command($cmd,'Database backed up on local server',$verbose);

            # Copy SQL script
            $source = "$local_mysql_backup_dir/$local_db/db1.sql";
            $dest = "$cpanel_user@$main_domain:/home/$cpanel_user/mysql_backup/$online_db/";
            $cmd = "$rsync_command $source $dest";
            run_db_sync_command($cmd,'Database dump copied to online server',$verbose);

            # Run MySQL update on online server
            $cmd = "$ssh_command \"/home/$cpanel_user/common_bash/run_mysql $main_domain $user $password $online_db db1\"";
            run_db_sync_command($cmd,'Database restored on online server',$verbose);
            break;
    }
}


//==============================================================================
define( 'MYSQL_SYNC_FUNCT_DEFINED' , true );
endif;
//==============================================================================
