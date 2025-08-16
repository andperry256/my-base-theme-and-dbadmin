<?php
/*
Generic script for all sites. This needs to be copied into public_html.
The main functionality for the individual site is contained in:

<root dir>/private_scripts/wp-config.php
*/
require(__DIR__.'/../private_scripts/wp-config.php');

/** Absolute path to the WordPress directory. **/
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. **/
require_once ABSPATH . 'wp-settings.php';
