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

$config['site_name'] 	= 'BioNames';

$local = true;
$local = false;

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
if ($local)
{
	$config['pdo'] = new PDO('sqlite:/Users/rpage/Development/bionames-sqlite/bionames.db');
}
else
{
	$config['pdo'] = new PDO('sqlite:bionames-web.db');
}

// Interface------------------------------------------------------------------------------

// Dimensions of treemap (used when calculating cell sizes)
$config['treemap_width']  = 1200;
$config['treemap_height'] =  800;

// Whether we display the data or not
$config['show_data'] = true;

?>
