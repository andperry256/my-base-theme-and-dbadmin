<?php
//==============================================================================
if (!function_exists('sync_databases'))
{
//==============================================================================
function sync_databases($local_db_name)
{
	global $Location, $CustomPagesPath, $RelativePath, $local_site_dir,
	$Localhost_ID, $DBAdminURL, $db_master_location, $Server_Station_ID,
	$TableExportDir;

	set_time_limit(300);
	print("<h1>Synchronise Databases</h1>\n");
	print("<h2 style=\"margin-bottom:0.5em\">($local_site_dir/$RelativePath)</h2>\n");
	$relationships_script_file = "$CustomPagesPath/$RelativePath/relationships.sql";
	$db = admin_db_connect();

	if ($Location == 'local')
	{
		$sync_direction = 'in';  // Default direction
		$db_sites = sites_db_connect();
	  $where_clause = 'dbname=? AND domname=?';
	  $where_values = array('s',$local_db_name,'s',$Server_Station_ID);
	  $query_result = mysqli_select_query($db_sites,'dbases','*',$where_clause,$where_values,'');
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
								print("<p>ERROR - Script file is locked (please refresh page to try again).</p>\n");
								break;
							}
						}
						$ofp = fopen($relationships_script_file,'w');
						fprintf($ofp,"## LOCKED ##\n");
						$count = 0;
					  $where_clause = "table_name NOT LIKE '%dba_%'";
					  $query_result2 = mysqli_select_query($db,'dba_relationships','*',$where_clause,array(),'');
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
							$cmd .= " -b -host=$Localhost_ID";
						}
						elseif ($_POST['sync_mode'] == 'restore')
						{
							$cmd .= " -r -host=$Localhost_ID";
						}
						elseif ($_POST['sync_mode'] == 'table_dump')
						{
							$cmd = '';
							if (empty($_POST['table']))
							{
								$cmd = '';
								print("<p>ERROR - no table selected (please refresh page to try again).</p>");
							}
							else
							{
								$table = str_replace('V#','',$_POST['table']);
								$pk_fields = '';
								$field_added = false;
							  $where_clause = 'table_name=? AND is_primary=1';
							  $where_values = array('s',$table);
							  $add_clause = 'ORDER BY display_order ASC';
							  $query_result = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,$add_clause);
								if (mysqli_num_rows($query_result) == 0)
								{
									print("<p>ERROR - table <em>$table</em> not found (please refresh page to try again).</p>\n");
									return;
								}
								while ($row = mysqli_fetch_assoc($query_result))
								{
									if ($field_added)
									{
										$pk_fields .= ',';
									}
									$pk_fields .= ($row['field_name']);
									$field_added = true;
								}
								$order_clause = "$pk_fields ASC";
								export_table_to_csv("$TableExportDir/table_$table.csv",$db,$table,'','long','',$order_clause);
								print("Table $table exported to CSV");
							}
						}
						elseif (substr($_POST['sync_mode'],0,6) == 'table_')
						{
							if (empty($_POST['table']))
							{
								$cmd = '';
								print("<p>ERROR - no table selected (please refresh page to try again).<p>");
							}
							elseif (substr($_POST['table'],0,2) == 'V#')
							{
								$cmd = '';
								print("<p>ERROR - sync operations not valid for views.<p>");
							}
							elseif (substr($_POST['sync_mode'],6) == $sync_direction)
							{
								$cmd .= " -st={$_POST['table']}";
							}
							else
							{
								$cmd .= " -rst={$_POST['table']}";
							}
						}
						elseif ($_POST['sync_mode'] == $sync_direction)
						{
							$cmd .= " -s -force";
						}
						else
						{
							$cmd .= " -rs=yes -force";
						}
						if (isset($_POST['noadd']))
						{
							$cmd .= " -noadd";
						}
						print("<p><em>$cmd</em></p>\n");
						if (!empty($cmd))
						{
							$start_time = time();
							exec("$cmd > '__temp_.txt'");
							$duration = time() - $start_time;
							$output = implode(file('__temp_.txt'));
							$output = str_replace("\n","<br />\n",$output);
							print("$output");
							print("Execution time: $duration seconds.<br />\n");
							unlink ('__temp_.txt');
						}
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
				print("<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"table_in\"></td><td>Download table to local DB</td></tr>\n");
				print("<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"table_out\"></td><td>Upload table to online DB</td></tr>\n");
				print("<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"table_dump\"></td><td>Dump table to CSV</td></tr>\n");
				print("<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"save-rlshps\"></td><td>Save relationships (locally)<br />");
				print("<input type=\"checkbox\" name=\"force-save-rlshps\">&nbsp;Force&nbsp;update</td></tr>\n");
				print("<tr><td><input type=\"checkbox\" name=\"noadd\"></td><td>Omit running of pre/post operation scripts<br />");
				print("<tr><td>Table:</td><td><select name=\"table\">\n");
				print("<option value=\"\">Please select...</option>\n");
				$dbname = admin_db_name();
				$query_result = mysqli_query_normal($db,"SHOW FULL TABLES FROM `$dbname` WHERE Table_type='BASE TABLE'");
				while ($row = mysqli_fetch_assoc($query_result))
				{
					print("<option value=\"{$row["Tables_in_$dbname"]}\">{$row["Tables_in_$dbname"]}</option>\n");
				}
				$query_result = mysqli_query_normal($db,"SHOW FULL TABLES FROM `$dbname` WHERE Table_type='VIEW'");
				while ($row = mysqli_fetch_assoc($query_result))
				{
					print("<option value=\"V#{$row["Tables_in_$dbname"]}\">[v] {$row["Tables_in_$dbname"]}</option>\n");
				}
				print("</select><br />");
				print("<span class=\"small\">[v] = Item is a view - valid only with dump to CSV option.</span></td></tr>");
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
