<?php
//==============================================================================
if (!function_exists('mysqli_query_normal'))
{
//==============================================================================
/*
Function run_mysqli_query

This is called in place of a regular call to mysqli_query and is used to output
a more useful error message if the MySQL function call raises an exception.

If the $strict option is set, then it will also abort with an error message if
the MySQL function call runs without an exception but returns an error value.

On an online server, errors are output to a log file rather than the screen.
*/
//==============================================================================

function run_mysqli_query($db,$query,$strict=false)
{
	global $argc, $RootDir;
	$eol = (isset($argc)) ? "\n" : "<br />\n";
	$error_id = substr(md5(date('YmdHis')),0,8);
	$date_and_time = date('Y-m-d H:i:s');
	$fatal_error_message = "There has been a fatal error, details of which have been logged.$eol";
	$fatal_error_message .= "Please report this to the webmaster quoting code <strong>$error_id</strong>.$eol";
	try
	{
		$result = mysqli_query($db,$query);
	}
	catch (Exception $e)
	{
		if (is_file("/Config/linux_pathdefs.php"))
		{
			// Local server
			print("Error caught on running MySQL query:$eol$query$eol");
			print($e->getMessage().$eol);
		}
		else
		{
			// Online server
			$ofp = fopen("$RootDir/logs/php_error.log",'a');
			fprintf($ofp,"[$date_and_time] [$error_id] Error caught on running MySQL query:\n  $query\n");
			fprintf($ofp,'  '.$e->getMessage()."\n");
			fclose($ofp);
			print($fatal_error_message);
		}
		exit;
	}
	if ((!$result) && ($strict))
	{
		if (is_file("/Config/linux_pathdefs.php"))
		{
			// Local server
			print("Error result returned from MySQL query:$eol$query$eol");
		}
		else
		{
			// Online server
			$ofp = fopen("$RootDir/logs/php_error.log",'a');
			fprintf($ofp,"[$date_and_time] [$error_id] Error result returned from MySQL query:\n  $query\n");
			fclose($ofp);
			print($fatal_error_message);
		}
		exit;
	}
	return $result;
}

//==============================================================================

function mysqli_query_normal($db,$query)
{
	return run_mysqli_query($db,$query,false);
}

//==============================================================================

function mysqli_query_strict($db,$query)
{
	return run_mysqli_query($db,$query,true);
}

//==============================================================================
}
//==============================================================================
?>
