<?php

error_reporting(E_ALL);
require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/core.php');

require_once __DIR__ . '/vendor/autoload.php';

use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\CiteProc;

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
			$html = '<a class="external" href="https://doi.org/' . $value . '" target="_new">' . $value . '</a>';			
			break;

		case 'handle':
			$html = '<a class="external" href="https://hdl.handle.net/' . $value . '" target="_new">' . $value . '</a>';			
			break;

		case 'issn':
			$html = '<a class="external" href="http://portal.issn.org/resource/ISSN/' . $value . '" target="_new">' . $value . '</a>';			
			break;
			
		case 'lsid':
			//$html = '<a href="https://lsid.io/' . $value . '" target="_new">' . $value . '</a>';			
			$html = '<a class="external" href="http://www.organismnames.com/details.htm?lsid=' . str_replace('urn:lsid:organismnames.com:name:', '', $value) . '" target="_new">' . $value . '</a>';			
			break;

		case 'oclcnum':
			$html = '<a class="external" href="https://worldcat.org/oclc/' . $value . '" target="_new">' . $value . '</a>';			
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
	
	if (preg_match('/(oclc)\/(\d+)$/', $id, $m))
	{
		$kv = [$m[1], $m[2]];
	}
	
	if (preg_match('/(doi).org\/(10.*)$/', $id, $m))
	{
		$kv = [$m[1], $m[2]];
	}
	
	if (preg_match('/(references)\/([0-9a-z]+)$/', $id, $m))
	{
		$kv = [$m[1], $m[2]];
	}	
		
	if (preg_match('/urn:lsid:organismnames.com:name:(\d+)$/', $id, $m))
	{
		$kv = ['names', $m[1]];
	}

	return $kv;
}


//----------------------------------------------------------------------------------------
function display_classification_breadcrumbs($higherClassification)
{
	$breadcrumbs = array();

	$parts = explode(';', $higherClassification);
	
	$image = '';
	$link = '';			
	foreach ($parts as $part)
	{
		$breadcrumb = new stdclass;
		
		if ($link == '')
		{
			$link = $part;
		}
		else
		{
			$link = $link . ';' . $part;
		}
		$breadcrumb->link = $link;
		$breadcrumb->label = preg_replace('/^\w+__/', '', $part);
		$breadcrumbs[] = $breadcrumb;
		
		$extension = 'png';
		$extension = 'svg';
		
		$image_filename = dirname(__FILE__) . '/images/' . $breadcrumb->label . '.' . $extension;
		if (file_exists($image_filename))
		{
			$image = 'images/' . $breadcrumb->label . '.' . $extension;
		}
	}
	
	echo '<div style="display: flex; align-items: center; gap: 10px;margin-top:1em;">';

	if ($image != '')
	{
		echo '<img style="opacity:0.6" height="60" src="' . $image . '">';
	}

	echo '<div>';
	$n = count($breadcrumbs);
	for ($i = 0; $i < $n; $i++)
	{
		echo '<a href="?path=' . urlencode($breadcrumbs[$i]->link) . '">' . $breadcrumbs[$i]->label . '</a>';
		if ($i < $n - 1)
		{
			echo ' / ';
		}
	}
	echo '</div>';

	echo '</div>';
}

//----------------------------------------------------------------------------------------
function display_entity_type($entity)
{	
	echo '<div class="type">' . $entity->type . '</div>';
}


//----------------------------------------------------------------------------------------
function encoding_to_citation($encoding, $format = 'apa')
{	
	$citation = '';
	
	if ($encoding)
	{
		$csljson = [];
		
		foreach ($encoding as $encoding)
		{
			if ($encoding->encodingFormat == 'application/vnd.citationstyles.csl+json')
			{
				$csljson = [json_decode($encoding->text)];
				break;
			}
		}
		
		if (count($csljson) == 1)
		{
			$style_sheet = StyleSheet::loadStyleSheet($format);
			$citeProc = new CiteProc($style_sheet);
			$citation = $citeProc->render($csljson, "bibliography");
		}							
	}
	
	return $citation;
}

