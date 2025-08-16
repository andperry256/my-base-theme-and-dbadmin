<?php
//==============================================================================

class tables_categories
{
    function beforeDelete($record)
    {
        $type = $record->FieldVal('type');
        if ($type == 'built-in') {
            return report_error("This is a built-in system category - please delete using phpMyAdmin.");
        }
    }
  
    function beforeSave($record)
    {
        $action = $record->action;
        $table = $record->table;
        $type = $record->FieldVal('type');
        if ($type == 'built-in') {
            return report_error("This is a built-in system category - please edit using phpMyAdmin.");
        }
    }
}

//==============================================================================
