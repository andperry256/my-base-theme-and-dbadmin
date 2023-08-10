<?php

  $where_clause = '';
  $where_values = array();
  $add_clause = '';
  $query_result = mysqli_select_query($db,'','*',$where_clause,$where_values,$add_clause);

  $set_fields = '';
  $set_values = array();
  $where_clause = '';
  $where_values = array();
  mysqli_update_query($db,$table,$set_fields,$set_values,$where_clause,$where_values);

  $fields = '';
  $values = array();
  mysqli_insert_query($db,$table,$fields,$values);

  $where_clause = '';
  $where_values = array();
  mysqli_delete_query($db,$table,$where_clause,$where_values);
?>
