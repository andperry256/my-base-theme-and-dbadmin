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
foreach ($dirs as $dir)
{
    print("$eol*** Processing $dir ***$eol");
    $dir_path = "$FilesSubdomainDir/$dir";
    $dirlist = scandir($dir_path);
    foreach ($dirlist as $file)
    {
        if (substr($file,0,8)  == 'storage-')
        {
            $storage_dir = $file;
            break;
        }
    }
    $key = password_hash($key,PASSWORD_DEFAULT);
    $key = md5($key);
    $key = substr($key,0,32);
    $links_path = "links-$key";
    $content = file_get_contents("$dir_path/paths.php");
    $content = preg_replace('/links-[0-9a-f]+/',"$links_path",$content);
    file_put_contents("$dir_path/paths.php",$content);
    $ofp = fopen("$dir_path/.htaccess",'w');
    fprintf($ofp,"RewriteEngine On\nRewriteRule ^$links_path/(.*)\$ $storage_dir/\$1\n");
    fclose($ofp);
    print("Links directory is now $links_path$eol");
}
print("Operation completed$eol");

//==============================================================================
?>
