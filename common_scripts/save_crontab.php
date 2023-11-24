<?php
//==============================================================================

if (isset($argv[1]))
{
    $local_site_dir = $argv[1];
}
else
{
    exit("Local site directory not specified\n");
}
$tok1 = strtok(__DIR__,'/');
$tok2 = strtok('/');
$tok3 = strtok('/');
$root_dir = "/$tok1/$tok2";
if ($tok3 != 'public_html')
{
    // Extra directory level in special cases
    $root_dir .= "/$tok3";
}
require("$root_dir/public_html/path_defs.php");
$content = file("$root_dir/maintenance/crontab.txt");
if (empty($content))
{
    exit("Crontab data not found\n");
}
$date_and_time = date('YmdHis');
$commands = '^';
$schedules = '^';
foreach ($content as $line)
{
    if (preg_match('/^[\*0-9]/',$line))
    {
        $schedules .= strtok($line,' ');
        for ($i=1; $i<=4; $i++)
        {
            $schedules .= ' '.strtok(' ');
        }
        $tok = strtok(' ');
        $commands .= "$tok";
        $tok = strtok(' ');
        while ($tok !== false)
        {
            $commands .= " $tok";
            $tok = strtok(' ');
        }
        $commands = trim($commands).'^';
        $schedules = trim($schedules).'^';
    }
}
$commands = urlencode($commands);
$schedules = urlencode($schedules);
print(file_get_contents("http://remote.andperry.com/store_crontab.php?site_path=$local_site_dir&commands=$commands&schedules=$schedules&datetime=$date_and_time"));

//==============================================================================
?>
