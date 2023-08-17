<?php
//==============================================================================

// SELECT
$where_clause = '';
$where_values = array();
$add_clause = '';
$query_result = mysqli_select_query($db,'','*',$where_clause,$where_values,'');

// UPDATE
$set_fields = '';
$set_values = array();
$where_clause = '';
$where_values = array();
mysqli_update_query($db,'',$set_fields,$set_values,$where_clause,$where_values);

// INSERT
$fields = '';
$values = array();
mysqli_insert_query($db,'',$fields,$values);

// DELETE
$where_clause = '';
$where_values = array();
mysqli_delete_query($db,'',$where_clause,$where_values);

// FREE FORMAT
$query = "";
$where_values = array();
mysqli_free_format_query($db,$query,$where_values);

//==============================================================================
?>
