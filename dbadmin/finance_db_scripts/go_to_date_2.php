<script>
  function goBack() {
    window.history.back();
  }
</script>
<?php
//==============================================================================

// Variables $local_site_dir and $RelativePath must be set up beforehand
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
require_once("$BaseDir/_link_to_common/date_funct.php");
require_once("$PrivateScriptsDir/mysql_connect.php");
require("$CustomPagesPath/$RelativePath/db_funct.php");
$db = admin_db_connect();
$table = $_GET['table'];
$account = $_GET['account'];

if (isset($_POST['submitted']))
{
	// Validate/determine the required date.
	if (empty($_POST['yr']))
	{
		$error_message = "Invalid Date";
	}
	else
	{
		if (empty($_POST['mth']))
		{
			if (!empty($_POST['dy']))
			{
				$error_message = "Invalid Date";
			}
			else
			{
				// No day & month specified. Set date to end of given year.
				$_POST['mth'] = 12;
			}
		}
		if (!empty($_POST['mth']))
		{
			if (empty($_POST['dy']))
			{
				// No day specified. Set date to end of given month.
				$_POST['dy'] = DaysInMonth($_POST['mth'],$_POST['yr']);
			}
			elseif ($_POST['dy'] > DaysInMonth($_POST['mth'],$_POST['yr']))
			{
				$error_message = "Invalid Date";
        print("<button onclick=\"goBack()\">Go Back</button>");
		  }
    }
	}

	if (isset($error_message))
  {
    print("<p>$error_message</p>\n");
    print("<p><a href=\"\">Try again</a></p>\n");
  }
  else
	{
		$display_date = sprintf("%04d-%02d-%02d",$_POST['yr'],$_POST['mth'],$_POST['dy']);
		$query_result = mysqli_query($db,"SELECT * FROM $table WHERE date>'$display_date'");
		$display_offset = mysqli_num_rows($query_result);
		if ($display_offset == 0)
		{
			$new_url = "$BaseURL/$RelativePath/?-table=$table";
		}
		elseif ($row = mysqli_fetch_assoc($query_result))
		{
			$display_offset = floor($display_offset/100) * 100;
			$new_url = "$BaseURL/$RelativePath/?-table=$table&-startoffset=$display_offset";
		}
    header("Location: $new_url");
    exit;
	}
}

//==============================================================================
?>
