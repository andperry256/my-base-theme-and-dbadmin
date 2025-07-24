<?php
//==============================================================================

// SELECT
$where_clause = '';
$where_values = ['s',];
$add_clause = '';
$query_result = mysqli_select_query($db,'','*',$where_clause,$where_values,'');
$query_result = mysqli_select_query($db,'','*','',[],'');

// UPDATE
$set_fields = '';
$set_values = ['s',];
$where_clause = '';
$where_values = ['s',];
mysqli_update_query($db,'',$set_fields,$set_values,$where_clause,$where_values);
mysqli_update_query($db,'',$set_fields,$set_values,'',[]);

// INSERT
$fields = '';
$values = ['s',];
mysqli_insert_query($db,'',$fields,$values);

// CONDITIONAL INSERT
$fields = '';
$values = ['s',];
$where_clause = '';
$where_values = ['s',];
mysqli_conditional_insert_query($db,'',$fields,$values,$where_clause,$where_values);

// DELETE
$where_clause = '';
$where_values = ['s',];
mysqli_delete_query($db,'',$where_clause,$where_values);
mysqli_delete_query($db,'','',[]);

// FREE FORMAT
$query = "";
$where_values = ['s',];
mysqli_free_format_query($db,$query,$where_values);

//==============================================================================
?>
