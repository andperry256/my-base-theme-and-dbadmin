<?php
//==============================================================================

// SELECT
$where_clause = '';
$where_values = array('s',);
$add_clause = '';
$query_result = mysqli_select_query($db,'','*',$where_clause,$where_values,'');
$query_result = mysqli_select_query($db,'','*','',array(),'');

// UPDATE
$set_fields = '';
$set_values = array('s',);
$where_clause = '';
$where_values = array('s',);
mysqli_update_query($db,'',$set_fields,$set_values,$where_clause,$where_values);
mysqli_update_query($db,'',$set_fields,$set_values,'',array());

// INSERT
$fields = '';
$values = array('s',);
mysqli_insert_query($db,'',$fields,$values);

// CONDITIONAL INSERT
$fields = '';
$values = array('s',);
$where_clause = '';
$where_values = array('s',);
mysqli_conditional_insert_query($db,'',$fields,$values,$where_clause,$where_values);

// DELETE
$where_clause = '';
$where_values = array('s',);
mysqli_delete_query($db,'',$where_clause,$where_values);
mysqli_delete_query($db,'','',array());

// FREE FORMAT
$query = "";
$where_values = array('s',);
mysqli_free_format_query($db,$query,$where_values);

//==============================================================================
?>
