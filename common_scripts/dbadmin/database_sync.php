<?php
//==============================================================================
if (!function_exists('sync_databases'))
{
//==============================================================================
function sync_databases($local_db_name)
{
	global $Location, $CustomPagesPath, $RelativePath, $local_site_dir,
	$Localhost_ID, $DBAdminURL, $db_master_location, $Server_Station_ID;

	set_time_limit(300);
	print("<h1>Synchronise Databases</h1>\n");
	print("<h2 style=\"margin-bottom:0.5em\">($local_site_dir/$RelativePath)</h2>\n");
	$relationships_script_file = "$CustomPagesPath/$RelativePath/relationships.sql";
	$db = admin_db_connect();

	if ($Location == 'local')
	{
		$sync_direction = 'in';  // Default direction
		$db_sites = sites_db_connect();
		$query_result = mysqli_query($db_sites,"SELECT * FROM dbases WHERE dbname='$local_db_name' AND domname='$Server_Station_ID'");
		if ($row = mysqli_fetch_assoc($query_result))
		{
			switch ($row['mode'])
			{
					case 'auto':
						$db_sub_path = str_replace('dbadmin/','',$RelativePath);
				    if ($Location == $db_master_location[$db_sub_path])
				    {
							$sync_direction = 'out';
				    }
				    else
				    {
							$sync_direction = 'in';
				    }
						break;

					case 'master':
						$sync_direction = 'out';
						break;

					case 'sub-master':
						$sync_direction = 'in';
						break;
			}

			if (isset($_POST['submitted']))
			{
				switch ($_POST['sync_mode'])
				{
					case ('save-rlshps'):
						if ((!isset($_POST['force-save-rlshps'])) && (is_file($relationships_script_file)))
						{
							$contents = file($relationships_script_file);
							if (substr($contents[0],0,12) == '## LOCKED ##')
							{
								print("<p>ERROR - Script file is locked.</p>\n");
								break;
							}
						}
						$ofp = fopen($relationships_script_file,'w');
						fprintf($ofp,"## LOCKED ##\n");
						$count = 0;
						$query_result2 = mysqli_query($db,"SELECT * FROM dba_relationships WHERE table_name NOT LIKE '%dba_%'");
						while ($row2 = mysqli_fetch_assoc($query_result2))
						{
							$relationship_name_par = addslashes($row2['relationship_name']);
							$line = "INSERT INTO dba_relationships VALUES ('{$row2['table_name']}','$relationship_name_par',\"{$row2['query']}\");";
							$line = str_replace('%','%%',$line);
							fprintf($ofp,"$line\n");
							$count++;
						}
						fclose($ofp);
						if ($count == 1)
						{
							print("<p>$count Query saved to relationships.sql.</p>\n");
						}
						else
						{
							print("<p>$count Queries saved to relationships.sql.</p>\n");
						}
						break;

					default:
						$cmd = "/Utilities/php_script mysql_sync $local_site_dir {$row['sub_path']}";
						if ($_POST['sync_mode'] == 'backup')
						{
							$cmd .= " -b $Localhost_ID";
						}
						elseif ($_POST['sync_mode'] == 'restore')
						{
							$cmd .= " -r $Localhost_ID";
						}
						elseif ($_POST['sync_mode'] == $sync_direction)
						{
							$cmd .= " -s -force";
						}
						else
						{
							$cmd .= " -rs -force";
						}
						$start_time = time();
						exec("$cmd > '__temp_.txt'");
						$duration = time() - $start_time;
						$output = implode(file('__temp_.txt'));
						$output = str_replace("\n","<br />\n",$output);
						print("$output");
						print("Execution time: $duration seconds.<br />\n");
						unlink ('__temp_.txt');
						break;
				}
			}
			else
			{
				print("<p>This facility is used to synchronise the local and online copies of the database. It may take a minute or so to complete, so please be patient.<br /><br />==== Please use with caution!! ====</p>\n");
				print("<form method=\"post\">\n");
				print("<table cellpadding=\"8\">\n");
				print("<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"in\"");
				if ($sync_direction == 'in')
				{
					print(" checked");
				}
				print("></td><td>Download online DB to local DB</td></tr>\n");

				print("<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"out\"");
				if ($sync_direction == 'out')
				{
					print(" checked");
				}
				print("></td><td>Upload local DB to online DB</td></tr>\n");
				print("<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"backup\"></td><td>Back up local DB (locally)</td></tr>\n");
				print("<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"restore\"></td><td>Restore local DB (locally)</td></tr>\n");
				print("<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"save-rlshps\"></td><td>Save relationships (locally)<br />");
				print("<input type=\"checkbox\" name=\"force-save-rlshps\">&nbsp;Force&nbsp;update</td></tr>\n");
				print("<tr><td colspan=\"2\"><input value=\"Run\" type=\"submit\"></td></tr>\n");
				print("</table>\n");
				print("<input type=\"hidden\" name=\"submitted\" value=\"TRUE\" />\n");
				print("</form>\n");
			}
		}
		else
		{
			print("<p>Unable to locate database record in sites database.</p>\n");
		}
	}
	else
	{
		print("<p>This facility is currently only supported on the local server.</p>\n");
	}
}
//==============================================================================
}
//==============================================================================
?>
