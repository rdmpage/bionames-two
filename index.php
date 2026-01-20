<?php

error_reporting(E_ALL);
require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/core.php');


// for dev environment we do the job of .htaccess 
if(preg_match('/^\/api.php/', $_SERVER["REQUEST_URI"])) return false;
if(preg_match('/^\/api_utils.php/', $_SERVER["REQUEST_URI"])) return false;
if(preg_match('/^\/images/', $_SERVER["REQUEST_URI"])) return false;


//----------------------------------------------------------------------------------------
// Format a link as a simple external link
function external_identifier_link($namespace, $value)
{
	$html = '';
	
	switch ($namespace)
	{
		case 'doi':
			$html = '<a href="https://doi.org/' . $value . '">' . $value . '</a>';			
			break;

		case 'issn':
			$html = '<a href="http://portal.issn.org/resource/ISSN/' . $value . '">' . $value . '</a>';			
			break;

		case 'oclc':
			$html = '<a href="https://worldcat.org/oclc/' . $value . '">' . $value . '</a>';			
			break;
						
		default:
			$html = $value;
			break;
	}
	
	return $html;
}

//----------------------------------------------------------------------------------------
// Format a link as a internal link
function internal_identifier_link($id)
{
	$link = '';
	
	$namespace = '';
	$value = '';
	
	if (preg_match('/resource\/ISSN\/(.*)/', $id, $m))
	{
		$namespace = 'issn';
		$value = $m[1];
	}

	if (preg_match('/oclc\/(\d+)/', $id, $m))
	{
		$namespace = 'oclc';
		$value = $m[1];
	}
		
	switch ($namespace)
	{
		case 'issn':
			$link = '?namespace=issn&id=' . $value ;			
			break;

		case 'oclc':
			$link = '?namespace=oclc&id=' . $value ;			
			break;
						
		default:
			break;
	}
	
	return $link;
}

//----------------------------------------------------------------------------------------
// Convert a URI or Curie to a namespace and id k,v pair
// if we move to using URIs as identifiers then this code will no longer matter
function id_to_key_value($id)
{
	$kv = [];
	
	if (preg_match('/(issn):([0-9]{4}-[0-9]{3}[0-9X])/', $id, $m))
	{
		$kv = [$m[1], $m[2]];
	}
	
	if (preg_match('/.org\/(.*)\/(.*)$/', $id, $m))
	{
		$kv = [$m[1], $m[2]];
	}

	return $kv;
}

