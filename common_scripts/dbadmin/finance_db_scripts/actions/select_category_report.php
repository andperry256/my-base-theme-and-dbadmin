<?php
//==============================================================================

$db = admin_db_connect();

print("<h1>Category Report</h1>\n");
print("<p>Please select the required category:-</p>\n");

$previous_supercategory = '';
$where_clause = "type<>'built-in' OR name='-none-' OR name='-transfer-'";
$query_result = mysqli_select_query($db,'categories','*',$where_clause,array(),'');
print("<ul>\n");
while ($row = mysqli_fetch_assoc($query_result))
{
  $category = $row['name'];
  $supercategory = strtok($category,':');
  if (($supercategory != $previous_supercategory ) && (strpos($category,':') !== false))
  {
    print("<li><a href=\"index.php?-action=display_transaction_report&category=$supercategory:%%\">$supercategory [ALL]</a></li>\n");
  }
  print("<li><a href=\"index.php?-action=display_transaction_report&category=$category\">$category</a></li>\n");
  $previous_supercategory = $supercategory;
}
print("</ul>\n");

//==============================================================================
?>
