<?php
//==============================================================================

require(__DIR__."/../../../path_defs.php");
$content = file("$root_dir/maintenance/crontab.txt");
if (empty($content)) {
    exit("Crontab data not found\n");
}
$date_and_time = date('YmdHis');
$commands = '^';
$schedules = '^';
foreach ($content as $line) {
    if (preg_match('/^[\*0-9]/',$line)) {
        $schedules .= strtok($line,' ');
        for ($i=1; $i<=4; $i++) {
            $schedules .= ' '.strtok(' ');
        }
        $tok = strtok(' ');
        $commands .= "$tok";
        $tok = strtok(' ');
        while ($tok !== false) {
            $commands .= " $tok";
            $tok = strtok(' ');
        }
        $commands = trim($commands).'^';
        $schedules = trim($schedules).'^';
    }
}
$commands = urlencode($commands);
$schedules = urlencode($schedules);
print("$site_path $commands $schedules");

//==============================================================================