//----------------------------------------------------------------------------------------
function display_entity_details($doc)
{	
	// Any entity
	$main_entity = $doc[0];
	echo '<h1>' . entity_name($main_entity) . '</h1>';
	
	// identifiers

	echo '<dl>';
	if (isset($main_entity->doi))
	{
		echo '<dt>DOI</dt>';				
		echo '<dd>' . external_identifier_link('doi', $main_entity->doi) . '</dd>';		
	}
	if (isset($main_entity->issn))
	{
		echo '<dt>ISSN</dt>';				
		echo '<dd>' . external_identifier_link('issn', $main_entity->issn) . '</dd>';		
	}

	if (isset($main_entity->oclc))
	{
		echo '<dt>OCLC</dt>';				
		echo '<dd>' . external_identifier_link('oclc', $main_entity->oclc) . '</dd>';		
	}
	
	echo '</dl>';
	
	
	// other names
	$alternate_names = entity_alternate_names($main_entity);
	if (count($alternate_names) > 0)
	{
		echo '<h2>Other names</h2>';
		echo '<ul>';
		foreach ($alternate_names as $name)
		{
			echo '<li>' . $name  . '</li>';
		}
		
		echo '</ul>';
	}
	
	// specific 
	if (isset($main_entity->isBasedOn))
	{
		$link_name = '[Unknown]';
		if (is_string($main_entity->isBasedOn))
		{
			$link_id = $main_entity->isBasedOn;
		}
		else
		{
			$link_id = $main_entity->isBasedOn->id;
			$link_name = $main_entity->isBasedOn->name;
		}
		
		$ns_id = id_to_key_value($link_id);
		
		echo '<a href="?id=' . $ns_id[1] . '&namespace=' . $ns_id[0] . '">';
		echo $link_name;
		echo '</a>';
	}
	
	if (isset($main_entity->isPartOf))
	{	
		$link_id = '';
		$link_name = '[Unknown]';
		
		if (is_string($main_entity->isPartOf))
		{
			$link_id = $main_entity->isPartOf;
		}
		else
		{
			if (isset($main_entity->isPartOf->id))
			{
				$link_id = $main_entity->isPartOf->id;
			}
			$link_name = $main_entity->isPartOf->name;
		}
		
		if ($link_id != '')
		{
			$ns_id = id_to_key_value($link_id);
			
			echo '<a href="?id=' . $ns_id[1] . '&namespace=' . $ns_id[0] . '">';
			echo $link_name;
			echo '</a>';		
		}
		else
		{
			echo $link_name;
		}
	}	
	
	
	// PDF
	echo '<div id="pdf" style="display:none;"></div>';
	
	// Entity connections
	$n = count($doc);
	for ($i = 0; $i < $n; $i++)
	{
		if ($doc[$i]->type == 'DataFeed')
		{
			echo '<h2>' . entity_name($doc[$i]) . '</h2>';
			echo '<ul>';
			foreach ($doc[$i]->dataFeedElement as $dataFeedElement)
			{
				echo '<li>';
				
				$item_ns = '';
				$item_id = '';
				
				if (preg_match('/.org\/(.*)\/(.*)$/', $dataFeedElement->item->id, $m))
				{
					$item_ns = $m[1];
					$item_id = $m[2];
				}
				
				echo '<a href="?id=' . $item_id . '&namespace=' . $item_ns . '">';
				
				echo entity_name($dataFeedElement->item);
				echo '</a>';

				echo '<br/>';
				if (isset($dataFeedElement->item->doi))
				{
					echo $dataFeedElement->item->doi;
				}
				
				echo '</li>';
			}
			echo '</ul>';
		}
	}

	// Citation display
	if (isset($main_entity->encoding))
	{
		$csl_json = null;
		foreach ($main_entity->encoding as $encoding)
		{
			if ($encoding->encodingFormat == 'application/vnd.citationstyles.csl+json')
			{
				$csl_json = $encoding->text;
				break;
			}
		}

		if ($csl_json)
		{
			echo '<div style="margin:2em 0;">';
			echo '<h2>Formatted Citation</h2>';
			echo '<div style="margin:1em 0;">';
			echo '<button onclick="show_citation(\'' . htmlspecialchars(addslashes($csl_json), ENT_QUOTES) . '\', \'apa\')">APA</button> ';
			echo '<button onclick="show_citation(\'' . htmlspecialchars(addslashes($csl_json), ENT_QUOTES) . '\', \'bibtex\')">BibTeX</button> ';
			echo '<button onclick="show_citation(\'' . htmlspecialchars(addslashes($csl_json), ENT_QUOTES) . '\', \'ris\')">RIS</button>';
			echo '</div>';
			echo '<div id="citation-output" style="display:none; padding:1em; background:#f5f5f5; border:1px solid #ddd; margin-top:1em;"></div>';
			echo '</div>';
		}
	}

	if (1)
	{
		// debug display simplified data
		echo '<div style="font-family:monospace;white-space:pre-wrap;">';
		echo json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		echo '</div>';

		if (isset($main_entity->encoding))
		{
			foreach ($main_entity->encoding as $encoding)
			{
				if ($encoding->encodingFormat == 'application/pdf')
				{
					echo '<script>display_pdf("' . $encoding->contentUrl . '");</script>';
				}
			}
		}
	}
}

//----------------------------------------------------------------------------------------
function display_entity($namespace, $id)
{
	global $config;
			
	$doc = get_entity($namespace, $id);
			
	if (!$doc)
	{
		// bounce
		header('Location: /?error=Record not found' . "\n\n");
		exit(0);
	}
	
	$title = entity_name($doc[0]);
	
 	display_html_start($title);
	display_navbar('');	
	display_main_start();
	
	display_entity_details($doc);
	
	display_main_end();
	display_html_end();	
}

