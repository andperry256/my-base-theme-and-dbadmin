<?php
//==============================================================================
/*
This script contains common functionality and is invoked from a site-related
script. On entry the following conditions must be met:-
1. The calling script has already included a valid path_defs.php script.
2. The array $dirs has been set up with a list of the directories to be
   processed for the given site.
*/
//==============================================================================

if (!isset($BaseDir))
{
	exit("Path definitions file not found");
}
require_once("$BaseDir/keycode.php");

if (isset($argc))
{
	$mode = 'command';
	$eol = "\n";
}
else
{
	$mode = 'web';
	$eol = "<br />\n";
	print("<h1>Configure Linked Directories</h1>$eol");
}

$rewrite_rules = array();
foreach ($dirs as $link => $id)
{
	print("$eol*** Processing $id ***$eol");
	if (is_dir("$BaseDir/$id"))
	{
		$dir = "$BaseDir/$id";
		print("$dir$eol");

		// Find existing storage and links directories
		$old_links_dir = '';
		$old_storage_dir = '';
		$dirlist = scandir($dir);
		foreach($dirlist as $file)
		{
			if (substr($file,0,8) == 'storage-')
			{
				$old_storage_dir = $file;
			}
			elseif (substr($file,0,6) == 'links-')
			{
				$old_links_dir = $file;
			}
		}

		if (($location == 'real') && (empty($old_storage_dir)))
		{
			print ("Error - storage directory not found$eol");
		}
		else
		{
			// Rename/regenerate links directory
			$key1 = generate_hex_key(date('Ymdhis'),32);
			$new_links_dir = 'links-'.$key1;
			if ((!empty($old_links_dir)) &&
			    ((is_dir("$dir/$old_links_dir")) || (is_link("$dir/$old_links_dir"))))
			{
				rename("$dir/$old_links_dir","$dir/$new_links_dir");
			}
			elseif ($Location == 'local')
			{
				symlink("$RootDir/$id","$dir/$new_links_dir");
				print ("Symlink re-established$eol");
			}
			else
			{
				mkdir($dir/$new_links_dir);
			}
			print ("Links directory is now $new_links_dir$eol");

			// Establish storage directory name
			if ($old_storage_dir == 'storage-000000')
			{
				// Rename if name is in initial state
				$key2 = generate_hex_key($key1,32);
				$new_storage_dir = 'storage-'.$key2;
				rename("$dir/$old_storage_dir","$dir/$new_storage_dir");
				print ("Storage directory is now $new_storage_dir$eol");
			}
			else
			{
				$new_storage_dir = $old_storage_dir;
			}

			// Re-generate path definitions file
			if (!empty($new_links_dir))
			{
				$ofp = fopen("$dir/paths.php",'w');
				fprintf($ofp,"<?php$eol");
				$id2 = str_replace('_',' ',$id);
				$id2 = ucwords($id2);
				$id2 = str_replace(' ','',$id2);
				fprintf($ofp,"  \$$id2"."LinksDir = \"$BaseDir/$id/"."$new_links_dir\";$eol");
				fprintf($ofp,"  \$$id2"."LinksURL = \"$BaseURL/$id/"."$new_links_dir\";$eol");
				fprintf($ofp,"?>$eol");
				fclose($ofp);
			}
			else
			{
				$ofp = fopen("$dir/paths.php",'w');
				fprintf($ofp,"<?php ?>$eol");
				fclose($ofp);
			}

			// Re-generate symlinks if required
			if (($Location == 'real') && (!is_link("$RootDir/$id")))
			{
				symlink("$dir/$new_storage_dir","$RootDir/$id");
				print ("Symlink re-established$eol");
			}

			// Save .htaccess rewrite rule
			$rewrite_rules[$id] = "RewriteRule ^$id/$new_links_dir(.*)\$ $id/$new_storage_dir/\$1";
		}
	}
}
print($eol);

// Update .htaccess file
if ($Location == 'real')
{
	$htaccess = file("$BaseDir/.htaccess");
	$ofp = fopen("$BaseDir/.htaccess2","w");
	foreach ($htaccess as $line)
	{
		if ((strpos($line,'links-') === false) && (strpos($line,'storage-') === false))
		{
			$line = str_replace('%','%%',$line);
			fprintf($ofp,"$line");
		}
		if (substr($line,0,11) == "RewriteBase")
		{
			foreach ($rewrite_rules as $id => $rule)
			{
				fprintf($ofp,"$rule$eol");
			}
		}
	}
	fclose($ofp);
	chmod("$BaseDir/.htaccess2",0644);
	unlink("$BaseDir/.htaccess");
	rename("$BaseDir/.htaccess2","$BaseDir/.htaccess");
	print ("Re-write rules updated in .htaccess$eol");
}

print("Operation completed$eol");

//==============================================================================
?>
