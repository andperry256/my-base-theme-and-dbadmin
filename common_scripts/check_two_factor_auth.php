<?php
require(__DIR__.'/allowed_hosts.php');
define('TWO_FACTOR_DISABLE', (isset($allowed_hosts[$_SERVER['REMOTE_ADDR']])));
