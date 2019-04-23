<?php

function sync_databases($local_db_name)
{
		global $Location, $RelativePath, $local_site_dir, $Localhost_ID;
		set_time_limit(300);

		$ofp = fopen("./templates/result.html", "w");
		print_bodytext_header($ofp);
		fprintf($ofp,"<h1>Synchronise Databases</h1>\n");
		fprintf($ofp,"<h2 style=\"margin-bottom:0.5em\">($local_site_dir/$RelativePath)</h2>\n");

		if ($Location == 'local')
		{
			$db_sites = sites_db_connect();
			$query_result = mysqli_query($db_sites,"SELECT * FROM dbases WHERE dbname='$local_db_name'");
			if (($row = mysqli_fetch_assoc($query_result)) && ($row['mode'] == 'master'))
				$sync_direction = 'out';
			else
				$sync_direction = 'in';

			if (isset($_POST['submitted']))
			{
				$cmd = "/Utilities/php_script mysql_sync $local_site_dir $RelativePath";
				if ($_POST['sync_mode'] == 'backup')
					$cmd .= " -b $Localhost_ID";
				elseif ($_POST['sync_mode'] == 'restore')
					$cmd .= " -r $Localhost_ID";
				elseif ($_POST['sync_mode'] == $sync_direction)
					$cmd .= " -s -force";
				else
					$cmd .= " -rs -force";
				exec("$cmd > '__temp_.txt'");
				$output = implode(file('__temp_.txt'));
				$output = str_replace("\n","<br />\n",$output);
				fprintf($ofp,$output);
				unlink ('__temp_.txt');
			}
			else
			{
				fprintf($ofp,"<p>This facility is used to synchronise the local and online copies of the database. It may take a minute or so to complete, so please be patient.<br /><br />==== Please use with caution!! ====</p>\n");
				fprintf($ofp,"<form method=\"post\">\n");
				fprintf($ofp,"<table cellpadding=\"8\">\n");
				fprintf($ofp,"<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"in\"");
				if ($sync_direction == 'in')
					fprintf($ofp," checked");
				fprintf($ofp,"></td><td>Download online DB to local DB</td></tr>\n");

				fprintf($ofp,"<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"out\"");
				if ($sync_direction == 'out')
					fprintf($ofp," checked");
				fprintf($ofp,"></td><td>Upload local DB to online DB</td></tr>\n");
				fprintf($ofp,"<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"backup\"></td><td>Back up local DB (locally)</td></tr>\n");
				fprintf($ofp,"<tr><td><input type=\"radio\" name=\"sync_mode\" value=\"restore\"></td><td>Restore local DB (locally)</td></tr>\n");
				fprintf($ofp,"<tr><td colspan=\"2\"><input value=\"Run\" type=\"submit\"></td></tr>\n");
				fprintf($ofp,"</table>\n");
				fprintf($ofp,"<input type=\"hidden\" name=\"submitted\" value=\"TRUE\" />\n");
				fprintf($ofp,"</form>\n");
			}
		}
		else
			fprintf($ofp,"<p>This facility is currently only supported on the local server.</p>\n");

		print_bodytext_footer($ofp);
		fclose($ofp);
		df_display(array(), 'result.html');
		unlink("./templates/result.html");
}

?>
