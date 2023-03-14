<?php
//==============================================================================

$db = admin_db_connect();
$entities = array ( 'funds'=>'fund', 'categories'=>'category', 'payees'=>'payee');

if ((!isset($_GET['type'])) && (!isset($_POST['type'])))
{
	// Initial page with entity type selection
	print("<h1>Merge Entities</h1>\n");
	print("<p>Please select entity type:-<br/></p>\n");
	print("<p><a href=\"index.php?-action=merge_entities&type=funds\"><button>Funds</button></a>&nbsp;&nbsp;&nbsp;");
	print("<a href=\"index.php?-action=merge_entities&type=categories\"><button>Categories</button></a>&nbsp;&nbsp;&nbsp;");
	print("<a href=\"index.php?-action=merge_entities&type=payees\"><button>Payees</button></a>&nbsp;&nbsp;&nbsp;</p>\n");
}
else
{
	if (isset($_POST['type']))
	{
		$type = $_POST['type'];
	}
	else
	{
		$type = $_GET['type'];
	}
	$Type = ucfirst($type);
	$entity = $entities[$type];
	$Entity = ucfirst($entity);
	$show_form = true;

	print("<h1>Merge $Type</h1>\n");
	if (isset($_POST['type']))
	{
		if ((empty($_POST['source'])) && (empty($_POST['target'])))
		{
			print("<p><strong>ERROR</strong> - one or both of the source and target have not been set.</p>\n");
		}
		elseif ($_POST['source'] == $_POST['target'])
		{
			print("<p><strong>ERROR</strong> - the source and target cannot be the same.</p>\n");
		}
		elseif ($_POST['confirm'] == 'YES')
		{
				// Run the merge
				$source = addslashes($_POST['source']);
				$target = addslashes($_POST['target']);
				mysqli_query_normal($db,"UPDATE transactions SET $entity='$target' WHERE  $entity='$source'");
				if ($type != 'payees')
				{
					mysqli_query_normal($db,"UPDATE splits SET $entity='$target' WHERE  $entity='$source'");
				}
				mysqli_query_normal($db,"DELETE from $type WHERE name='$source'");
				print("<p>$Entity <strong>{$_POST['source']}</strong> successfully merged into <strong>{$_POST['target']}</strong>.</p>\n");
				print("<p><a href=\"index.php?-action=merge_entities&type={$_POST['type']}\"><button>Go Back</button></a></p>\n");
				$show_form = false;
		}
	}

	if ($show_form)
	{
		print("<p>You are about to merge two $type. ");
		print("All transactions with the source $entity will be changed to use the target $entity. ");
		print("The source $entity will then be removed from the system.</p>");
		print("<form method=\"post\">\n");
		print("<table cellpadding=\"8\"><tr>\n");
		$query = "SELECT * FROM $type WHERE name NOT LIKE '-%' ORDER BY name ASC";

		// Selector for source
		print("<td width=\"100px\">Source:</td>");
		print("<td><select name=\"source\">\n");
		print("<option value=\"\">Please select ...</option>");
		$query_result = mysqli_query_normal($db,$query);
		while ($row = mysqli_fetch_assoc($query_result))
		{
			print("<option value=\"{$row['name']}\"");
			if ((isset($_POST['source'])) && ($_POST['source'] == $row['name']))
			{
				print(" SELECTED");
			}
			print(">{$row['name']}</option>");
		}
		print("</select></td>\n");
		print("</tr><tr>\n");

		// Selector for target
		print("<td>Target:</td>");
		print("<td><select name=\"target\">\n");
		print("<option value=\"\">Please select ...</option>");
		$query_result = mysqli_query_normal($db,$query);
		while ($row = mysqli_fetch_assoc($query_result))
		{
			print("<option value=\"{$row['name']}\"");
			if ((isset($_POST['target'])) && ($_POST['target'] == $row['name']))
			{
				print(" SELECTED");
			}
			print(">{$row['name']}</option>");
		}
		print("</select></td>\n");
		print("</tr><tr>\n");

		// Confirmation
		print("<td>Are you sure?</td>");
		print("<td><input type=\"radio\" name=\"confirm\" value=\"NO\" checked> No<br/>");
		print("<input type=\"radio\" name=\"confirm\" value=\"YES\"> Yes</td>");
		print("</tr></table>\n");
		print("<input value=\"Merge $Type\" type=\"submit\">\n");
		print("<input type=\"hidden\" name=\"type\" value=\"$type\" />\n");
		print("</form>\n");
	}
}

//==============================================================================
?>
