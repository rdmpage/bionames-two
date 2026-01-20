<?php

/**
 * @file config.inc.php
 *
 * Global configuration variables (may be added to by other modules).
 *
 */

global $config;

// Date timezone
date_default_timezone_set('UTC');

$config['site_name'] 	= 'My site';

$local = true;

if ($local)
{
	$config['web_server']	= 'http://localhost';
	$config['web_root']		= '/bionames-two/';
}
else
{
	$config['web_server']	= '';
	$config['web_root']		= '/';
}

// Database-------------------------------------------------------------------------------
$config['pdo'] = new PDO('sqlite:' . dirname(__FILE__) . '/bionames.db');

?>
