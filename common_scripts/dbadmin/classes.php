<?php
//==============================================================================

define ('DEFAULT_SEQ_NO',9999);

//==============================================================================

class db_record
{
    public $action;
    public $table;
    private $fields = array();
    private $old_primary_keys = array();
    private $custom_vars = array();
  
    function SetField($field_name,$value,$type='s')
    {
        // Perform a trim because this is the format in which the field
        // will be submitted to any MySQL query.
        $this->fields[$field_name] = array(trim($value),$type);
    }
  
    function FieldVal($field_name)
    {
        if (isset($this->fields[$field_name]))
        {
            return $this->fields[$field_name][0];
        }
        else
        {
            return false;
        }
    }
  
    function FieldType($field_name)
    {
        if (isset($this->fields[$field_name]))
        {
            return $this->fields[$field_name][1];
        }
        else
        {
            return false;
        }
    }
  
    function FieldIsSet($field_name)
    {
        return (isset($this->fields[$field_name]));
    }
  
    function SaveOldPKs($primary_keys)
    {
        foreach ($primary_keys as $key => $val)
        {
            $this->old_primary_keys[$key] = $val;
        }
    }
  
    function OldPKVal($field_name)
    {
        if (isset($this->old_primary_keys[$field_name]))
        {
            return $this->old_primary_keys[$field_name];
        }
        else
        {
            return false;
        }
    }
  
    function SaveCustomVar($key,$val)
    {
        $this->$custom_vars[$key] = $val;
    }
  
    function CustomVarVal($key)
    {
        if (isset($this->$custom_vars[$key]))
        {
            return $this->$custom_vars[$key];
        }
        else
        {
            return false;
        }
    }
}

//==============================================================================

class tables_dba_table_info
{
    function beforeSave($record)
    {
          $charset = $record->FieldVal('character_set');
          $collation = $record->FieldVal('collation');
          $len = strlen($charset);
          if (substr($collation,0,$len) != $charset)
          {
              return report_error("Collation does not match the character set");
          }
    }
  
    function afterDelete($record)
    {
        $db = admin_db_connect();
        $table = $record->FieldVal('table_name');
        $where_clause = 'table_name=?';
        $where_values = array('s',$table);
        mysqli_delete_query($db,'dba_table_fields',$where_clause,$where_values);
    }
}

class tables__view_orphan_table_info_records extends tables_dba_table_info {}

//==============================================================================

class tables_dba_table_fields
{
    function widget_type__validate($record,$value)
    {
        $table = $record->FieldVal('table_name');
        $field = $record->FieldVal('field_name');
        $db = admin_db_connect();
        $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM $table WHERE Field='$field'");
        if ($row = mysqli_fetch_assoc($query_result))
        {
            $field_type = strtok($row['Type'],'(');
            $field_size = strtok(')');
      
            // Ensure that the widget type is set to 'date' for any date field
            if (($field_type == 'date') && ($value != 'date') && ($value != 'static-date'))
            {
                return report_error("Attempt to set non <em>date</em> widget type on date field <em>$field</em>");
            }
      
            // Ensure that the widget type is not set to 'date' for a non date field
            if (($field_type != 'date') && (($value == 'date') || ($value == 'static-date')))
            {
                return report_error("Attempt to set <em>date</em> widget type on non date field <em>$field</em>");
            }
      
            // Ensure that the widget type is set to 'enum' for any enum field
            if (($field_type == 'enum') && ($value != 'enum'))
            {
                return report_error("Attempt to set non <em>enum</em> widget type on enum field <em>$field</em>");
            }
      
            // Ensure that the widget type is not set to 'enum' for a non enum field
            if (($field_type != 'enum') && ($value == 'enum'))
            {
                return report_error("Attempt to set <em>enum</em> widget type on non enum field <em>$field</em>");
            }
      
            // Only allow a 'checkbox' widget for an int(1) field
            if ($value == 'checkbox')
            {
                if ((($field_type != 'int') && ($field_type != 'tinyint')) || ($field_size > 1))
                {
                    return report_error("Attempt to set <em>checkbox</em> widget type on non int(1) field <em>$field</em>");
                }
            }
      
            // Ensure that a select widget has a valid vocabulary
            if ($value == 'select')
            {
                $vocab_table = $record->FieldVal('vocab_table');
                $vocab_field = $record->FieldVal('vocab_field');
                $valid_select = false;
                if ((!empty($vocab_table)) && (!empty($vocab_field)))
                {
          
                    if (mysqli_select_query($db,$vocab_table,$vocab_field,'',array(),'',false))
                    {
                        $valid_select = true;
                    }
                }
                if (!$valid_select)
                {
                    return report_error("Attempt to set <em>select</em> widget type for field <em>$field</em> with no valid vocabulary.");
                }
            }
        }
    }
  
    function beforeSave($record)
    {
        /*
        Check to ensure that the table name field is not being altered unless the
        record is being copied.
        */
        if (($record->FieldVal('table_name') != $record->OldPKVal('table_name')) &&
            ($record->action != 'copy') && (!isset($_POST['save_as_new'])))
        {
            return report_error("<p class=\"highlight-error\">Table name field can only be modified when creating a copy of the record.</p>\n");
        }
    }
}

class tables__view_orphan_table_field_records extends tables_dba_table_fields {}

//==============================================================================

class tables_dba_relationships
{
  
}

//==============================================================================

class tables_dba_sidebar_config
{
    function afterSave($record)
    {
        $db = admin_db_connect();
        $display_order = $record->FieldVal('display_order');
        $default_seq_no = DEFAULT_SEQ_NO;
        if ($display_order == $default_seq_no)
        {
            $add_clause = 'ORDER BY display_order DESC LIMIT 1';
            $query_result = mysqli_select_query($db,'dba_sidebar_config','*','',array(),$add_clause);
            if ($row = mysqli_fetch_assoc($query_result))
            {
                $new_display_order = $row['display_order'] + 10;
            }
            else
            {
                $new_display_order = 10;
            }
            $set_fields = 'display_order';
            $set_values = array('i',$new_display_order);
            $where_clause = 'display_order=?';
            $where_values = array('i',$default_seq_no);
            mysqli_update_query($db,'dba_sidebar_config',$set_fields,$set_values,$where_clause,$where_values);
        }
    }
}

//==============================================================================

class tables_dba_change_log
{
  
    function afterSave($record)
    {
        $delete_record = $record->FieldVal('delete_record');
        if ($delete_record)
        {
            // Delete flag is set
            delete_record_on_save($record);
        }
    }
}

//==============================================================================

class tables_admin_passwords
{
    function beforeSave($record)
    {
        $new_passwd = $record->FieldVal('new_passwd');
        $conf_new_passwd = $record->FieldVal('conf_new_passwd');
        if ($conf_new_passwd != $new_passwd)
        {
            return report_error("Passwords do not match");
        }
    }
  
    function afterSave($record)
    {
        $db = admin_db_connect();
        $username = $record->FieldVal('username');
        $new_passwd = $record->FieldVal('new_passwd');
        if (!empty($new_passwd))
        {
            $enc_passwd = password_hash($new_passwd,PASSWORD_DEFAULT);
            $set_fields = 'new_passwd,conf_new_passwd,enc_passwd';
            $set_values = array('s','','s','','s',$enc_passwd);
            $where_clause = 'username=?';
            $where_values = array('s',$username);
            mysqli_update_query($db,'admin_passwords',$set_fields,$set_values,$where_clause,$where_values);
        }
    }
}

//==============================================================================
?>
