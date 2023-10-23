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
              $set_fields = "$entity";
              $set_values = array('s',$_POST['target']);
              $where_clause = "$entity=?";
              $where_values = array('s',$_POST['source']);
              mysqli_update_query($db,'transactions',$set_fields,$set_values,$where_clause,$where_values);
              if ($type != 'payees')
              {
                  $set_fields = "$entity";
                  $set_values = array('s',$target);
                  $where_clause = "$entity=?";
                  $where_values = array('s',$_POST['source']);
                  mysqli_update_query($db,'splits',$set_fields,$set_values,$where_clause,$where_values);
              }
              $where_clause = 'name=?';
              $where_values = array('s',$_POST['source']);
              mysqli_delete_query($db,$type,$where_clause,$where_values);
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
    
        // Selector for source
        print("<td width=\"100px\">Source:</td>");
        print("<td><select name=\"source\">\n");
        print("<option value=\"\">Please select ...</option>");
        $where_clause = "name NOT LIKE '-%'";
        $add_clause = 'ORDER BY name ASC';
        $query_result = mysqli_select_query($db,$type,'*',$where_clause,array(),$add_clause);
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
        $where_clause = "name NOT LIKE '-%'";
        $add_clause = 'ORDER BY name ASC';
        $query_result = mysqli_select_query($db,$type,'*',$where_clause,array(),$add_clause);
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
