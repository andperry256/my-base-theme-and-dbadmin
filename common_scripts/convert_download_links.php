<?php
//==============================================================================

function convert_download_links($content)
{
	global $BaseURL, $FilesSubdomainURL, $DownloadLinksURL;
	$subdir = '';
	$content_lines = explode("\n",$content);

	foreach ($content_lines as $index => $line)
	{
		// Check for a subdirectory directive
		$pos0 = $pos1 = strpos($line,'[SD=');
		if ($pos1 !== false)
		{
			$pos2 = strpos($line,']',$pos1);
			if ($pos2 !== false)
			{
				$pos1 += 4;
				// Note the subdirectory and delete the directive
				$subdir = trim(substr($line,$pos1,$pos2-$pos1));
				$line = substr($line,0,$pos0).substr($line,$pos2+1);
			}
		}

		// Check for a link directive to download
		$pos0 = $pos1 = strpos($line,'[DL=');
		if ($pos1 !== false)
		{
			$pos2 = strpos($line,']',$pos1);
			if ($pos2 !== false)
			{
				$pos1 += 4;
				$filename = trim(substr($line,$pos1,$pos2-$pos1));
			}
			$pos3 = strpos($line,'[/DL]',$pos2);
			if ($pos3 !== false)
			{
				$pos2 += 1;
				$path = urlencode("$subdir/$filename");
				$description = trim(substr($line,$pos2,$pos3-$pos2));
				$line = substr($line,0,$pos0)."<a href=\"$FilesSubdomainURL/download.php?path=$path\">$description</a>".substr($line,$pos3+5);
			}
		}

		// Check for a link directive to open in own tab/page
		$pos0 = $pos1 = strpos($line,'[LO=');
		if ($pos1 !== false)
		{
			$pos2 = strpos($line,']',$pos1);
			if ($pos2 !== false)
			{
				$pos1 += 4;
				$filename = trim(substr($line,$pos1,$pos2-$pos1));
			}
			$pos3 = strpos($line,'[/LO]',$pos2);
			if ($pos3 !== false)
			{
				$pos2 += 1;
				$path = "$subdir/$filename";
				$description = trim(substr($line,$pos2,$pos3-$pos2));
				$line = substr($line,0,$pos0)."<a href=\"$DownloadLinksURL/$path\">$description</a>".substr($line,$pos3+5);
			}
		}

		// Check for a link directive to open in new tab/page
		$pos0 = $pos1 = strpos($line,'[LN=');
		if ($pos1 !== false)
		{
			$pos2 = strpos($line,']',$pos1);
			if ($pos2 !== false)
			{
				$pos1 += 4;
				$filename = trim(substr($line,$pos1,$pos2-$pos1));
			}
			$pos3 = strpos($line,'[/LN]',$pos2);
			if ($pos3 !== false)
			{
				$pos2 += 1;
				$path = "$subdir/$filename";
				$description = trim(substr($line,$pos2,$pos3-$pos2));
				$line = substr($line,0,$pos0)."<a href=\"$DownloadLinksURL/$path\" target=\"_blank\">$description</a>".substr($line,$pos3+5);
			}
		}

		// Update the line
		$content_lines[$index] = $line;
	}
	return implode("\n",$content_lines);
}

//==============================================================================
?>
