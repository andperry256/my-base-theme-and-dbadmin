<?php
//==============================================================================

if (!defined('DEFAULT_CHARSET'))
{
    define ('DEFAULT_CHARSET','utf8');
}
if (!defined('DEFAULT_COLLATION'))
{
    define ('DEFAULT_COLLATION','utf8_general_ci');
}
if (!defined('DEFAULT_ENGINE'))
{
    define ('DEFAULT_ENGINE','InnoDB');
}

//==============================================================================
if (!function_exists('update_table_data')):
//==============================================================================
/*
Function update_table_data
*/
//==============================================================================

function update_table_data($update_charsets=false,$optimise=false,$purge=false)
{
    update_table_data_main('',$update_charsets,$optimise,$purge);
}

function update_table_data_with_dbid($dbid,$update_charsets=false,$optimise=false,$purge=false)
{
    update_table_data_main($dbid,$update_charsets,$optimise,$purge);
}

function update_table_data_main($dbid,$update_charsets,$optimise,$purge)
{
    global $custom_pages_path, $relative_path, $alt_include_path;
    global $widget_types;
    global $argc;
    global $dbinfo, $location;
    if (isset($argc))
    {
        $mode = 'command';
        $eol = "\n";
        $ltag = '[';
        $rtag = ']';
        $nbsp = ' ';
    }
    else
    {
        $mode = 'web';
        $eol = "<br />\n";
        $ltag = '<em>';
        $rtag = '</em>';
        $nbsp = '&nbsp;';
    }
    if (!isset($widget_types))
    {
        exit("ERROR - Attempt to run script out of context.\n");
    }

    print($eol);
    print("Processing database at relative path [$relative_path] ...$eol");
    if ($update_charsets)
    {
        print("[Updating of charsets/collation included]$eol");
    }
    if ($optimise)
    {
        print("[Optimising of tables included]$eol");
    }
    if ($purge)
    {
        print("[Purging of dynamic views included]$eol");
    }
    $default_engine = DEFAULT_ENGINE;
    $default_charset = DEFAULT_CHARSET;
    $default_collation = DEFAULT_COLLATION;
    if (!empty($dbid))
    {
        if (function_exists('db_full_connect'))
        {
            $db = db_full_connect($dbid);
        }
        else
        {
            $db = db_connect($dbid);
        }
        if ($location == 'local')
        {
            $dbname = $dbinfo[$dbid][0];
        }
        else
        {
            $dbname = $dbinfo[$dbid][1];
        }
    }
    else
    {
        $db = admin_db_connect();
        $dbname = admin_db_name();
    }
    if (!$db)
    {
        print("<p>Failed to connect to database</p>");
        return;
    }

    if ($update_charsets)
    {
        if (mysqli_query_normal($db,"ALTER DATABASE `$dbname` CHARACTER SET $default_charset COLLATE $default_collation") === false)
        {
            print("Unable to update default collation for database$eol");
        }
    }
    $access_types = "'read-only','edit','auto-edit','full','auto-full'";
    $default_access_type = 'full';
    $widget_type_list = '';
    foreach ($widget_types as $key => $value)
    {
        $widget_type_list .= "'$key',";
    }
    $widget_type_list = rtrim($widget_type_list,',');
    $default_widget_type = 'input-text';

    if (mysqli_num_rows(mysqli_query_normal($db,"SHOW FULL TABLES FROM `$dbname` WHERE `Tables_in_$dbname`='dba_table_info'")) == 0)
    {
        // Create tables and mark as a new installation
        $new_installation = true;
        mysqli_query_normal($db,"CREATE TABLE `dba_table_info` ( `table_name` varchar(63) COLLATE $default_collation NOT NULL, PRIMARY KEY (`table_name`) ) ENGINE=$default_engine DEFAULT CHARSET=$default_charset COLLATE=$default_collation");
        mysqli_query_normal($db,"CREATE TABLE `dba_table_fields` ( `table_name` varchar(63) COLLATE $default_collation NOT NULL, PRIMARY KEY (`table_name`) ) ENGINE=$default_engine DEFAULT CHARSET=$default_charset COLLATE=$default_collation");
        mysqli_query_normal($db,"CREATE TABLE `dba_sidebar_config` ( `display_order` INT(11) COLLATE $default_collation NOT NULL, PRIMARY KEY (`display_order`) ) ENGINE=$default_engine DEFAULT CHARSET=$default_charset COLLATE=$default_collation");
    }
    else
    {
        $new_installation = false;
    }

    // These tables are checked regardless of whether it is a new installation
    // as they were added after the original version of DBAdmin.
    if (mysqli_num_rows(mysqli_query_normal($db,"SHOW FULL TABLES FROM `$dbname` WHERE `Tables_in_$dbname`='dba_change_log'")) == 0)
    {
        mysqli_query_normal($db,"CREATE TABLE `dba_change_log` ( `seq_no` INT(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY (`seq_no`) ) ENGINE=$default_engine DEFAULT CHARSET=$default_charset COLLATE=$default_collation");
    }
    if (mysqli_num_rows(mysqli_query_normal($db,"SHOW FULL TABLES FROM `$dbname` WHERE `Tables_in_$dbname`='dba_relationships'")) == 0)
    {
        mysqli_query_normal($db,"CREATE TABLE `dba_relationships` ( `table_name` varchar(63) COLLATE $default_collation NOT NULL, PRIMARY KEY (`table_name`) ) ENGINE=$default_engine DEFAULT CHARSET=$default_charset COLLATE=$default_collation");
    }

    // Run the following queries to create/update the structure for the table info table
    $fieldlist = array();
    $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM dba_table_info");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $fieldlist[$row['Field']] = true;
    }
    if (isset($fieldlist['auto_dump']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_info` DROP `auto_dump`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_info` CHANGE `table_name` `table_name` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL");
    if (!isset($fieldlist['parent_table']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_info` ADD `parent_table` VARCHAR( 63 ) NULL AFTER `table_name`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_info` CHANGE `parent_table` `parent_table` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
    if (!isset($fieldlist['real_access']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_info` ADD `local_access` ENUM( $access_types ) NOT NULL DEFAULT '$default_access_type' AFTER `parent_table`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_info` CHANGE `local_access` `local_access` ENUM( $access_types ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL DEFAULT '$default_access_type'");
    if (!isset($fieldlist['real_access']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_info` ADD `real_access` ENUM( $access_types ) NOT NULL DEFAULT '$default_access_type' AFTER `local_access`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_info` CHANGE `real_access` `real_access` ENUM( $access_types ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL DEFAULT '$default_access_type'");
    if (!isset($fieldlist['list_size']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_info` ADD `list_size` INT NOT NULL DEFAULT '100' AFTER `real_access`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_info` CHANGE `list_size` `list_size` INT( 11 ) NOT NULL DEFAULT '100'");
    if (!isset($fieldlist['sort_1_field']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_info` ADD `sort_1_field` VARCHAR( 63 ) NULL AFTER `list_size`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_info` CHANGE `sort_1_field` `sort_1_field` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
    if (!isset($fieldlist['seq_no_field']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_info` ADD `seq_no_field` VARCHAR( 63 ) NULL AFTER `sort_1_field`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_info` CHANGE `seq_no_field` `seq_no_field` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
    if (!isset($fieldlist['seq_method']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_info` ADD `seq_method` ENUM( 'continuous', 'repeat' ) NOT NULL DEFAULT 'continuous' AFTER `seq_no_field`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_info` CHANGE `seq_method` `seq_method` ENUM( 'continuous', 'repeat' ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL DEFAULT 'continuous'");
    if (!isset($fieldlist['renumber_enabled']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_info` ADD `renumber_enabled` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `seq_method`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_info` CHANGE `renumber_enabled` `renumber_enabled` TINYINT( 1 ) NOT NULL DEFAULT '0'");
    if (!isset($fieldlist['alt_field_order']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_info` ADD `alt_field_order` VARCHAR( 127 ) NULL AFTER `renumber_enabled`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_info` CHANGE `alt_field_order` `alt_field_order` VARCHAR( 127 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
    if (!isset($fieldlist['engine']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_info` ADD `engine` ENUM( 'InnoDB', 'MyISAM' ) NOT NULL DEFAULT '$default_engine' AFTER `alt_field_order`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_info` CHANGE `engine` `engine` ENUM( 'InnoDB', 'MyISAM' ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL DEFAULT '$default_engine'");
    if (!isset($fieldlist['character_set']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_info` ADD `character_set` VARCHAR( 15 ) NULL AFTER `engine`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_info` CHANGE `character_set` `character_set` VARCHAR( 15 ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL DEFAULT '-auto-'");
    mysqli_query_normal($db,"UPDATE dba_table_info SET character_set='-auto-' WHERE character_set='' OR character_set IS NULL");
    if (!isset($fieldlist['collation']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_info` ADD `collation` VARCHAR( 31 ) NULL AFTER `character_set`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_info` CHANGE `collation` `collation` VARCHAR( 31 ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL DEFAULT '-auto-'");
    mysqli_query_normal($db,"UPDATE dba_table_info SET collation='-auto-' WHERE collation='' OR collation IS NULL");
    if (!isset($fieldlist['grid_columns']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_info` ADD `grid_columns` VARCHAR( 31 ) NOT NULL DEFAULT '1.5em 1fr' AFTER `collation`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_info` CHANGE `grid_columns` `grid_columns` VARCHAR( 31 ) NOT NULL DEFAULT '1.5em 1fr'");
    if (!isset($fieldlist['replicate_enabled']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_info` ADD `replicate_enabled` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `grid_columns`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_info` CHANGE `replicate_enabled` `replicate_enabled` TINYINT( 1 ) NOT NULL DEFAULT '0'");
    if (!isset($fieldlist['orphan']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_info` ADD `orphan` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `replicate_enabled`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_info` CHANGE `orphan` `orphan` TINYINT( 1 ) NOT NULL DEFAULT '0'");
    if ($new_installation)
    {
        print("This is a first time installation - please return to the main page (to do any auto view creation) and then repeat this operation.$eol");
        return;
    }

    // Run the following queries to create/update the structure for the table fields table.
    $fieldlist = array();
    $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM dba_table_fields");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $fieldlist[$row['Field']] = true;
    }
    if (isset($fieldlist['parent_table']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` DROP `parent_table`");
    }
    if (isset($fieldlist['default_widget_type']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` DROP `default_widget_type`");
    }
    if (isset($fieldlist['custom_widget_type']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` DROP `custom_widget_type`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` CHANGE `table_name` `table_name` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL");
    if (!isset($fieldlist['field_name']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` ADD `field_name` VARCHAR( 63 ) NOT NULL AFTER `table_name`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` CHANGE `field_name` `field_name` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL");
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` DROP PRIMARY KEY, ADD PRIMARY KEY( `table_name`, `field_name`)");
    if (!isset($fieldlist['is_primary']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` ADD `is_primary` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `field_name`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` CHANGE `is_primary` `is_primary` TINYINT( 1 ) NOT NULL DEFAULT '0'");
    if (!isset($fieldlist['required']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` ADD `required` INT( 11 ) NOT NULL DEFAULT '0' AFTER `is_primary`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` CHANGE `required` `required` Int( 11 ) NOT NULL DEFAULT '0'");
    if (!isset($fieldlist['alt_label']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` ADD `alt_label` VARCHAR( 63 ) NULL AFTER `required`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` CHANGE `alt_label` `alt_label` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
    if (!isset($fieldlist['widget_type']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` ADD `widget_type` ENUM( $widget_type_list ) NOT NULL DEFAULT '$default_widget_type' AFTER `alt_label`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` CHANGE `widget_type` `widget_type` ENUM( $widget_type_list ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL DEFAULT '$default_widget_type'");
    if (!isset($fieldlist['description']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` ADD `description` VARCHAR( 511 ) NULL AFTER `widget_type`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` CHANGE `description` `description` VARCHAR( 511 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
    if (!isset($fieldlist['vocab_table']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` ADD `vocab_table` VARCHAR( 63 ) NULL AFTER `description`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` CHANGE `vocab_table` `vocab_table` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
    if (!isset($fieldlist['vocab_field']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` ADD `vocab_field` VARCHAR( 63 ) NULL AFTER `vocab_table`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` CHANGE `vocab_field` `vocab_field` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
    if (!isset($fieldlist['list_desktop']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` ADD `list_desktop` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `vocab_field`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` CHANGE `list_desktop` `list_desktop` TINYINT( 1 ) NOT NULL DEFAULT '0'");
    if (!isset($fieldlist['list_mobile']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` ADD `list_mobile` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `list_desktop`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` CHANGE `list_mobile` `list_mobile` TINYINT( 1 ) NOT NULL DEFAULT '0'");
    if (!isset($fieldlist['display_group']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` ADD `display_group` VARCHAR( 31 ) NOT NULL DEFAULT '-default-' AFTER `list_mobile`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` CHANGE `display_group` `display_group` VARCHAR( 31 ) NOT NULL DEFAULT '-default-'");
    mysqli_query_normal($db,"UPDATE `dba_table_fields` SET `display_group`='-default-' WHERE display_group='0' OR display_group='' OR display_group IS NULL");
    if (!isset($fieldlist['display_order']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` ADD `display_order` INT( 11 ) NOT NULL DEFAULT '0' AFTER `display_group`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` CHANGE `display_order` `display_order` INT( 11 ) NOT NULL DEFAULT '0'");
    if (!isset($fieldlist['grid_coords']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` ADD `grid_coords` VARCHAR( 15 ) NOT NULL DEFAULT 'auto' AFTER `display_order`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` CHANGE `grid_coords` `grid_coords` VARCHAR( 15 ) NOT NULL DEFAULT 'auto'");
    if (!isset($fieldlist['relative_path']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` ADD `relative_path` VARCHAR( 63 ) NULL AFTER `grid_position`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` CHANGE `relative_path` `relative_path` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
    if (!isset($fieldlist['allowed_filetypes']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` ADD `allowed_filetypes` VARCHAR( 63 ) NULL AFTER `relative_path`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` CHANGE `allowed_filetypes` `allowed_filetypes` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
    if (!isset($fieldlist['exclude_from_search']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` ADD `exclude_from_search` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `allowed_filetypes`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` CHANGE `exclude_from_search` `exclude_from_search` TINYINT( 1 ) NOT NULL DEFAULT '0'");
    if (!isset($fieldlist['orphan']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` ADD `orphan` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `exclude_from_search`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_table_fields` CHANGE `orphan` `orphan` TINYINT( 1 ) NOT NULL DEFAULT '0'");

    // Run the following queries to create/update the structure for the sidebar configuration table.
    $fieldlist = array();
    $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM dba_sidebar_config");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $fieldlist[$row['Field']] = true;
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_sidebar_config` CHANGE `display_order` `display_order` INT( 11 ) NOT NULL DEFAULT '9999'");
    if (!isset($fieldlist['label']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_sidebar_config` ADD `label` VARCHAR( 31 ) NOT NULL AFTER `display_order`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_sidebar_config` CHANGE `label` `label` VARCHAR( 31 ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL");
    if (!isset($fieldlist['action_name']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_sidebar_config` ADD `action_name` VARCHAR( 63 ) NULL AFTER `label`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_sidebar_config` CHANGE `action_name` `action_name` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
    if (!isset($fieldlist['table_name']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_sidebar_config` ADD `table_name` VARCHAR( 63 ) NULL AFTER `action_name`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_sidebar_config` CHANGE `table_name` `table_name` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
    if (!isset($fieldlist['link']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_sidebar_config` ADD `link` VARCHAR( 63 ) NULL AFTER `table_name`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_sidebar_config` CHANGE `link` `link` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
    if (!isset($fieldlist['new_window']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_sidebar_config` ADD `new_window` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `link`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_sidebar_config` CHANGE `new_window` `new_window` TINYINT( 1 ) NOT NULL DEFAULT '0'");

    // Run the following queries to create/update the structure for the relationships table.
    $fieldlist = array();
    $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM dba_relationships");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $fieldlist[$row['Field']] = true;
    }
    if (!isset($fieldlist['relationship_name']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_relationships` ADD `relationship_name` VARCHAR( 63 ) NOT NULL AFTER `table_name`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_relationships` CHANGE `relationship_name` `relationship_name` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL");
    mysqli_query_normal($db,"ALTER TABLE `dba_relationships` DROP PRIMARY KEY, ADD PRIMARY KEY( `table_name`, `relationship_name`)");
    if (!isset($fieldlist['query']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_relationships` ADD `query` VARCHAR( 255 ) NOT NULL AFTER `relationship_name`");
    }
    mysqli_query_normal($db,"ALTER TABLE `dba_relationships` CHANGE `query` `query` VARCHAR( 255 ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL");

    // Run the following queries to create/update the structure for the change log table.
    $fieldlist = array();
    $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM dba_change_log");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $fieldlist[$row['Field']] = true;
    }
    if (!isset($fieldlist['date_and_time']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_change_log` ADD `date_and_time` CHAR( 19 ) NOT NULL AFTER `seq_no`");
    }
    if (!isset($fieldlist['table_name']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_change_log` ADD `table_name` VARCHAR( 63 ) NOT NULL AFTER `date_and_time`");
    }
    if (!isset($fieldlist['action']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_change_log` ADD `action` ENUM( 'New','Edit','Delete' ) NOT NULL AFTER `table_name`");
    }
    if (!isset($fieldlist['record_id']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_change_log` ADD `record_id` VARCHAR( 511 ) NOT NULL AFTER `action`");
    }
    if (!isset($fieldlist['details']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_change_log` ADD `details` MEDIUMTEXT NULL AFTER `record_id`");
    }
    if (!isset($fieldlist['delete_record']))
    {
        mysqli_query_normal($db,"ALTER TABLE `dba_change_log` ADD `delete_record` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `details`");
    }

    /*
    Create views for displaying orphan records. Do not use the 'create_view_structure'
    function, as the child class definitions are pre-defined in classes.php.
    Set all orphan flags to 1 by default. The main loop below will then reset the
    flags to 0 for those tables/views which exist in the database.
    */
    mysqli_query_normal($db,"CREATE OR REPLACE VIEW _view_orphan_table_info_records AS SELECT * FROM dba_table_info WHERE orphan=1");
    mysqli_query_normal($db,"CREATE OR REPLACE VIEW _view_orphan_table_field_records AS SELECT * FROM dba_table_fields WHERE orphan=1");
    $fields = 'table_name,parent_table';
    $values = array('s','_view_orphan_table_info_records','s','dba_table_info');
    $where_clause = "table_name='_view_orphan_table_info_records'";
    mysqli_conditional_insert_query($db,'dba_table_info',$fields,$values,$where_clause,array());
    $fields = 'table_name,parent_table';
    $values = array('s','_view_orphan_table_field_records','s','dba_table_fields');
    $where_clause = "table_name='_view_orphan_table_field_records'";
    mysqli_conditional_insert_query($db,'dba_table_info',$fields,$values,$where_clause,array());
    mysqli_query_normal($db,"UPDATE dba_table_info SET orphan=1");
    mysqli_query_normal($db,"UPDATE dba_table_fields SET orphan=1");


    $table_field = "Tables_in_$dbname";
    $query_result = mysqli_query_normal($db,"SHOW FULL TABLES FROM `$dbname` WHERE `$table_field` LIKE 'dataface__%'");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $table = $row[$table_field];
        mysqli_query_normal($db,"DROP TABLE $table");
    }

    // Main loop to process all tables in the database
    $query_result = mysqli_query_normal($db,"SHOW FULL TABLES FROM `$dbname` WHERE `$table_field` NOT LIKE 'dataface__%'");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $table = $row[$table_field];
        if (($purge) && (substr($table,0,6) == '_view_'))
        {
            print ("Purging view $ltag$table$rtag ...$eol");
            $where_clause = 'table_name=?';
            $where_values = array('s',$table);
            if (($query_result2 = mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,'')) &&
                ($row2 = mysqli_fetch_assoc($query_result2)) &&
                (!empty($row2['parent_table'])))
            {
                delete_view_structure($table,$row2['parent_table']);
            }
            else
            {
                mysqli_query_normal($db,"DROP VIEW $table");
            }
        }
        else
        {
            print("Processing");
            if ($row['Table_type'] == 'VIEW')
            {
                print(" view");
            }
            else
            {
                print(" table");
            }
            print(" $ltag$table$rtag ...$eol");
    
            try { mysqli_query($db,"SHOW COLUMNS FROM $table"); }
            catch (Exception $e)
            {
                /*
                This should not normally occur but may do so if there is an old view
                present in the database that no longer relates to valid data.
                */
                mysqli_query_normal($db,"DROP VIEW IF EXISTS $table");
                exit("ERROR - ".$e->getMessage().$eol);
            }
            if ($row['Table_type'] != 'VIEW')
            {
                // Set the table to the required character set and collation if required
                if ($update_charsets)
                {
                    $charset = $default_charset;
                    $collation = $default_collation;
                    $engine = $default_engine;
                    $where_clause = 'table_name=?';
                    $where_values = array('s',$table);
                    $query_result2 = mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,'');
                    if ($row2 = mysqli_fetch_assoc($query_result2))
                    {
                        if (!empty($row2['engine']))
                        {
                            $engine = $row2['engine'];
                        }
                        if ((!empty($row2['character_set'])) && ($row2['character_set'] != '-auto-'))
                        {
                            $charset = $row2['character_set'];
                        }
                        if ((!empty($row2['collation'])) && ($row2['collation'] != '-auto-'))
                        {
                            $collation = $row2['collation'];
                        }
                    }
                    if (mysqli_query_normal($db,"ALTER TABLE $table CONVERT TO CHARACTER SET $charset COLLATE $collation") === false)
                    {
                        print("--Unable to update charset/collation for table $table$eol");
                    }
                    if (mysqli_query_normal($db,"ALTER TABLE $table ENGINE=$engine") === false)
                    {
                        print("--Unable to update storage engine for table $table$eol");
                    }
                }
        
                // Optimise the table if required
                if ($optimise)
                {
                    if (mysqli_query_normal($db,"OPTIMIZE TABLE $table") === false)
                    {
                        print("--Unable to optimise table $table$eol");
                    }
                }
            }
    
            $table = $row[$table_field];
            mysqli_query_normal($db,"UPDATE dba_table_info SET orphan=0 WHERE table_name='$table'");
            mysqli_query_normal($db,"UPDATE dba_table_fields SET orphan=0 WHERE table_name='$table'");
            if ($table == get_base_table($table,$db))
            {
                if ((is_dir("$custom_pages_path/$relative_path/tables/$table")) ||
                (is_dir("$alt_include_path/tables/$table")) ||
                (substr($table,0,4) == 'dba_'))
                {
                    $fields = 'table_name';
                    $values = array('s',$table);
                    $where_clause = "table_name=?";
                    $where_values = array('s',$table);
                    mysqli_conditional_insert_query($db,'dba_table_info',$fields,$values,$where_clause,$where_values);
                    $last_display_order = 0;
        
                    // Loop through the table fields
                    $field_list = array();
                    if ($query_result2 = mysqli_query_normal($db,"SHOW COLUMNS FROM $table"))
                    {
                        while ($row2 = mysqli_fetch_assoc($query_result2))
                        {
                            $field_name = $row2['Field'];
                            $field_list[$field_name] = true;
                            $field_type = strtok($row2['Type'],'(');
                            $field_size = strtok(')');
                            if ($row2['Key'] == 'PRI')
                            {
                                $is_primary = 1;
                                $required = 2;  // Value required
                            }
                            else
                            {
                                $is_primary = 0;
                                if ($row2['Null'] == 'NO')
                                {
                                    // Require value by default. Can later call the
                                    // enable_non_null_empty function to override this.
                                    $required = 2;
                                }
                                else
                                {
                                    $required = 0;  //Can be null
                                }
                            }
                            switch ($field_type)
                            {
                                case 'date':
                                $default_widget_type = 'date';
                                break;
                
                                case 'time':
                                $default_widget_type = 'time';
                                break;
                
                                case 'varchar';
                                case 'char';
                                if ($field_size >= 200)
                                {
                                    $default_widget_type = 'textarea';
                                }
                                else
                                {
                                    $default_widget_type = 'input-text';
                                }
                                break;
                
                                case 'int':
                                case 'decimal':
                                if ($row2['Extra'] == 'auto_increment')
                                {
                                    $default_widget_type = 'auto-increment';
                                }
                                else
                                {
                                    $default_widget_type = 'input-num';
                                }
                                if ($required == 1)
                                {
                                    $required = 2;
                                }
                                break;
                
                                case 'tinyint':
                                $default_widget_type = 'checkbox';
                                if ($required == 1)
                                {
                                    $required = 2;
                                }
                                break;
                
                                case 'enum':
                                $enum_select_list = $field_size;
                                $field_size ='';
                                $default_widget_type = 'enum';
                                if ($required == 1)
                                {
                                    $required = 2;
                                }
                                break;
                
                                default:
                                $default_widget_type = 'input-text';
                                break;
                            }
            
                            // Run query to select data for the given table & field
                            $where_clause = 'table_name=? AND field_name=?';
                            $where_values = array('s',$table,'s',$field_name);
                            $query_result3 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
                            if ($row3 = mysqli_fetch_assoc($query_result3))
                            {
                                /*
                                Record already found.  Enforce the following constraints:-
                                1. Ensure that the 'is primary' and 'required' fields reflect
                                the current table definition and that they are set as static
                                widgets.
                                2. Set the widget type for a nnumeric primary key to 'input-num'.
                                3. Set the widget type to 'date' for any date field (unless already set to 'static-date').
                                4. Set the widget type to 'enum' for any enum field.
                                5. Set the widget type to 'auto-increment' for any auto-increment field.
                                */
                                if ($row3['is_primary'])
                                {
                                    /*
                                    If the field is already set to primary then do not reset this
                                    as it may have been set manually (normally in the case of a view
                                    where primary key status does not occur naturally).
                                    */
                                    $is_primary = 1;
                                    $required = 2;
                                }
                                mysqli_query_normal($db,"UPDATE dba_table_fields SET is_primary=$is_primary,required=$required WHERE table_name='$table' AND field_name='$field_name'");
                                if ($is_primary)
                                {
                                    if ($row2['Extra'] == 'auto_increment')
                                    {
                                        mysqli_query_normal($db,"UPDATE dba_table_fields SET widget_type='auto-increment' WHERE table_name='$table' AND field_name='$field_name'");
                                    }
                                    elseif ($field_type == 'int')
                                    {
                                        mysqli_query_normal($db,"UPDATE dba_table_fields SET widget_type='input-num' WHERE table_name='$table' AND field_name='$field_name'");
                                    }
                                }
                                if ($default_widget_type == 'date')
                                {
                                    mysqli_query_normal($db,"UPDATE dba_table_fields SET widget_type='date' WHERE table_name='$table' AND field_name='$field_name' AND widget_type<>'static-date'");
                                }
                                if ($default_widget_type == 'enum')
                                {
                                    mysqli_query_normal($db,"UPDATE dba_table_fields SET widget_type='enum' WHERE table_name='$table' AND field_name='$field_name'");
                                }
                                $last_display_order = $row3['display_order'];
                            }
                            else
                            {
                                /*
                                Add new record into the table fields table.
                                A simplified method is currently used to determine the required value
                                for the display order. A value of 10 is added to that of the previous
                                record unless this clashes with an existing record in which case a
                                value of 5 is added.
                                */
                                $next_display_order = $last_display_order + 10;
                                $where_clause = 'table_name=? AND display_order=?';
                                $where_values = array('s',$table,'i',$next_display_order);
                                $query_result4 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
                                if (mysqli_num_rows($query_result4) == 0)
                                {
                                    $display_order = $last_display_order + 10;
                                }
                                else
                                {
                                    $display_order = $last_display_order + 5;
                                }
                                $last_display_order = $display_order;
                
                                // Insert record.
                                // N.B. Set the 'list desktop' and 'list mobile' fields by default on
                                // primary key fields only.
                                $where_clause = 'table_name=? AND field_name=?';
                                $where_values = array('s',$table,'s',$field_name);
                                $fields = 'table_name,field_name,is_primary,required,widget_type,list_desktop,list_mobile,display_order';
                                $values = array('s',$table,'s',$field_name,'i',$is_primary,'i',$required,'s',$default_widget_type,'i',$is_primary,'i',$is_primary,'i',$display_order);
                                if (mysqli_conditional_insert_query($db,'dba_table_fields',$fields,$values,$where_clause,$where_values) === true);
                                {
                                    print("$nbsp$nbsp$nbsp"."Field $ltag$field_name$rtag added$eol");
                                }
                            }
                        }
                    }
        
                    // Delete redundant table field records
                    $where_clause = 'table_name=?';
                    $where_values = array('s',$table);
                    $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
                    while ($row2 = mysqli_fetch_assoc($query_result2))
                    {
                        $field_name = $row2['field_name'];
                        if (!isset($field_list[$field_name]))
                        {
                            $where_clause = 'table_name=? AND field_name=?';
                            $where_values = array('s',$table,'s',$field_name);
                            mysqli_delete_query($db,'dba_table_fields',$where_clause,$where_values);
                            print("$nbsp$nbsp$nbsp"."Field $ltag$field_name$rtag removed$eol");
                        }
                    }
        
                    /*
                    Force certain widgets to static within the dba_table_fields table iteslf.
                    Although the 'table_name' field should not be editable, allow this to be
                    a 'select' widget in order to allow it to be selected in a copy operation.
                    */
                    mysqli_query_normal($db,"UPDATE dba_table_fields SET widget_type='select',vocab_table='dba_table_info',vocab_field='table_name' WHERE table_name='dba_table_fields' AND field_name='table_name'");
                    mysqli_query_normal($db,"UPDATE dba_table_fields SET widget_type='static' WHERE table_name='dba_table_fields' AND (field_name='field_name' OR field_name='is_primary' OR field_name='required')");
                    mysqli_query_normal($db,"UPDATE dba_table_fields SET widget_type='static' WHERE table_name='dba_change_log' AND field_name<>'delete_record'");
                }
            }
        }
    }  // End of loop through tables

    // Make a number of standard settings to enable certain non primary key fields
    // to be displayed by default in a table listing.
    mysqli_query_normal($db,"UPDATE dba_table_fields SET list_desktop=1,list_mobile=1 WHERE table_name='dba_sidebar_config' AND field_name='label'");
    mysqli_query_normal($db,"UPDATE dba_table_fields SET list_desktop=1,list_mobile=1 WHERE table_name='dba_table_info' AND field_name='parent_table'");
    mysqli_query_normal($db,"UPDATE dba_table_fields SET list_desktop=1,list_mobile=1 WHERE table_name='dba_table_info' AND field_name='grid_columns'");
    mysqli_query_normal($db,"UPDATE dba_table_fields SET list_desktop=1,list_mobile=1 WHERE table_name='dba_table_fields' AND field_name='display_group'");
    mysqli_query_normal($db,"UPDATE dba_table_fields SET list_desktop=1,list_mobile=1 WHERE table_name='dba_table_fields' AND field_name='display_order'");
    mysqli_query_normal($db,"UPDATE dba_table_fields SET list_desktop=1,list_mobile=1 WHERE table_name='dba_table_fields' AND field_name='grid_coords'");
    mysqli_query_normal($db,"UPDATE dba_table_fields SET list_desktop=1,list_mobile=0 WHERE table_name='dba_relationships' AND field_name='query'");
    mysqli_query_normal($db,"UPDATE dba_table_fields SET list_desktop=1,list_mobile=1 WHERE table_name='dba_change_log' AND field_name='date_and_time'");
    mysqli_query_normal($db,"UPDATE dba_table_fields SET list_desktop=1,list_mobile=1 WHERE table_name='dba_change_log' AND field_name='table_name'");
    mysqli_query_normal($db,"UPDATE dba_table_fields SET list_desktop=1,list_mobile=1 WHERE table_name='dba_change_log' AND field_name='action'");
    mysqli_query_normal($db,"UPDATE dba_table_fields SET list_desktop=1,list_mobile=1 WHERE table_name='dba_change_log' AND field_name='record_id'");

    // Set sequencing info for built-in tables
    mysqli_query_normal($db,"UPDATE dba_table_info SET sort_1_field='table_name',seq_no_field='display_order',seq_method='repeat',renumber_enabled=1 WHERE table_name='dba_table_fields'");
    mysqli_query_normal($db,"UPDATE dba_table_info SET sort_1_field='',seq_no_field='display_order',seq_method='continuous',renumber_enabled=1 WHERE table_name='dba_sidebar_config'");

    // Set miscellaneous field descriptions for built-in tables
    mysqli_query_normal($db,"UPDATE dba_table_fields SET description='Alternate field order for sorting records when creating <em>Previous</em> and <em>Next</em> links. Comma separated list of field names.' WHERE table_name='dba_table_info' AND field_name='alt_field_order'");
    mysqli_query_normal($db,"UPDATE dba_table_fields SET description='Character set to be applied to the table. Set to <i>-auto-</i> to use default.' WHERE table_name='dba_table_info' AND field_name='character_set'");
    mysqli_query_normal($db,"UPDATE dba_table_fields SET description='Collation to be applied to the table. Set to <i>-auto-</i> to use default.' WHERE table_name='dba_table_info' AND field_name='collation'");
    mysqli_query_normal($db,"UPDATE dba_table_fields SET description='CSS grid column widths for mobile mode. Do NOT use the <em>repeat</em> construct.' WHERE table_name='dba_table_info' AND field_name='grid_columns'");
    mysqli_query_normal($db,"UPDATE dba_table_fields SET description='0 = can be null; 1 = can be empty; 2 = value required.' WHERE table_name='dba_table_fields' AND field_name='required'");
    mysqli_query_normal($db,"UPDATE dba_table_fields SET alt_label='Grid Co-ordinates' WHERE table_name='dba_table_fields' AND field_name='grid_coords'");
    mysqli_query_normal($db,"UPDATE dba_table_fields SET alt_label='Date & Time' WHERE table_name='dba_change_log' AND field_name='date_and_time'");
    mysqli_query_normal($db,"UPDATE dba_table_fields SET alt_label='Record ID' WHERE table_name='dba_change_log' AND field_name='record_id'");
    $query = "UPDATE dba_table_fields SET description='";
    $query .= "In format <em>row/column/span</em><br />";
    $query .= "Column is optional and defaults to 2; span is optional and defaults to 1.<br />";
    $query .= "Set all field records for a given table to <em>auto</em> to format as one field per row.";
    $query .= "' WHERE table_name='dba_table_fields' AND field_name='grid_coords'";
    mysqli_query_normal($db,$query);
    mysqli_query_normal($db,"UPDATE dba_table_fields SET description='Set flag to delete record on save.' WHERE table_name='dba_change_log' AND field_name='delete_record'");

    // Set other misceallaneous fields for built-in-tables
    mysqli_query_normal($db,"UPDATE dba_table_fields SET widget_type='select',vocab_table='dba_table_info',vocab_field='table_name' WHERE table_name='dba_relationships' AND field_name='table_name'");

    // Add/re-create relationships for built-in tables
    // The queries are built the way they are due to problems with syntax highlighting in Atom
    $where_clause = "table_name LIKE 'dba_%'";
    $where_values = array();
    mysqli_delete_query($db,'dba_relationships',$where_clause,$where_values);
    $query = "INSERT INTO dba_relationships VALUES ('dba_table_info','Child Tables',\"{query}\")";
    $query = str_replace('{query}','SELECT * FROM dba_table_info WHERE parent_table=\'$table_name\'',$query);
    mysqli_query_normal($db,$query);
    $query = "INSERT INTO dba_relationships VALUES ('dba_table_info','Child Tables - Delete',\"{query}\")";
    $query = str_replace('{query}','# Do not include (too complex)',$query);
    mysqli_query_normal($db,$query);
    $query = "INSERT INTO dba_relationships VALUES ('dba_table_info','Child Tables - Update',\"{query}\")";
    $query = str_replace('{query}','UPDATE dba_table_info SET parent_table=\'$table_name WHERE parent_table=\'$$table_name\'',$query);
    mysqli_query_normal($db,$query);
    $query = "INSERT INTO dba_relationships VALUES ('dba_table_info','Fields',\"{query}\")";
    $query = str_replace('{query}','SELECT * FROM dba_table_fields WHERE table_name=\'$table_name\'',$query);
    mysqli_query_normal($db,$query);
    $query = "INSERT INTO dba_relationships VALUES ('dba_table_info','Fields - Delete',\"{query}\")";
    $query = str_replace('{query}','DELETE FROM dba_table_fields WHERE table_name=\'$table_name\'',$query);
    mysqli_query_normal($db,$query);
    $query = "INSERT INTO dba_relationships VALUES ('dba_table_info','Fields - Update',\"{query}\")";
    $query = str_replace('{query}','UPDATE dba_table_fields SET table_name=\'$table_name\' WHERE table_name=\'$$table_name\'',$query);
    mysqli_query_normal($db,$query);
    $query = "INSERT INTO dba_relationships VALUES ('dba_table_info','Relationships',\"{query}\")";
    $query = str_replace('{query}','SELECT * FROM dba_relationships WHERE table_name=\'$table_name\'',$query);
    mysqli_query_normal($db,$query);
    $query = "INSERT INTO dba_relationships VALUES ('dba_table_info','Relationships - Delete',\"{query}\")";
    $query = str_replace('{query}','DELETE FROM dba_relationships WHERE table_name=\'$table_name\'',$query);
    mysqli_query_normal($db,$query);
    $query = "INSERT INTO dba_relationships VALUES ('dba_table_info','Relationships - Update',\"{query}\")";
    $query = str_replace('{query}','UPDATE dba_relationships SET table_name=\'$table_name\' WHERE table_name=\'$$table_name\'',$query);
    mysqli_query_normal($db,$query);

    $where_clause = 'orphan=1';
    $where_values = array();
    mysqli_delete_query($db,'dba_table_info',$where_clause,$where_values);
    mysqli_delete_query($db,'dba_table_fields',$where_clause,$where_values);
    print("Operation completed.$eol");
    if ((false) && ($mode == 'web'))  // Functionality deprecated
    {
        print("<p><a href=\"./?-table=_view_orphan_table_info_records\" target=\"_blank\">Orphan Table Info Records</a><br />\n");
        print("<a href=\"./?-table=_view_orphan_table_field_records\" target=\"_blank\">Orphan Table Field Records</a></p>\n");
    }
}

//==============================================================================
endif;
//==============================================================================
?>
