<?php
  $today_date = date('ymd');
  if (isset($_GET['site']))
  {
    $local_site_dir = $_GET['site'];
  }
  elseif (is_dir('/media/Data'))
  {
    exit("Site not specified");
  }
  require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
  if (!is_dir($BaseDir))
  {
    exit("Site directory not found\n");
  }
  if (is_file("$BaseDir/last_preset_link_version.php"))
  {
    include("$BaseDir/last_preset_link_version.php");
  }
  if (!isset($last_preset_link_version))
  {
    $last_preset_link_version = $today_date;
  }
  else
  {
    $link_version_date = strtok($last_preset_link_version,'-');
    $link_version_seq = strtok('-');
    if ($link_version_date != $today_date)
    {
      // Update version to today's date.
      $last_preset_link_version = $today_date;
    }
    else
    {
      // Update version to next in sequence for today's date.
      if ((empty($link_version_seq)) || (!is_numeric($link_version_seq)))
      {
        $link_version_seq = 0;
      }
      $link_version_seq = (int)$link_version_seq+1;
      $last_preset_link_version = "$today_date-$link_version_seq";
    }
  }
  $ofp = fopen("$BaseDir/last_preset_link_version.php",'w');
  fprintf($ofp,"<?php\n");
  fprintf($ofp,"  // Update this variable to force theme stylesheets and images to be reloaded in cache.\n");
  fprintf($ofp,"  // Set it to the date in format 'yymmdd' with an additional suffix if multiple versions are needed on a single day.\n");
  fprintf($ofp,"  // The value will be superseded anyway if set to before the start of the current month.\n");
  fprintf($ofp,"  \$last_preset_link_version = '$last_preset_link_version';\n");
  fprintf($ofp,"?>\n");
  fclose($ofp);
  print("<p>Last preset link version set to <em>$last_preset_link_version</em></p>");
  print("<p>Site =  <em>$local_site_dir</em></p>");
  print("<p>Location =  <em>$Location</em></p>");
?>