//----------------------------------------------------------------------------------------
function display_datafeed($feed)
{
	echo '<h2>' . entity_name($feed) . '</h2>';
	echo '<ul style="padding-left:1em;">';
	foreach ($feed->dataFeedElement as $dataFeedElement)
	{
		if (in_array($dataFeedElement->item->type, ['Book', 'Chapter', 'CreativeWork', 'ScholarlyArticle']))
		{
			$list_class = 'closed';
			
			if (isset($dataFeedElement->item->isAccessibleForFree))
			{
				if ($dataFeedElement->item->isAccessibleForFree)
				{
					$list_class = 'open';
				}
			}
			echo '<li class="' . $list_class . '">';
		}
		else
		{
			echo '<li>';
		}
		
		$kv = id_to_key_value($dataFeedElement->item->id);

		echo '<a href="?id=' . $kv[1] . '&namespace=' . $kv[0] . '">';
		
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


//----------------------------------------------------------------------------------------
function display_entity_details($doc)
{	
	echo '<div class="headline">';

	// Any entity
	$main_entity = $doc[0];
	
	display_entity_type($main_entity);
	
	echo '<h1>' . entity_name($main_entity) . '</h1>';
	
	// identifiers
	echo '<dl>';
	
	if (isset($main_entity->id))
	{
		if (preg_match('/^urn:lsid/', $main_entity->id))
		{
			echo '<dt>LSID</dt>';				
			echo '<dd>' . external_identifier_link('lsid', $main_entity->id) . '</dd>';					
		}
	}	
	
	if (isset($main_entity->doi))
	{
		echo '<dt>DOI</dt>';				
		echo '<dd>' . external_identifier_link('doi', $main_entity->doi) . '</dd>';		
	}
	
	if (isset($main_entity->handle))
	{
		echo '<dt>Handle</dt>';				
		echo '<dd>' . external_identifier_link('handle', $main_entity->handle) . '</dd>';		
	}
	
	if (isset($main_entity->issn))
	{
		echo '<dt>ISSN</dt>';				
		echo '<dd>' . external_identifier_link('issn', $main_entity->issn) . '</dd>';		
	}

	if (isset($main_entity->oclcnum))
	{
		echo '<dt>OCLC</dt>';
		echo '<dd>' . external_identifier_link('oclcnum', $main_entity->oclcnum) . '</dd>';
	}

	// sameAs - for TaxonName entities that are duplicates pointing to representative
	if (isset($main_entity->sameAs))
	{
		echo '<dt>Same as</dt>';

		// Handle both string and array cases
		$sameAs_values = is_array($main_entity->sameAs) ? $main_entity->sameAs : [$main_entity->sameAs];

		echo '<dd>';
		$first = true;
		foreach ($sameAs_values as $sameAs_id)
		{
			if (!$first)
			{
				echo '<br/>';
			}
			$first = false;

			// Extract namespace and id from the sameAs identifier
			$ns_id = id_to_key_value($sameAs_id);
			echo '<a href="?id=' . $ns_id[1] . '&namespace=' . $ns_id[0] . '">';
			echo htmlspecialchars($sameAs_id);
			echo '</a>';
		}
		echo '</dd>';
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
	
	switch ($main_entity->type)
	{
		//--------------------------------------------------------------------------------
		case 'TaxonName': 
			echo '<div>';
			echo '<div>';
			
			// show name in a way we make parts of it clickable 
			
			if (isset($main_entity->uninomial))
			{		
				if (isset($main_entity->taxonRank))
				{
					if ($main_entity->taxonRank == 'genus')
					{
						echo '<span class="genericName">';
						echo '<a href="?q=genus:' . $main_entity->uninomial . '">';
						echo $main_entity->uninomial;
						echo '</a>';	
						echo '</span>';			
					}
					else
					{
						echo '<span class="' . $main_entity->taxonRank . '">';
						echo $main_entity->uninomial;
						echo '</span>';
					}
				}
				else
				{
					// shouldn't happen but see e.g. Cangshanaltica
					echo '<span class="unranked">';
					echo $main_entity->uninomial;
					echo '</span>';				
				}
			}
			else
			{
				if (isset($main_entity->genericName))
				{
					// genusPart
					echo '<span class="genericName">';
					echo '<a href="?q=genus:' . $main_entity->genericName . '">';
					echo $main_entity->genericName;
					echo '</a>';	
					echo '</span>';	
					
					// subgenus
					if (isset($main_entity->infragenericEpithet))
					{
						echo ' ';
						echo '<span class="infragenericEpithet">';
						echo '(';
						echo $main_entity->infragenericEpithet;
						echo ')';
						echo '</span>';
					}
				
					if (isset($main_entity->specificEpithet))
					{
						echo ' ';
						echo '<span class="specificEpithet">';
						echo $main_entity->specificEpithet;
						echo '</span>';
					}
		
					if (isset($main_entity->infraspecificEpithet))
					{
						echo ' ';
						echo '<span class="infraspecificEpithet">';
						echo $main_entity->infraspecificEpithet;
						echo '</span>';
					}
				}
				else
				{
					// shouldn't happen, but name might not be parsed
					echo '<span>';
					echo $main_entity->name;
					echo '</span>';			
				}
			}
			
			if (isset($main_entity->author))
			{
				echo '&nbsp;';
				echo $main_entity->author;
			}
			echo '</div>';
			
			if (isset($main_entity->higherClassification))
			{
				display_classification_breadcrumbs($main_entity->higherClassification);
			}
			
			// specific
			if (isset($main_entity->isBasedOn))
			{
				$link_id = '';
				$link_name = '[Unknown]';
				
				$citation = '';
				
				if (is_string($main_entity->isBasedOn))
				{
					$link_id = $main_entity->isBasedOn;
				}
				else
				{
					if (isset($main_entity->isBasedOn->id))
					{
						$link_id = $main_entity->isBasedOn->id;
					}
					if (isset($main_entity->isBasedOn->name))
					{
						$link_name = $main_entity->isBasedOn->name;
					}
					
					// CSL-JSON
					if (isset($main_entity->isBasedOn->encoding))
					{
						$citation = encoding_to_citation($main_entity->isBasedOn->encoding);
					}
					
				}
				
				if ($link_id != '')
				{			
					$ns_id = id_to_key_value($link_id);
					
					echo '<div>';
					echo '<h3>Based on</h3>';
					echo '<a href="?id=' . $ns_id[1] . '&namespace=' . $ns_id[0] . '">';
					
					if ($citation != '')
					{
						echo $citation;
					}
					else
					{
						echo $link_name;
					}
					echo '</a>';
					echo '</div>';
				}
				else
				{
					echo $link_name;
				}
			}
			
			break;
			
		//--------------------------------------------------------------------------------
		case 'Taxon':
			if (isset($main_entity->taxonRank))
			{
				echo '<div>';
				echo '<strong>Rank:</strong> ' . htmlspecialchars($main_entity->taxonRank);
				echo '</div>';
			}
	
			// Display higher classification (same as for TaxonName)
			if (isset($main_entity->higherClassification))
			{
				display_classification_breadcrumbs($main_entity->higherClassification);
			}
	
			if (isset($main_entity->scientificName) && count($main_entity->scientificName) > 0)
			{
				echo '<h2>Scientific Names</h2>';
				echo '<ul>';
				foreach ($main_entity->scientificName as $scientificName)
				{
					echo '<li>';
	
					// Use id_to_key_value to extract namespace and id
					$ns_id = id_to_key_value($scientificName->id);
	
					if ($ns_id[0] && $ns_id[1])
					{
						echo '<a href="?namespace=' . $ns_id[0] . '&id=' . $ns_id[1] . '">';
					}
	
					echo htmlspecialchars($scientificName->name);
	
					if ($ns_id[0] && $ns_id[1])
					{
						echo '</a>';
					}
	
					// Display author if available
					if (isset($scientificName->author))
					{
						echo ' ' . htmlspecialchars($scientificName->author);
					}
	
					echo '</li>';
				}
				echo '</ul>';
			}		
			break;
			
		//--------------------------------------------------------------------------------
		case 'Book':
		case 'Chapter':
		case 'CreativeWork':
		case 'ScholarlyArticle':	
		
			// CSL-JSON
			if (isset($main_entity->encoding))
			{
				$citation = encoding_to_citation($main_entity->encoding);
				
				if ($citation != '')
				{
					echo '<div class="citation">';
					echo $citation;
					echo '</div>';
				}
									
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
					if (isset($main_entity->isPartOf->name))
					{
						$link_name = $main_entity->isPartOf->name;
					}
				}
				
				if ($link_id != '')
				{			
					$ns_id = id_to_key_value($link_id);
					
					echo '<div>';
					echo '<p>Is part of ';
					echo '<a href="?id=' . $ns_id[1] . '&namespace=' . $ns_id[0] . '">';
					echo $link_name;
					echo '</a>';
					echo '</p>';
					echo '</div>';
				}
				else
				{
					echo $link_name;
				}
			}	
			break;
		
		//--------------------------------------------------------------------------------
		default:
			break;
	}	

	echo '</div> <!-- headline -->';
	
	
	// PDF
	if (in_array($main_entity->type, ['Book','Chapter','CreativeWork','ScholarlyArticle']))		
	{
		echo '<div id="pdf" style="display:none;"></div>';
	}
	
	// Entity connections
	
	echo '<div class="relationships">';
	
	
	
	$n = count($doc);
	for ($i = 0; $i < $n; $i++)
	{
		if ($doc[$i]->type == 'DataFeed')
		{
			display_datafeed($doc[$i]);
		}
	}
	
	echo '</div> <!-- relationships -->';
	
	if (0)
	{
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
				echo '<div>';
				echo '<h2>Formatted Citation</h2>';
				echo '<div>';
				echo '<button onclick="show_citation(\'' . htmlspecialchars(addslashes($csl_json), ENT_QUOTES) . '\', \'apa\')">APA</button> ';
				echo '<button onclick="show_citation(\'' . htmlspecialchars(addslashes($csl_json), ENT_QUOTES) . '\', \'bibtex\')">BibTeX</button> ';
				echo '<button onclick="show_citation(\'' . htmlspecialchars(addslashes($csl_json), ENT_QUOTES) . '\', \'ris\')">RIS</button>';
				echo '</div>';
				echo '<div id="citation-output""></div>';
				echo '</div>';
			}
		}
	}

	if (0)
	{
		// debug display simplified data
		echo '<h2>Data</h2>';
		echo '<div style="font-family:monospace;white-space:pre-wrap;border:1px solid #CCC;padding:1em;">';
		echo json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		echo '</div>';
	}
	
	if (1)
	{
		if (isset($main_entity->encoding))
		{
			foreach ($main_entity->encoding as $encoding)
			{
				if ($encoding->encodingFormat == 'application/pdf')
				{
					if (preg_match('/^hash:\/\/sha1/', $encoding->contentUrl, ))
					{
						echo '<script>display_pdf("http://localhost/content-store-cloud-client/' . $encoding->contentUrl . '");</script>';
					}
					else
					{
						// echo '<script>display_pdf("' . $encoding->contentUrl . '");</script>';
					}
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
	require_once (dirname(__FILE__) . '/lists.css.inc.php');
	require_once (dirname(__FILE__) . '/taxonomy.css.inc.php');
	
echo '



#citation-output {
	display:none; 
	padding:1em; 
	background:#f5f5f5; 
	border:1px solid #ddd; 
	margin-top:1em; 
	white-space: pre-wrap;
	word-wrap: break-word;
	overflow-wrap: break-word;
}

/* citation output pre tags */
#citation-output pre {
	white-space: pre-wrap;
	word-wrap: break-word;
	overflow-wrap: break-word;
}



';

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
		<li><a href="?containers">Containers</a></li>
		<li><a href="https://github.com/rdmpage/bionames-two/issues" target="_new">Feedback</a></li>
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
// Search for names, cluster results by cluster_id
function display_search($q)
{
	global $config;

	$title = 'Search: ' . $q;

	$results = search($q);

	display_html_start($title);
	display_navbar($q);
	display_main_start();
	
	echo '<div class="headline">';

	echo '<h1>' . htmlspecialchars($results->name) . '</h1>';

	if (count($results->dataFeedElement) == 0)
	{
		echo '<p>No results found for "' . htmlspecialchars($q) . '"</p>';
	}
	else
	{
		// Cluster names by sameAs
		$clusters = array();
		$processed = array();

		// First pass: identify representative names (those without sameAs)
		foreach ($results->dataFeedElement as $dataFeedElement)
		{
			$item_id = '';
			if (preg_match('/:name:(\d+)$/', $dataFeedElement->item->id, $m))
			{
				$item_id = $m[1];
			}

			if (!isset($dataFeedElement->item->sameAs))
			{
				// This is a representative name
				$clusters[$dataFeedElement->item->id] = array(
					'representative' => $dataFeedElement,
					'duplicates' => array()
				);
				$processed[$dataFeedElement->item->id] = true;
			}
		}

		// Second pass: assign duplicates to their clusters
		foreach ($results->dataFeedElement as $dataFeedElement)
		{
			if (isset($dataFeedElement->item->sameAs))
			{
				$sameAs_id = is_array($dataFeedElement->item->sameAs)
					? $dataFeedElement->item->sameAs[0]
					: $dataFeedElement->item->sameAs;

				if (isset($clusters[$sameAs_id]))
				{
					$clusters[$sameAs_id]['duplicates'][] = $dataFeedElement;
					$processed[$dataFeedElement->item->id] = true;
				}
			}
		}

		// Third pass: handle any names not yet processed (edge cases)
		foreach ($results->dataFeedElement as $dataFeedElement)
		{
			if (!isset($processed[$dataFeedElement->item->id]))
			{
				$clusters[$dataFeedElement->item->id] = array(
					'representative' => $dataFeedElement,
					'duplicates' => array()
				);
			}
		}

		// Display clusters
		echo '<ul>';
		foreach ($clusters as $cluster_id => $cluster)
		{
			echo '<li>';

			// Display representative name
			$item_id = '';
			if (preg_match('/:name:(\d+)$/', $cluster['representative']->item->id, $m))
			{
				$item_id = $m[1];
			}

			if ($item_id)
			{
				echo '<a href="?namespace=names&id=' . $item_id . '">';
			}

			echo htmlspecialchars($cluster['representative']->item->name);

			if (isset($cluster['representative']->item->author))
			{
				echo ' ' . htmlspecialchars($cluster['representative']->item->author);
			}

			if ($item_id)
			{
				echo '</a>';
			}

			// Display duplicates if any
			if (count($cluster['duplicates']) > 0)
			{
				echo '<ul>';
				foreach ($cluster['duplicates'] as $duplicate)
				{
					echo '<li>';

					$dup_id = '';
					if (preg_match('/:name:(\d+)$/', $duplicate->item->id, $m))
					{
						$dup_id = $m[1];
					}

					if ($dup_id)
					{
						echo '<a href="?namespace=names&id=' . $dup_id . '">';
					}

					echo htmlspecialchars($duplicate->item->name);

					if (isset($duplicate->item->author))
					{
						echo ' ' . htmlspecialchars($duplicate->item->author);
					}

					if ($dup_id)
					{
						echo '</a>';
					}

					echo '</li>';
				}
				echo '</ul>';
			}

			echo '</li>';
		}
		echo '</ul>';
	}

	// Debug display
	if (0)
	{
		echo '<div style="font-family:monospace;white-space:pre-wrap;border:1px solid black;padding:1em;">';
		echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		echo '</div>';
	}
	
	echo '</div>';

	display_main_end();
	display_html_end();
}

//----------------------------------------------------------------------------------------
function display_container_list($letter = '')
{
	display_html_start($letter == '' ? 'Containers' : $letter);
	display_navbar('');
	display_main_start();
	
	$letters = ["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z"];

	echo '<ul style="list-style-type: none;display:block;overflow: auto;">';
	foreach ($letters as $one_letter)
	{
		echo '<li style="float: left;padding:0.3em"><a href="?containers&letter=' . $one_letter . '">' . $one_letter . '</a></li>';
	}
	echo '</ul>';

	if ($letter != '')
	{
		$doc = get_container_list($letter);

		echo '<div class="headline">';
		echo '<h1>' . $letter . '</h1>';
		echo '</div>';

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
	}

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
	display_main_start();

	if ($error_msg != '')
	{
		echo '<div><strong>Error!</strong> ' . $error_msg . '</div>';
	}
	else
	{
		// Get database statistics
		$stats = get_database_stats();
		
		echo '<div class="headline">';

		echo '<h1>Welcome to BioNames</h1>';

		echo '<h2>Database Statistics</h2>';
		echo '<dl>';
		
		echo '<dt>Total names</dt>';
		echo '<dd>' . number_format($stats->total_names) . '</dd>';

		echo '<dt>Distinct name clusters</dt>';
		echo '<dd>' . number_format($stats->total_clusters) . '</dd>';

		echo '<dt>Names with publications</dt>';
		echo '<dd>' . number_format($stats->names_with_publications) . '</dd>';

		echo '<dt>Names with DOIs</dt>';
		echo '<dd>' . number_format($stats->names_with_dois) . '</dd>';

		echo '<dt>Names with free PDFs</dt>';
		echo '<dd>' . number_format($stats->names_with_content_sha1) . '</dd>';
		
		echo '</dl>';
		
		echo '</div>';
	}

	display_main_end();
	display_html_end();
}


//----------------------------------------------------------------------------------------
function display_path($path)
{
	$title = classification_label($path);

	$doc = get_taxon($title);
	
	$main_entity = $doc[0];
	
	display_html_start($title);
	display_navbar('');
	display_main_start();
		
	echo '<div class="headline">';
	echo '<h1>' . htmlspecialchars($main_entity->name) . '</h1>';
	
	display_classification_breadcrumbs($main_entity->higherClassification);	
	
	echo '</div>';
	
	echo '<div id="treemap"></div>';	
	
	echo '<script>
	drawTreemap("' . $path . '");	
	</script>';
	
	echo '<div class="relationships">';
	$n = count($doc);
	for ($i = 0; $i < $n; $i++)
	{
		if ($doc[$i]->type == 'DataFeed')
		{
			display_datafeed($doc[$i]);
		}
	}	
	echo '</div> <!-- relationships -->';
	
	
	if (0)
	{
		// debug display simplified data
		echo '<h2>Data</h2>';
		echo '<div style="font-family:monospace;white-space:pre-wrap;border:1px solid #CCC;padding:1em;">';
		echo json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		echo '</div>';
	}
	

	display_main_end();
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
		$letter = '';
		if (isset($_GET['letter']))
		{
			$letter = $_GET['letter'];
		}

		display_container_list($letter);
		exit(0);
	}
	
	// Taxonomy browser
	if (isset($_GET['path']))
	{
		$path = $_GET['path'];
		display_path($path);
		exit(0);
	}

}

main();

?>