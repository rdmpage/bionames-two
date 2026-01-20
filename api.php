<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/api_utils.php');
require_once (dirname(__FILE__) . '/core.php');

//--------------------------------------------------------------------------------------------------
function api_display_default()
{
	echo "hi";
}

//--------------------------------------------------------------------------------------------------
// URL (e.g., PDF) exists
function api_url_exists ($url, $callback)
{
	$obj = new stdclass;
	$obj->url = $url;
	$obj->found = false;

	$status = 404;
	
	if (api_head($url))
	{
		$status = 200;
		$obj->found = true;
	}
		
	api_output($obj, $callback, $status);
}	
	
//--------------------------------------------------------------------------------------------------
// One record
function api_display_one ($namespace, $id, $format= '', $callback = '')
{
	$obj = null;
	$status = 404;
	
	switch ($format)
	{
		default:
			$obj = get_entity($namespace, $id);
			break;	
	}
	
	if ($obj != '')
	{		
		$status = 200;
	}

	api_output($obj, $callback, $status);
}	

//--------------------------------------------------------------------------------------------------
// Search
function api_display_search ($q, $callback = '')
{	
	$status = 404;
				
	if ($q == '')
	{
		$obj = new stdclass;
		
		$status = 200;
	}
	else
	{	
		$status = 200;
		$obj = search($q);	
	}
	
	api_output($obj, $callback, 200);
}


//--------------------------------------------------------------------------------------------------
function api_main()
{
	global $config;

	$callback = '';
	$handled = false;
		
	// If no query parameters 
	if (count($_GET) == 0)
	{
		api_default_display();
		exit(0);
	}
	
	if (isset($_GET['callback']))
	{	
		$callback = $_GET['callback'];
	}
	
	// Submit job
	if (!$handled)
	{
		if (isset($_GET['id']))
		{	
			$id = $_GET['id'];
			
			$namespace = '';	
			if (isset($_GET['namespace']))
			{	
				$namespace = $_GET['namespace'];
			}			
			
			$format = '';
			
			if (isset($_GET['format']))
			{
				$format = $_GET['format'];
			}			
			
			if (!$handled)
			{
				api_display_one($namespace, $id, $format, $callback);
				$handled = true;
			}
			
		}
	}
	
	if (!$handled)
	{
		if (isset($_GET['url']))
		{	
			$pdf = $_GET['url'];
			
			api_url_exists($pdf, $callback);
			$handled = true;
		}
	}
	
	
	if (!$handled)
	{
		if (isset($_GET['q']))
		{	
			$q = $_GET['q'];
			
			// Elastic
			$from = 0;
			$size = 10;
			
			$filter = null;
			
			api_display_search($q, $callback);
			
			$handled = true;
		}
			
	}
	
	if (!$handled)
	{
		api_default_display();
	}

}


api_main();

?>