//----------------------------------------------------------------------------------------
// Start of HTML document
function display_html_start($title = '')
{
	global $config;
	
	echo '<!DOCTYPE html>
<head>';

	echo '<meta charset="utf-8">';
	
	echo '<!-- base -->
    	<base href="' . $config['web_root'] . '" /><!--[if IE]></base><![endif]-->' . "\n";
    	
    if ($title == '')
    {
    	$title = $config['site_name'];
    }
    echo '<title>' . htmlentities($title, ENT_HTML5). '</title>';
	
	echo '<script src="js/citation.js"></script>' . "\n";
	echo '<script>
		const Cite = require("citation-js");
	</script>' . "\n";

	echo '<script>' . "\n";
	require_once (dirname(__FILE__) . '/display.js.inc.php');
	echo '</script>' . "\n";

	echo '<style>';	
	require_once (dirname(__FILE__) . '/root.css.inc.php');
	require_once (dirname(__FILE__) . '/body.css.inc.php');
	require_once (dirname(__FILE__) . '/nav.css.inc.php');
	
echo '


/* main column */
main {
	width:90vw;
	padding:1em;
	margin:auto;
}';	

	echo '#citation-output > pre {
		white-space:pre-wrap;
	}';
	
	echo '</style>' . "\n";	

	echo '</head>';
	echo '<body>';
}

//----------------------------------------------------------------------------------------
// End of HTML document
function display_html_end()
{
	global $config;
	
	echo 
'<script>
<!-- any end of document script goes here -->
</script>';

	echo '</body>';
	echo '</html>';
}

//----------------------------------------------------------------------------------------
function display_navbar($q)
{
	global $config;

	echo '<nav>
	<ul>
		<li><a href=".">Home</a></li>
		<li>
			<form method="get" action="index.php" style="display:inline;">
				<input class="search" id="search" name="q" type="text" placeholder="Search for name..." value="' . htmlspecialchars($q) . '">
				<input type="submit" value="Search">
			</form>
		</li>
		<li><a href="https://github.com/rdmpage/bold-view/issues" target="_new">Feedback</a></li>
	</ul>
	</nav>';

}

//----------------------------------------------------------------------------------------
function display_main_start()
{
	echo '<main>';
}

//----------------------------------------------------------------------------------------
function display_main_end()
{
	echo '</main>';
}

//----------------------------------------------------------------------------------------
function display_search($q)
{
	global $config;

	$title = 'Search: ' . $q;

	$results = search($q);

	display_html_start($title);
	display_navbar($q);
	display_main_start();

	echo '<h1>' . htmlspecialchars($results->name) . '</h1>';

	if (count($results->dataFeedElement) == 0)
	{
		echo '<p>No results found for "' . htmlspecialchars($q) . '"</p>';
	}
	else
	{
		echo '<ul>';
		foreach ($results->dataFeedElement as $dataFeedElement)
		{
			echo '<li>';

			// Extract numeric ID from LSID
			$item_id = '';
			if (preg_match('/:name:(\d+)$/', $dataFeedElement->item->id, $m))
			{
				$item_id = $m[1];
			}

			if ($item_id)
			{
				echo '<a href="?namespace=names&id=' . $item_id . '">';
			}

			echo htmlspecialchars($dataFeedElement->item->name);

			if (isset($dataFeedElement->item->author))
			{
				echo ' ' . htmlspecialchars($dataFeedElement->item->author);
			}

			if ($item_id)
			{
				echo '</a>';
			}

			echo '</li>';
		}
		echo '</ul>';
	}

	// Debug display
	if (1)
	{
		echo '<div style="font-family:monospace;white-space:pre-wrap;">';
		echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		echo '</div>';
	}

	display_main_end();
	display_html_end();
}

