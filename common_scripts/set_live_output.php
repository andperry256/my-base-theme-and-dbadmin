<?php
//================================================================================
// This code can be included from anywhere to force live screen output during
// the execution of the script.
//================================================================================

ini_set('output_buffering','off');
ini_set('zlib.output_compression',false);
while(@ob_end_flush());
ini_set('implicit_flush',true);
ob_implicit_flush(true);

//================================================================================
