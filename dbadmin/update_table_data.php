<?php
//==============================================================================

define ('DEFAULT_CHARSET','utf8');
define ('DEFAULT_COLLATION','utf8_general_ci');
define ('DEFAULT_ENGINE','InnoDB');

if (!function_exists('update_table_data'))
{
//==============================================================================
/*
Function update_table_data
*/
//==============================================================================

function update_table_data()
{
  update_table_data_main('');
}

function update_table_data_with_dbid($dbid)
{
  update_table_data_main($dbid);
}

function update_table_data_main($dbid)
{
  global $CustomPagesPath, $RelativePath;
  global $WidgetTypes;
  $default_engine = DEFAULT_ENGINE;
  $default_charset = DEFAULT_CHARSET;
  $default_collation = DEFAULT_COLLATION;
  $db = admin_db_connect();
  if (!$db)
  {
    print("<p>Failed to connect to database</p>");
    return;
  }
  $dbname = admin_db_name();
  if (mysqli_query($db,"ALTER DATABASE `$dbname` CHARACTER SET $default_charset COLLATE $default_collation") === false)
  {
    print("Unable to update default collation for database<br />");
  }
  $access_types = "'read-only','edit','auto-edit','full','auto-full'";
  $default_access_type = 'full';
  $widget_types = '';
  foreach ($WidgetTypes as $key => $value)
  {
    $widget_types .= "'$key',";
  }
  $widget_types = rtrim($widget_types,',');
  $default_widget_type = 'input-text';

  // Run the following queries to create/update the structure for the table info table
  // Any queries to create existing fields will automatically fail.
  $query_result = mysqli_query($db,"SELECT * FROM dba_table_info");
  if (!$query_result)
  {
    $new_installation = true;
    mysqli_query($db,"CREATE TABLE `dba_table_info` ( `table_name` varchar(63) COLLATE $default_collation NOT NULL, PRIMARY KEY (`table_name`) ) ENGINE=$default_engine DEFAULT CHARSET=$default_charset COLLATE=$default_collation");
  }
  else
  {
    $new_installation = false;
  }
  mysqli_query($db,"ALTER TABLE `dba_table_info` CHANGE `table_name` `table_name` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL");
  mysqli_query($db,"ALTER TABLE `dba_table_info` ADD `parent_table` VARCHAR( 63 ) NULL AFTER `table_name`");
  mysqli_query($db,"ALTER TABLE `dba_table_info` CHANGE `parent_table` `parent_table` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
  mysqli_query($db,"ALTER TABLE `dba_table_info` ADD `local_access` ENUM( $access_types ) NOT NULL DEFAULT '$default_access_type' AFTER `parent_table`");
  mysqli_query($db,"ALTER TABLE `dba_table_info` CHANGE `local_access` `local_access` ENUM( $access_types ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL DEFAULT '$default_access_type'");
  mysqli_query($db,"ALTER TABLE `dba_table_info` ADD `real_access` ENUM( $access_types ) NOT NULL DEFAULT '$default_access_type' AFTER `local_access`");
  mysqli_query($db,"ALTER TABLE `dba_table_info` CHANGE `real_access` `real_access` ENUM( $access_types ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL DEFAULT '$default_access_type'");
  mysqli_query($db,"ALTER TABLE `dba_table_info` ADD `list_size` INT NOT NULL DEFAULT '100' AFTER `real_access`");
  mysqli_query($db,"ALTER TABLE `dba_table_info` CHANGE `list_size` `list_size` INT( 11 ) NOT NULL DEFAULT '100'");
  mysqli_query($db,"ALTER TABLE `dba_table_info` ADD `auto_dump` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `list_size`");
  mysqli_query($db,"ALTER TABLE `dba_table_info` CHANGE `auto_dump` `auto_dump` TINYINT( 1 ) NOT NULL DEFAULT '0'");
  mysqli_query($db,"ALTER TABLE `dba_table_info` ADD `sort_1_field` VARCHAR( 63 ) NULL AFTER `auto_dump`");
  mysqli_query($db,"ALTER TABLE `dba_table_info` CHANGE `sort_1_field` `sort_1_field` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
  mysqli_query($db,"ALTER TABLE `dba_table_info` ADD `seq_no_field` VARCHAR( 63 ) NULL AFTER `sort_1_field`");
  mysqli_query($db,"ALTER TABLE `dba_table_info` CHANGE `seq_no_field` `seq_no_field` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
  mysqli_query($db,"ALTER TABLE `dba_table_info` ADD `seq_method` ENUM( 'continuous', 'repeat' ) NOT NULL DEFAULT 'continuous' AFTER `seq_no_field`");
  mysqli_query($db,"ALTER TABLE `dba_table_info` CHANGE `seq_method` `seq_method` ENUM( 'continuous', 'repeat' ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL DEFAULT 'continuous'");
  mysqli_query($db,"ALTER TABLE `dba_table_info` ADD `renumber_enabled` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `seq_method`");
  mysqli_query($db,"ALTER TABLE `dba_table_info` CHANGE `renumber_enabled` `renumber_enabled` TINYINT( 1 ) NOT NULL DEFAULT '0'");
  mysqli_query($db,"ALTER TABLE `dba_table_info` ADD `engine` ENUM( 'InnoDB', 'MyISAM' ) NOT NULL DEFAULT '$default_engine' AFTER `renumber_enabled`");
  mysqli_query($db,"ALTER TABLE `dba_table_info` CHANGE `engine` `engine` ENUM( 'InnoDB', 'MyISAM' ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL DEFAULT '$default_engine'");
  mysqli_query($db,"ALTER TABLE `dba_table_info` ADD `character_set` VARCHAR( 15 ) NULL AFTER `engine`");
  mysqli_query($db,"ALTER TABLE `dba_table_info` CHANGE `character_set` `character_set` VARCHAR( 15 ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL DEFAULT '-auto-'");
  mysqli_query($db,"UPDATE dba_table_info SET character_set='-auto-' WHERE character_set='' OR character_set IS NULL");
  mysqli_query($db,"ALTER TABLE `dba_table_info` ADD `collation` VARCHAR( 31 ) NULL AFTER `character_set`");
  mysqli_query($db,"ALTER TABLE `dba_table_info` CHANGE `collation` `collation` VARCHAR( 31 ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL DEFAULT '-auto-'");
  mysqli_query($db,"UPDATE dba_table_info SET collation='-auto-' WHERE collation='' OR collation IS NULL");
  mysqli_query($db,"ALTER TABLE `dba_table_info` ADD `orphan` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `collation`");
  mysqli_query($db,"ALTER TABLE `dba_table_info` CHANGE `orphan` `orphan` TINYINT( 1 ) NOT NULL DEFAULT '0'");
  if ($new_installation)
  {
    print("<p>This is a first time installation - please return to the main page (to do any auto view creation) and then repeat this operation</p>\n");
    return;
  }

  // Run the following queries to create/update the structure for the table fields table.
  // Any queries to create existing fields will automatically fail.
  mysqli_query($db,"CREATE TABLE `dba_table_fields` ( `table_name` varchar(63) COLLATE $default_collation NOT NULL, PRIMARY KEY (`table_name`) ) ENGINE=$default_engine DEFAULT CHARSET=$default_charset COLLATE=$default_collation");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` DROP `parent_table`");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` DROP `default_widget_type`");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` DROP `custom_widget_type`");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` CHANGE `table_name` `table_name` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` ADD `field_name` VARCHAR( 63 ) NOT NULL AFTER `table_name`");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` CHANGE `field_name` `field_name` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL");
  mysqli_query($db,"ALTER TABLE `dba_table_fields`  DROP PRIMARY KEY, ADD PRIMARY KEY( `table_name`, `field_name`)");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` ADD `is_primary` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `field_name`");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` CHANGE `is_primary` `is_primary` TINYINT( 1 ) NOT NULL DEFAULT '0'");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` ADD `required` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `is_primary`");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` CHANGE `required` `required` TINYINT( 1 ) NOT NULL DEFAULT '0'");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` ADD `alt_label` VARCHAR( 63 ) NULL AFTER `required`");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` CHANGE `alt_label` `alt_label` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` ADD `widget_type` ENUM( $widget_types ) NOT NULL DEFAULT '$default_widget_type' AFTER `alt_label`");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` CHANGE `widget_type` `widget_type` ENUM( $widget_types ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL DEFAULT '$default_widget_type'");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` ADD `description` VARCHAR( 255 ) NULL AFTER `widget_type`");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` CHANGE `description` `description` VARCHAR( 255 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` ADD `vocab_table` VARCHAR( 63 ) NULL AFTER `description`");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` CHANGE `vocab_table` `vocab_table` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` ADD `vocab_field` VARCHAR( 63 ) NULL AFTER `vocab_table`");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` CHANGE `vocab_field` `vocab_field` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` ADD `list_desktop` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `vocab_field`");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` CHANGE `list_desktop` `list_desktop` TINYINT( 1 ) NOT NULL DEFAULT '0'");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` ADD `list_mobile` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `list_desktop`");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` CHANGE `list_mobile` `list_mobile` TINYINT( 1 ) NOT NULL DEFAULT '0'");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` ADD `display_group` VARCHAR( 31 ) NOT NULL DEFAULT '-default-' AFTER `list_mobile`");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` CHANGE `display_group` `display_group` VARCHAR( 31 ) NOT NULL DEFAULT '-default-'");
  mysqli_query($db,"UPDATE `dba_table_fields` SET `display_group`='-default-' WHERE display_group='0' OR display_group='' OR display_group IS NULL");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` ADD `display_order` INT( 11 ) NOT NULL DEFAULT '0' AFTER `display_group`");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` CHANGE `display_order` `display_order` INT( 11 ) NOT NULL DEFAULT '0'");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` ADD `relative_path` VARCHAR( 63 ) NULL AFTER `display_order`");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` CHANGE `relative_path` `relative_path` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` ADD `allowed_filetypes` VARCHAR( 63 ) NULL AFTER `relative_path`");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` CHANGE `allowed_filetypes` `allowed_filetypes` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` ADD `orphan` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `allowed_filetypes`");
  mysqli_query($db,"ALTER TABLE `dba_table_fields` CHANGE `orphan` `orphan` TINYINT( 1 ) NOT NULL DEFAULT '0'");

  // Run the following queries to create/update the structure for the sidebar configuration table.
  // Any queries to create existing fields will automatically fail.
  mysqli_query($db,"CREATE TABLE `dba_sidebar_config` ( `display_order` INT(11) COLLATE $default_collation NOT NULL, PRIMARY KEY (`display_order`) ) ENGINE=$default_engine DEFAULT CHARSET=$default_charset COLLATE=$default_collation");
  mysqli_query($db,"ALTER TABLE `dba_sidebar_config` CHANGE `display_order` `display_order` INT( 11 ) NOT NULL DEFAULT '9999'");
  mysqli_query($db,"ALTER TABLE `dba_sidebar_config` ADD `label` VARCHAR( 31 ) NOT NULL AFTER `display_order`");
  mysqli_query($db,"ALTER TABLE `dba_sidebar_config` CHANGE `label` `label` VARCHAR( 31 ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL");
  mysqli_query($db,"ALTER TABLE `dba_sidebar_config` ADD `action_name` VARCHAR( 63 ) NULL AFTER `label`");
  mysqli_query($db,"ALTER TABLE `dba_sidebar_config` CHANGE `action_name` `action_name` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
  mysqli_query($db,"ALTER TABLE `dba_sidebar_config` ADD `table_name` VARCHAR( 63 ) NULL AFTER `action_name`");
  mysqli_query($db,"ALTER TABLE `dba_sidebar_config` CHANGE `table_name` `table_name` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
  mysqli_query($db,"ALTER TABLE `dba_sidebar_config` ADD `link` VARCHAR( 63 ) NULL AFTER `table_name`");
  mysqli_query($db,"ALTER TABLE `dba_sidebar_config` CHANGE `link` `link` VARCHAR( 63 ) CHARACTER SET $default_charset COLLATE $default_collation NULL");
  mysqli_query($db,"ALTER TABLE `dba_sidebar_config` ADD `new_window` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `link`");
  mysqli_query($db,"ALTER TABLE `dba_sidebar_config` CHANGE `new_window` `new_window` TINYINT( 1 ) NOT NULL DEFAULT '0'");

  // Run the following queries to create/update the structure for the master location table.
  // Any queries to create existing fields will automatically fail.
  mysqli_query($db,"CREATE TABLE `dba_master_location` ( `rec_id` INT(11) COLLATE $default_collation NOT NULL, PRIMARY KEY (`rec_id`) ) ENGINE=$default_engine DEFAULT CHARSET=$default_charset COLLATE=$default_collation");
  mysqli_query($db,"ALTER TABLE `dba_master_location` ADD `location` ENUM( 'local', 'real' ) NOT NULL DEFAULT 'real' AFTER `rec_id`");
  mysqli_query($db,"ALTER TABLE `dba_master_location` CHANGE `location` `location` ENUM( 'local', 'real' ) CHARACTER SET $default_charset COLLATE $default_collation NOT NULL DEFAULT 'real'");
  mysqli_query($db,"INSERT INTO `dba_master_location` VALUES (1,'real')");

  /*
  Create views for displaying orphan records. Do not use the 'create_view_structure'
  function, as the child class definitions are pre-defined in classes.php.
  Set all orphan flags to 1 by default. The main loop below will then reset the
  flags to 0 for those tables/views which exist in the database.
  */
  mysqli_query($db,"CREATE OR REPLACE VIEW _view_orphan_table_info_records AS SELECT * FROM dba_table_info WHERE orphan=1");
  mysqli_query($db,"CREATE OR REPLACE VIEW _view_orphan_table_field_records AS SELECT * FROM dba_table_fields WHERE orphan=1");
  mysqli_query($db,"INSERT INTO dba_table_info (table_name,parent_table) VALUES ('_view_orphan_table_info_records','dba_table_info')");
  mysqli_query($db,"INSERT INTO dba_table_info (table_name,parent_table) VALUES ('_view_orphan_table_field_records','dba_table_fields')");
  mysqli_query($db,"UPDATE dba_table_info SET orphan=1");
  mysqli_query($db,"UPDATE dba_table_fields SET orphan=1");


  $table_field = "Tables_in_$dbname";
  $query_result = mysqli_query($db,"SHOW FULL TABLES FROM `$dbname` WHERE `$table_field` LIKE 'dataface__%'");
  while ($row = mysqli_fetch_assoc($query_result))
  {
    $table = $row[$table_field];
    mysqli_query($db,"DROP TABLE $table");
  }
  $query_result = mysqli_query($db,"SHOW FULL TABLES FROM `$dbname` WHERE `$table_field` NOT LIKE 'dataface__%'");
  while ($row = mysqli_fetch_assoc($query_result))
  {
    $table = $row[$table_field];
    if ($row['Table_type'] != 'VIEW')
    {
      // Set the table to the required character set and collation
      $charset = $default_charset;
      $collation = $default_collation;
      $engine = $default_engine;
      $query_result2 = mysqli_query($db,"SELECT * FROM dba_table_info WHERE table_name='$table'");
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
      if (mysqli_query($db,"ALTER TABLE $table CONVERT TO CHARACTER SET $charset COLLATE $collation") === false)
      {
        print("--Unable to update charset/collation for table $table<br />");
      }
      if (mysqli_query($db,"ALTER TABLE $table ENGINE=$engine") == false)
      {
        print("--Unable to update storage engine for table $table<br />");
      }
      if (mysqli_query($db,"OPTIMIZE TABLE $table") === false)
      {
        print("--Unable to optimise table $table<br />");
      }
    }
    $table = $row[$table_field];
    mysqli_query($db,"UPDATE dba_table_info SET orphan=0 WHERE table_name='$table'");
    mysqli_query($db,"UPDATE dba_table_fields SET orphan=0 WHERE table_name='$table'");
    if ($table == get_base_table($table))
    {

      if ((is_dir("$CustomPagesPath/$RelativePath/tables/$table")) || (substr($table,0,4) == 'dba_'))
      {
        // Process table
        print("Processing");
        if ($row['Table_type'] == 'VIEW')
        {
          print(" view");
        }
        else
        {
          print(" table");
        }
        print(" <em>$table</em> ...<br />\n");
        mysqli_query($db,"INSERT INTO dba_table_info (table_name) VALUES ('$table')");  // Will automatically fail if already present.
        $last_display_order = 0;
        $query_result2 = mysqli_query($db,"SHOW COLUMNS FROM $table");
        while ($row2 = mysqli_fetch_assoc($query_result2))
        {
          // Process table field
          $field_name = $row2['Field'];
          $field_type = strtok($row2['Type'],'(');
          $field_size = strtok(')');
          if ($row2['Key'] == 'PRI')
          {
            $is_primary = 1;
          }
          else
          {
            $is_primary = 0;
          }
          if ($row2['Null'] == 'NO')
          {
            $required = 1;
          }
          else
          {
            $required = 0;
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
              if ($field_size >= 128)
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
              break;

            case 'tinyint':
              $default_widget_type = 'checkbox';
              break;

            case 'enum':
              $enum_select_list = $field_size;
              $field_size ='';
              $default_widget_type = 'enum';
              break;

            default:
              $default_widget_type = 'input-text';
              break;
          }
          $query_result3 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$table' AND field_name='$field_name'");
          if ($row3 = mysqli_fetch_assoc($query_result3))
          {
            /*
  					  Record already found.  Enforce the following constraints:-
              1. Ensure that the 'is primary' and 'required' fields reflect
  						   the current table definition and that they are set as static
  							 widgets.
  						2. Set the widget type for a nnumeric primary key to 'input-num'.
  						3. Set the widget type to 'date' for any date field.
  						4. Set the widget type to 'enum' for any enum field.
              5. Set the widget type to 'auto-increment' for any auto-increment field.
            */
            mysqli_query($db,"UPDATE dba_table_fields SET is_primary=$is_primary,required=$required WHERE table_name='$table' AND field_name='$field_name'");
  					if ($is_primary)
  					{
              if ($row2['Extra'] == 'auto_increment')
              {
                mysqli_query($db,"UPDATE dba_table_fields SET widget_type='auto-increment' WHERE table_name='$table' AND field_name='$field_name'");
              }
  						elseif ($field_type == 'int')
  						{
  							mysqli_query($db,"UPDATE dba_table_fields SET widget_type='input-num' WHERE table_name='$table' AND field_name='$field_name'");
  						}
  					}
  					if ($default_widget_type == 'date')
  					{
  						mysqli_query($db,"UPDATE dba_table_fields SET widget_type='date' WHERE table_name='$table' AND field_name='$field_name'");
  					}
  					if ($default_widget_type == 'enum')
  					{
  						mysqli_query($db,"UPDATE dba_table_fields SET widget_type='enum' WHERE table_name='$table' AND field_name='$field_name'");
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
            $query_result4 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$table' AND display_order=$next_display_order");
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
            $query = "INSERT INTO dba_table_fields";
            $query .= " (table_name,field_name,is_primary,required,widget_type,list_desktop,list_mobile,display_order)";
            $query .= " VALUES ('$table','$field_name',$is_primary,$required,'$default_widget_type',$is_primary,$is_primary,$display_order)";
            mysqli_query($db,$query);
            print("&nbsp;&nbsp;&nbsp;Field <em>$field_name</em> added<br />\n");
          }
        }
      }

      /*
      Force certain widgets to static within the dba_table_fields table iteslf.
      Although the 'table_name' field should not be editable, allow this to be
      a 'select' widget in order to allow it to be selected in a copy operation.
      */
      mysqli_query($db,"UPDATE dba_table_fields SET widget_type='select',vocab_table='dba_table_info',vocab_field='table_name' WHERE table_name='dba_table_fields' AND field_name='table_name'");
      mysqli_query($db,"UPDATE dba_table_fields SET widget_type='static' WHERE table_name='dba_table_fields' AND (field_name='field_name' OR field_name='is_primary' OR field_name='required')");
    }
  }

  // Make a number of standard settings to enable certain non primary key fields
  // to be displayed by default in a table listing.
  mysqli_query($db,"UPDATE dba_table_fields SET list_desktop=1,list_mobile=1 WHERE table_name='dba_sidebar_config' AND field_name='label'");
  mysqli_query($db,"UPDATE dba_table_fields SET list_desktop=1,list_mobile=1 WHERE table_name='dba_table_info' AND field_name='parent_table'");
  mysqli_query($db,"UPDATE dba_table_fields SET list_desktop=1 WHERE table_name='dba_table_info' AND field_name='auto_dump'");
  mysqli_query($db,"UPDATE dba_table_fields SET list_desktop=1,list_mobile=1 WHERE table_name='dba_table_fields' AND field_name='display_group'");
  mysqli_query($db,"UPDATE dba_table_fields SET list_desktop=1,list_mobile=1 WHERE table_name='dba_table_fields' AND field_name='display_order'");
  mysqli_query($db,"UPDATE dba_table_fields SET list_desktop=1,list_mobile=1 WHERE table_name='dba_master_location' AND field_name='location'");

  // Set sequencing info for built-in tables
  mysqli_query($db,"UPDATE dba_table_info SET sort_1_field='table_name',seq_no_field='display_order',seq_method='repeat',renumber_enabled=1 WHERE table_name='dba_table_fields'");
  mysqli_query($db,"UPDATE dba_table_info SET sort_1_field='',seq_no_field='display_order',seq_method='continuous',renumber_enabled=1 WHERE table_name='dba_sidebar_config'");

  // Set miscellaneous field descriptions for built-in tables
  mysqli_query($db,"UPDATE dba_table_fields SET description='Character set to be applied to the table. Set to <i>-auto-</i> to use default.' WHERE table_name='dba_table_info' AND field_name='character_set'");
  mysqli_query($db,"UPDATE dba_table_fields SET description='Collation to be applied to the table. Set to <i>-auto-</i> to use default.' WHERE table_name='dba_table_info' AND field_name='collation'");

  print("<p>Operation completed.</p>\n");
  print("<p><a href=\"./?-table=_view_orphan_table_info_records\" target=\"_blank\">Orphan Table Info Records</a><br />\n");
  print("<a href=\"./?-table=_view_orphan_table_field_records\" target=\"_blank\">Orphan Table Field Records</a></p>\n");
}
//==============================================================================
}
//==============================================================================
?>
