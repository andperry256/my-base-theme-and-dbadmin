<script>
  function goBack() {
    window.history.back();
  }
</script>
<?php
//==============================================================================

// Variables $local_site_dir and $RelativePath must be set up beforehand
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
require_once("$BaseDir/common_scripts/date_funct.php");
require_once("$PrivateScriptsDir/mysql_connect.php");
require("$CustomPagesPath/$RelativePath/db_funct.php");
$db = admin_db_connect();
$table = $_GET['table'];

$query_result = mysqli_query_normal($db,"SELECT * FROM dba_table_info WHERE table_name='transactions'");
if ($row = mysqli_fetch_assoc($query_result))
{
  $list_size = $row['list_size'];
}
else
{
  exit("ERROR - Failed to read table info from DB - this should not occur!!");
}

if (isset($_POST['submitted']))
{
  if (!DateIsValid($_POST['date_selection']))
	{
    print("<p>Invalid Date</p>\n");
    print("<p><a href=\"{$_GET['-returnurl']}\">Try again</a></p>\n");
	}
  else
	{
		$query_result = mysqli_query_normal($db,"SELECT * FROM $table WHERE date>'{$_POST['date_selection']}'");
		$display_offset = mysqli_num_rows($query_result);
    $display_offset = floor($display_offset/$list_size) * $list_size;
    header("Location: $BaseURL/$RelativePath/?-table=$table&-startoffset=$display_offset");
    exit;
	}
}

//==============================================================================
?>
