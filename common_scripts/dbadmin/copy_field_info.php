<?php
  ini_set('error_reporting','E_ALL');
  require("functions.php");
  if (isset($_GET['site']))
  {
    $local_site_dir = $_GET['site'];
    require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
    require("$PrivateScriptsDir/mysql_connect.php");
  }
  else
  {
    exit("Site parameter not specified");
  }
  if (isset($_GET['table']))
  {
    $table = $_GET['table'];
  }
  else
  {
    exit("Table parameter not specified");
  }

  if (isset($_GET['subpath']))
  {
    $tables_dir = "$BaseDir/admin2/{$_GET['subpath']}/tables";
    require("$CustomPagesPath/dbadmin/db-"."{$_GET['subpath']}/db_funct.php");
  }
  else
  {
    $tables_dir = "$BaseDir/admin2/tables";
    require("$CustomPagesPath/dbadmin/db_funct.php");
  }

  $tables = array();
  if ($table == '*')
  {
    if ($handle = opendir($tables_dir))
    {
      while (false !== ($file = readdir($handle)))
      {
        if ((is_dir("$tables_dir/$file")) && (!is_link("$tables_dir/$file")) && ($file != '.') && ($file != '..'))
        {
          $tables[$file] = true;
        }
      }
    }
  }
  else
  {
    $tables[$table] = true;
  }

  $db = admin_db_connect();
  foreach ($tables as $table => $val)
  {
    print("<h1>Table $table</h1>\n");
    $source = "$tables_dir/$table/fields.ini";
    if (!is_file($source))
    {
      print("fields.ini file cannot be loaded<br />");
    }

    $base_table = get_base_table($table);
    $query_result = mysqli_query_normal($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table'");
    if (mysqli_num_rows($query_result) > 0)
    {
      $list_desktop = array();
      $input = file($source);
      foreach ($input as $line)
      {
        if (substr($line,0,1) == '[')
        {
          $field_name = trim($line,"[] \n\r\t");
          print("Processing field $field_name ...<br />\n");
          $list_desktop[$field_name] = 1;
        }
        elseif (substr($line,0,15) == 'visibility:list')
        {
          $vis_status = trim(substr($line,15)," =\"\n\r\t");
          if ($vis_status == 'hidden')
          {
            $list_desktop[$field_name] = 0;
          }
        }
        elseif (substr($line,0,12) == 'widget:label')
        {
          $label = addslashes(trim(substr($line,12)," =\"\n\r\t"));
          $query = "UPDATE dba_table_fields SET alt_label='$label' WHERE table_name='$table' AND field_name='$field_name'";
          print("$query<br />\n");
          mysqli_query_normal($db,$query);
        }
        elseif (substr($line,0,18) == 'widget:description')
        {
          $description = addslashes(trim(substr($line,18)," =\"\n\r\t"));
          $query = "UPDATE dba_table_fields SET description='$description' WHERE table_name='$table' AND field_name='$field_name'";
          print("$query<br />\n");
          mysqli_query_normal($db,$query);
        }
        elseif (substr($line,0,10) == 'vocabulary')
        {
          print("<span style=\"color:red\">&nbsp;&nbsp;Vocabulary found</span><br />\n");
        }
      }

      foreach ($list_desktop as $field => $status)
      {
        $query = "UPDATE dba_table_fields SET list_desktop=$status WHERE table_name='$table' AND field_name='$field'";
        print("$query<br />\n");
        mysqli_query_normal($db,$query);
      }
    }
  }
  print("<p>Operation completed</p>\n");
?>
