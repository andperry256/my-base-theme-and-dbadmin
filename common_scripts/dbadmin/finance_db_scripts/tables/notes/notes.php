<?php
//==============================================================================

class tables_notes
{
    function afterSave($record)
    {
        $db = admin_db_connect();
        $action = $record->action;
        $table = $record->table;
        $set_values = array('s',date('Y-m-d'));
        $where_clause = "date NOT LIKE '20%'";
        $where_values = array();
        mysqli_update_query($db,'notes',$set_fields,$set_values,$where_clause,$where_values);
    }
}

//==============================================================================
?>
