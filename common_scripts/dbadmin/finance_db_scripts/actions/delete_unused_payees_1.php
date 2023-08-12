<?php
//==============================================================================

$db = admin_db_connect();
print("<h1>Delete Unused Payees</h1>\n");
print("<p>The following payees are currently unused and will be deleted:-</p>\n");
print("<ul>\n");
$add_clause = ' ORDER BY name ASC';
$query_result = mysqli_select_query($db,'payees','*','',array(),$add_clause);
while ($row = mysqli_fetch_assoc($query_result))
{
		$payee = $row['name'];
	  $where_clause = 'payee=?';
	  $where_values = array('s',$payee);
	  $query_result2 = mysqli_select_query($db,'transactions','*',$where_clause,$where_values,'');
		$count = mysqli_num_rows($query_result2);
		mysqli_query_normal($db,"UPDATE payees SET instances=$count WHERE name='$payee'");
		if (($count == 0) && ($row['locked'] == 0))
		{
			print("<li>{$row['name']}</li>\n");
		}
}
print("</ul>\n");
print("<p><a href=\"index.php?-action=delete_unused_payees_2\"><button>Continue</button></a></p>\n");

//==============================================================================
?>