//----------------------------------------------------------------------------------------
function display_container_list($letter = 'A')
{
	$doc = get_container_list($letter);
	
	display_html_start($letter);
	display_navbar('');
	display_main_start();
	
	$letters = ["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z"];
		
	echo '<ul style="list-style-type: none;display:block;overflow: auto;">';
	foreach ($letters as $one_letter) 
	{
		echo '<li style="float: left;padding:0.3em"><a href="?containers&letter=' . $one_letter . '">' . $one_letter . '</a></li>';
	}
	echo '</ul>';

	echo '<h1>' . $letter . '</h1>';
	
	// list of titles...		
	echo '<div class="multicolumn">';
	echo '<ul>';
	
	foreach ($doc->dataFeedElement as $container)
	{
		echo '<li>';
		//echo '<a href="' . $work->{'@id'} . '">' . $work->item->name . '</a>';
		
		if (isset($container->item->id))
		{
			echo '<a href="' . internal_identifier_link($container->item->id) . '">';
		}
		
		echo $container->item->name;
		
		if (isset($container->item->id))
		{
			echo '</a>';
		}
		
		
		echo '</li>';			
	}
			
	echo '</ul>';
	echo '</div>';
	
	display_main_end();	
	display_html_end();	
}


//----------------------------------------------------------------------------------------
// Home page, or badness happened
function default_display($error_msg = '')
{
	global $config;
	
	$title = $config['site_name'];
	
	display_html_start($title);
	display_navbar('');
		
	if ($error_msg != '')
	{
		echo '<div><strong>Error!</strong> ' . $error_msg . '</div>';
	}
	else
	{
		// main content		
		echo '<div>Default display</div>';
	}

	display_html_end();
}

//----------------------------------------------------------------------------------------
function main()
{
	$query = '';
		
	// If no query parameters 
	if (count($_GET) == 0)
	{
		default_display();
		exit(0);
	}
		
	// Error message
	if (isset($_GET['error']))
	{	
		$error_msg = $_GET['error'];		
		default_display($error_msg);
		exit(0);			
	}
	
	// Show entity
	if (isset($_GET['id']))
	{	
		$id = $_GET['id'];						
	
		$namespace = '';	
		if (isset($_GET['namespace']))
		{	
			$namespace = $_GET['namespace'];
		}
	
		display_entity($namespace, $id);
		exit(0);
	}
	
		
	// Show search
	if (isset($_GET['q']))
	{	
		$query = $_GET['q'];
		display_search($query);
		exit(0);
	}	
	
	// Show list of containers (entity by name)			
	if (isset($_GET['containers']))
	{	
		$letter = 'A';		
		if (isset($_GET['letter']))
		{
			$letter = $_GET['letter'];
		}	

		display_container_list($letter);
		exit(0);
	}
	
	// Taxonomy browser (treemap)
	if (isset($_GET['treemap']))
	{
		$group = '';
		if (isset($_GET['group']))
		{
			$group = $_GET['group'];
		}

		display_treemap($group);
		exit(0);
	}

}

//----------------------------------------------------------------------------------------
function display_treemap($group = '')
{
	global $config;

	$tree_data = get_taxonomy_tree($group);

	$title = $group == '' ? 'Taxonomic Browser' : 'Taxonomy: ' . $group;

	display_html_start($title);
	display_navbar('');
	display_main_start();

	echo '<h1>' . htmlspecialchars($title) . '</h1>';

	// Breadcrumb navigation
	if ($group != '')
	{
		$parts = explode(';', $group);
		echo '<div class="breadcrumb">';
		echo '<a href="?treemap">All</a>';

		$path = '';
		foreach ($parts as $part)
		{
			$path .= ($path == '' ? '' : ';') . $part;
			echo ' &gt; <a href="?treemap&group=' . urlencode($path) . '">' . htmlspecialchars($part) . '</a>';
		}
		echo '</div>';
	}

	// Container for treemap
	echo '<div id="treemap" style="width:100%; height:600px;"></div>';

	// Embed the tree data as JSON
	echo '<script>';
	echo 'var treeData = ' . json_encode($tree_data) . ';';
	echo '</script>';

	// Debug display
	if (1)
	{
		echo '<div style="font-family:monospace;white-space:pre-wrap;">';
		echo json_encode($tree_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		echo '</div>';
	}

	display_main_end();
	display_html_end();
}

main();

?>