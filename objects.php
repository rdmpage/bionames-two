<?php

// Output things from BioNames as JSON-LD, but in a way that is easy for client to
// process. For example, we alias "@id" to "id"

require_once (dirname(__FILE__) . '/sqlite.php');

//----------------------------------------------------------------------------------------
function get_reference_type($row)
{
	$type = 'article'; // default
	
	if (isset($row->isbn) || isset($row->publisher))
	{
		$type = 'book';
	}

	if (isset($row->isPartOf))
	{
		if ($row->isPartOf == 'Y')
		{
			$type = 'chapter';
		}
	}
	
	return $type;
}

//----------------------------------------------------------------------------------------
function create_context()
{
	$context = new stdclass;
	$context->{'@vocab'} = 'http://schema.org/';
	$context->xsd = 'http://www.w3.org/2001/XMLSchema#';
	
	// flatten id and type using aliases
	$context->id = '@id';
	$context->type = '@type';
	
	// sameAs
	$sameAs = new stdclass;
	$sameAs->{'@id'} = 'sameAs';
	$sameAs->{'@type'} = '@id';
	$context->sameAs = $sameAs;	
	
	// url
	$url = new stdclass;
	$url->{'@id'} = 'url';
	$url->{'@type'} = '@id';
	$context->url = $url;		
	
	return $context;
}

//----------------------------------------------------------------------------------------
function add_work_context($context)
{
	// date
	$datePublished = new stdclass;
	$datePublished->{'@id'} = 'datePublished';
	$datePublished->{'@type'} = 'xsd:gYear';
	$context->datePublished = $datePublished;	
	
	// ISSN
	$context->issn = 'http://issn.org/resource/ISSN/';

	// PRISM
	$context->prism = 'http://prismstandard.org/namespaces/basic/2.0/';
	$context->doi = 'prism:doi';			

	return $context;
}

//----------------------------------------------------------------------------------------
function add_container_context($context)
{	
	// ISSN
	$context->issn = 'http://issn.org/resource/ISSN/';
	
	// OCLC
	$context->oclc = 'https://worldcat.org/oclc/';

	return $context;
}

//----------------------------------------------------------------------------------------
function add_taxon_context($context)
{
	// isBasedOn 
	$isBasedOn = new stdclass;
	$isBasedOn->{'@id'} = 'isBasedOn';
	$isBasedOn->{'@type'} = '@id';
	$context->isBasedOn = $isBasedOn;		

	// DarwinCore
	$context->dwc = 'http://rs.tdwg.org/dwc/terms/';
	
	$context->genericName          = 'dwc:genusPart';
	$context->infragenericEpithet  = 'dwc:infragenericEpithet';
	$context->specificEpithet      = 'dwc:specificEpithet';
	$context->infraspecificEpithet = 'dwc:infraspecificEpithet';
	$context->scientificNameID     = 'dwc:scientificNameID';
	$context->higherClassification = 'dwc:higherClassification';

	return $context;
}

//----------------------------------------------------------------------------------------
// convert a row in the database to a schema.org representation of a work,
// can limit the set of key,vaklue pairs to be included.
// If embedded flag is true then this record is part 
// of a list or other object and we won't include the JSON-LD context
function db_row_to_reference(
	$row, 
	$embedded = false,
	$keys = ['sici', 'title', 'journal', 'volume', 'issue', 'spage', 'epage', 'year','issn', 'isbn', 'oclc', 'publisher', 'doi', 'url']
	)
{
	$obj = new stdclass;
	
	if (!$embedded)
	{		
		$context = create_context();			
		$context = add_work_context($context);										
		$obj->{'@context'} = $context;	
	}	

	switch(get_reference_type($row))
	{
		case 'article':
			$obj->{'type'} = 'ScholarlyArticle';
			break;

		case 'book':
			$obj->{'type'} = 'Book';
			break;

		case 'chapter':
			$obj->{'type'} = 'Chapter';
			break;

		default:
			$obj->{'type'} = 'CreativeWork';
			break;				
	}
	
	foreach ($keys as $k)
	{
		if (isset($row->{$k}))
		{
			switch ($k)
			{
				case 'doi':
					$obj->doi = strtolower($row->{$k});
					$obj->sameAs = 'https://doi.org/' . strtolower($row->{$k});
					break;	
					
				case 'epage':
					$obj->pageEnd = $row->{$k};
					break;	
					
				case 'issn':
					break;		
					
				case 'isbn':
					switch ($obj->type)
					{
						case 'Chapter':
							$obj->isPartOf = new stdclass;
							$obj->isPartOf->id = 'isbn:' . $row->isbn;
							$obj->isPartOf->type = 'Book';
							$obj->isPartOf->isbn = $row->isbn;
							
							if (isset($row->journal))
							{
								$obj->isPartOf->name = $row->journal;
							}
							break;
							
						case 'Book':
						default:
							$obj->isbn = $row->isbn;
							break;
					}						
					break;																	

				case 'issue':
					$obj->issueNumber = $row->{$k};
					break;	
					
				case 'journal':
					$obj->isPartOf = new stdclass;	
					$obj->isPartOf->name = $row->{$k};
					
					if (isset($row->issn))
					{
						$obj->isPartOf->id = 'issn:' . $row->issn;
						$obj->isPartOf->type = 'Periodical';
						$obj->isPartOf->issn = $row->issn;
					}
					elseif (isset($row->oclc))
					{
						$obj->isPartOf->id = 'https://worldcat.org/oclc/' . $row->oclc;
						$obj->isPartOf->type = 'Periodical';
					}						
					elseif (isset($row->isbn))
					{
						$obj->isPartOf->id = 'isbn:' . $row->isbn;
						$obj->isPartOf->type = 'Book';
						$obj->isPartOf->isbn = $row->isbn;
					}						
					break;									
								
				case 'sici':
					$obj->id = 'https://bionames.org/references/' . $row->{$k};
					break;	

				case 'spage':
					$obj->pageStart = $row->{$k};
					break;				
					
				case 'title':
					$obj->name = $row->{$k};
					break;	
					
				case 'url':
					$obj->url = $row->{$k};
					break;	
																	
				case 'volume':
					$obj->volumeNumber = $row->{$k};
					break;				
			
				case 'year':
					$obj->datePublished = $row->{$k};
					break;								
			
				default:
					$obj->{$k} = $row->{$k};
					break;
			}
		}
	}
	
	if (!$embedded)
	{
		$encoding = new stdclass;
		$encoding->encodingFormat = 'application/vnd.citationstyles.csl+json';
		$encoding->text = json_encode(get_reference_csl($row->sici));
		
		$obj->encoding = [];
		$obj->encoding[] = $encoding;
		
		if (isset($row->pdf))
		{
			$encoding = new stdclass;
			$encoding->encodingFormat = 'application/pdf';
			$encoding->contentUrl = $row->pdf;
			
			$obj->encoding[] = $encoding;
		}
	}	

	return $obj;
}

//----------------------------------------------------------------------------------------
// get schema.org style reference from its id
function get_reference($id, $embedded = false)
{
	$obj = null;
	
	$sql = "SELECT * FROM names WHERE sici='$id' LIMIT 1";
	
	$data = db_get($sql);
	
	if ($data && isset($data[0]))
	{
		$obj = db_row_to_reference($data[0], $embedded);
	}

	return $obj;
}

//----------------------------------------------------------------------------------------
// Get reference in CSL JSON
function get_reference_csl($id)
{
	$obj = null;
	
	$sql = "SELECT * FROM names WHERE sici='$id' LIMIT 1";
	
	$data = db_get($sql);
	
	// keys relevant to CSL
	$keys = ['sici', 'title', 'journal', 'volume', 'issue', 'spage', 'epage', 'year', 'issn', 'isbn', 'oclc', 'doi', 'url', 'publisher'];
	
	foreach ($data as $row)
	{
		$obj = new stdclass;
		switch(get_reference_type($row))
		{
			case 'article':
				$obj->type = 'article-journal';
				break;

			case 'book':
				$obj->type = 'book';
				break;

			case 'chapter':
				$obj->type = 'chapter';
				break;

			default:
				$obj->type = 'article-journal';
				break;				
		}
	
		foreach ($keys as $k)
		{
			if (isset($row->{$k}))
			{
				switch ($k)
				{
					case 'doi':
						$obj->DOI= strtolower($row->{$k});
						break;
				
					case 'isbn':
						$obj->ISBN[] = $row->{$k};
						break;

					case 'issn':
						$obj->ISSN[] = $row->{$k};
						break;

					case 'journal':
						$obj->{'container-title'} = $row->{$k};
						break;
						
					case 'sici':
						$obj->id = $row->{$k};
						break;
						
					case 'spage':
						$obj->page = $row->{$k};
						break;

					case 'epage':
						$obj->page .= '-' . $row->{$k};
						break;	
						
					case 'url':
						$obj->URL= $row->{$k};
						break;											
						
					case 'year':
						$obj->issued = new stdclass;
						$obj->issued->{'date-parts'} = array();
						$obj->issued->{'date-parts'}[0][] = (Integer)$row->{$k};
						break;
				
					default:
						$obj->{$k} = $row->{$k};
						break;
				}
			}
		}
	}

	return $obj;
}

//----------------------------------------------------------------------------------------
// Get names published in a reference
function get_names_in_reference($sici)
{
	$feed = new stdclass;
	$feed->{'@context'} = create_context();
	$feed->{'@context'} = add_taxon_context($feed->{'@context'});
	$feed->type = 'DataFeed';
	$feed->name = 'Names published in this reference';

	$feed->dataFeedElement = [];

	$escaped_sici = str_replace("'", "''", $sici);
	$sql = "SELECT id, nameComplete, taxonAuthor, rank FROM names WHERE sici='$escaped_sici' ORDER BY nameComplete";

	$data = db_get($sql);

	foreach ($data as $row)
	{
		$item = new stdclass;
		$item->type = 'DataFeedItem';

		$name = new stdclass;
		$name->type = 'TaxonName';
		$name->id = 'urn:lsid:organismnames.com:name:' . $row->id;
		$name->name = $row->nameComplete;

		if (isset($row->taxonAuthor))
		{
			$name->author = $row->taxonAuthor;
		}

		if (isset($row->rank))
		{
			$name->taxonRank = $row->rank;
		}

		$item->item = $name;
		$feed->dataFeedElement[] = $item;
	}

	return $feed;
}

//----------------------------------------------------------------------------------------
// Get container, use most common name as the name, and store the others as alternateNames
//
function get_container($namespace, $id)
{
	$obj = null;
	
	$sql = 'SELECT COUNT(id) AS c, journal FROM names WHERE journal IS NOT NULL AND ';
	
	switch ($namespace)
	{
		case 'oclc':
			$sql .= ' oclc="' . $id . '"';
			break;
			
		case 'issn':
		default:
			$sql .= ' issn="' . $id . '"';
			break;
	}
	$sql .= ' GROUP BY journal';
	
	$data = db_get($sql);
	
	if (count($data) > 0)
	{
		$obj = new stdclass;
		
		$obj->{'@context'} = create_context();
		$obj->{'@context'} = add_container_context($obj->{'@context'});
				
		switch ($namespace)
		{
			case 'oclc':
				$obj->oclc = $id;
				$obj->id = 'https://worldcat.org/oclc/' . $id;	
				$obj->type = 'CreativeWork';
				break;
				
			case 'isbn':
				$obj->isbn = $id;
				$obj->id = 'https://worldcat.org/isbn/' . $id;	
				$obj->type = 'Book';
				break;
				
			case 'issn':
			default:
				$obj->issn = $id;
				$obj->id = 'http://issn.org/resource/ISSN/' . $id;	
				$obj->type = 'Periodical';			
				break;
		}
		
		// Run through the names, use most common as name
		$max_count = 0;
		$obj->name = "";		
		$obj->alternateName = array();
		foreach ($data as $row)
		{
			if ($row->c > $max_count)
			{
				$obj->name = $row->journal;
				$max_count = $row->c;
			}
		
			$obj->alternateName[] = $row->journal;
		}
		
		// remove chosen name from alternateName
		$key = array_search($obj->name, $obj->alternateName);
		{
			unset($obj->alternateName[$key]);
		}	
		
		$obj->alternateName = array_values($obj->alternateName);
		
		if (count($obj->alternateName) == 0)
		{
			unset($obj->alternateName);
		}
				
	}	
	
	return $obj;
}

//----------------------------------------------------------------------------------------
// List of works in container (e.g., articles in a journal)
function get_container_works_list($namespace, $id)
{
	$feed = new stdclass;
	$feed->{@context} = create_context();
	$feed->{@context} = add_container_context($feed->{@context});
	$feed->type = 'DataFeed';
	$feed->name = 'Works in container';

	$feed->dataFeedElement = [];
	

	$sql = 'SELECT DISTINCT sici, title, journal, volume, issue, spage, epage, year, doi  FROM names';
	
	switch ($namespace)
	{
		case 'oclc':
			$sql .= ' WHERE oclc="' . $id . '"';
			break;
	
		case 'issn':
		default:
			$sql .= ' WHERE issn="' . $id . '"';
			break;
	}
	
	$sql .= ' ORDER BY year';
	
	$data = db_get($sql);
	
	// keys relevant to a simple list of references
	$keys = ['sici', 'title', 'journal', 'year', 'doi'];
	
	foreach ($data as $row)
	{
		$item = new stdclass;
		$item->type = 'DataFeedItem';
		
		$item->item = db_row_to_reference($row, true, $keys);		
		$feed->dataFeedElement[] = $item;	
	}
	
	return $feed;
}

//----------------------------------------------------------------------------------------
function get_related_names($id)
{
	$related = array();
	
	$sql = "SELECT * FROM variants INNER JOIN names ON target_id = id WHERE source_id=$id";
	
	$data = db_get($sql);	
	foreach ($data as $row)
	{
		$name = $row->nameComplete;
		if (isset($row->taxonAuthor))
		{
			$name .= ' ' . $row->taxonAuthor;
		}
		$related[$row->target_id] = $name;
	}
	
	$sql = "SELECT * FROM variants INNER JOIN names ON source_id = id WHERE target_id=$id";

	$data = db_get($sql);	
	foreach ($data as $row)
	{
		$name = $row->nameComplete;
		if (isset($row->taxonAuthor))
		{
			$name .= ' ' . $row->taxonAuthor;
		}
		$related[$row->source_id] = $name;
	}
	
	return $related;

}


//----------------------------------------------------------------------------------------
function get_container_list($letter = 'A')
{
	$feed = new stdclass;
	$feed->{@context} = create_context();
	$feed->type = 'DataFeed';
	$feed->name = 'Containers ' . $letter;

	$feed->dataFeedElement = [];

	$sql = "SELECT DISTINCT journal, issn, oclc, isbn FROM names WHERE journal LIKE '$letter%' ORDER BY journal";
	
	$results = array();
	
	$data = db_get($sql);	
	foreach ($data as $row)
	{
		$key = $row->journal;
		
		if (isset($row->issn))
		{
			$key = $row->issn;
		}
		elseif (isset($row->oclc))
		{
			$key = $row->oclc;
		}
		elseif (isset($row->isbn))
		{
			$key = $row->isbn;
		}
		
		if (!isset($results[$key]))
		{
			$results[$key] = new stdclass;
			$results[$key]->name = [];
			
			if (isset($row->issn))
			{
				$results[$key]->issn = $row->issn;
			}
			if (isset($row->oclc))
			{
				$results[$key]->oclc = $row->oclc;
			}
			if (isset($row->isbn))
			{
				$results[$key]->isbn = $row->isbn;
			}			
		}
		$results[$key]->name[] = $row->journal;
	}

	foreach ($results as $r)
	{	
		$item = new stdclass;
		$item->type = 'DataFeedItem';
		
		$container = new stdclass;
		$container->type = 'CreativeWork';
		$container->name = $r->name[0];
		
		if (isset($r->issn))
		{
			$container->id = 'http://issn.org/resource/ISSN/' . $r->issn;	
			$container->type = 'Periodical';
			$container->issn = $r->issn;			
		}
		elseif (isset($r->oclc))
		{
			$container->id = 'https://worldcat.org/oclc/' . $r->oclc;	
			$container->type = 'CreativeWork';	
			$container->oclc = $r->oclc;	
		}
		elseif (isset($r->isbn))
		{
			$container->id = 'https://worldcat.org/isbn/' . $r->isbn;
			$container->type = 'Book';	
			$container->isbn = $r->isbn;	
		}
		
		$item->item = $container;		
		$feed->dataFeedElement[] = $item;	
	}
	
	return $feed;
}

//----------------------------------------------------------------------------------------
function get_name($id, $expand = true)
{
	$obj = null;

	$sql = "SELECT * FROM names WHERE id=$id LIMIT 1";

	$data = db_get($sql);

	$keys = ['id', 'nameComplete', 'taxonAuthor', 'rank', 'group',
		'uninomial', 'genusPart', 'infragenericEpithet', 'specificEpithet', 'specificStem', 'infraspecificEpithet', 'infraspecificStem', 'publication', 'sici'];

	foreach ($data as $row)
	{
		$obj = new stdclass;

		$context = create_context();
		$context = add_work_context($context);
		$context = add_taxon_context($context);
		$obj->{'@context'} = $context;

		$obj->type = 'TaxonName';

		foreach ($keys as $k)
		{
			if (isset($row->{$k}))
			{
				switch ($k)
				{
					case 'id':
						$obj->id = 'urn:lsid:organismnames.com:name:' . $row->{$k};

						// $obj->scientificNameID = $obj->{'@id'};

						$obj->sameAs = 'https://lsid.io/' .  $obj->id;
						break;

					case 'nameComplete':
						$obj->name = $row->{$k};
						break;

					case 'taxonAuthor':
						$obj->author = $row->{$k};
						break;

					case 'rank':
						$obj->taxonRank = $row->{$k};
						break;

					// parts of a name
					case 'genusPart':
						$obj->genericName = $row->{$k};
						break;

					case 'infragenericEpithet':
					case 'specificEpithet':
					case 'infraspecificEpithet':
						$obj->{$k} = $row->{$k};
						break;

					case 'sici':
						if ($expand)
						{
							$obj->isBasedOn = get_reference($row->{$k}, true);
						}
						else
						{
							$obj->isBasedOn = 'https://bionames.org/references/' . $row->{$k};
						}
						break;

					case 'group':
						$obj->higherClassification = $row->{$k};
						break;
						
					default:
						break;
				}
			}
		}
	}

	if ($obj)
	{
		//$obj->related = get_related_names($id);
	}

	return $obj;

}

//----------------------------------------------------------------------------------------
// Search for taxonomic names by exact match on nameComplete field
function search_names($query)
{
	// Trim leading and trailing whitespace
	$query = trim($query);

	$feed = new stdclass;
	$feed->{'@context'} = create_context();
	$feed->{'@context'} = add_taxon_context($feed->{'@context'});
	$feed->type = 'DataFeed';
	$feed->name = 'Search results for "' . $query . '"';

	$feed->dataFeedElement = [];

	// Escape query for SQL
	$escaped_query = str_replace("'", "''", $query);

	$sql = "SELECT id, nameComplete, taxonAuthor FROM names WHERE nameComplete = '$escaped_query'";

	$data = db_get($sql);

	foreach ($data as $row)
	{
		$item = new stdclass;
		$item->type = 'DataFeedItem';

		$name = new stdclass;
		$name->type = 'TaxonName';
		$name->id = 'urn:lsid:organismnames.com:name:' . $row->id;
		$name->name = $row->nameComplete;

		if (isset($row->taxonAuthor))
		{
			$name->author = $row->taxonAuthor;
		}

		$item->item = $name;
		$feed->dataFeedElement[] = $item;
	}

	return $feed;
}

//----------------------------------------------------------------------------------------
// Get taxonomic hierarchy for treemap visualization
// Returns hierarchical structure with counts for each taxon
function get_taxonomy_tree($parent_path = '')
{
	$sql = "SELECT DISTINCT `group` FROM names WHERE `group` IS NOT NULL";

	if ($parent_path != '')
	{
		$escaped_path = str_replace("'", "''", $parent_path);
		$sql .= " AND `group` LIKE '$escaped_path%'";
	}

	$data = db_get($sql);

	// Build hierarchy
	$tree = new stdclass;
	$tree->name = $parent_path == '' ? 'Life' : $parent_path;
	$tree->children = [];

	$groups = [];

	foreach ($data as $row)
	{
		if (isset($row->group))
		{
			$lineage = explode(';', $row->group);

			// Find the next level down from parent
			$depth = $parent_path == '' ? 0 : count(explode(';', $parent_path));

			if (isset($lineage[$depth]))
			{
				$taxon = $lineage[$depth];

				if (!isset($groups[$taxon]))
				{
					$groups[$taxon] = 0;
				}
				$groups[$taxon]++;
			}
		}
	}

	// Convert to tree structure
	foreach ($groups as $taxon => $count)
	{
		$child = new stdclass;
		$child->name = $taxon;
		$child->value = $count;

		// Build full path for this taxon
		if ($parent_path == '')
		{
			$child->path = $taxon;
		}
		else
		{
			$child->path = $parent_path . ';' . $taxon;
		}

		$tree->children[] = $child;
	}

	return $tree;
}

//----------------------------------------------------------------------------------------
// Get names within a specific taxonomic group
function get_names_in_group($group_path)
{
	$feed = new stdclass;
	$feed->{'@context'} = create_context();
	$feed->{'@context'} = add_taxon_context($feed->{'@context'});
	$feed->type = 'DataFeed';
	$feed->name = 'Names in ' . $group_path;

	$feed->dataFeedElement = [];

	$escaped_path = str_replace("'", "''", $group_path);
	$sql = "SELECT id, nameComplete, taxonAuthor, rank FROM names WHERE `group` LIKE '$escaped_path%' LIMIT 100";

	$data = db_get($sql);

	foreach ($data as $row)
	{
		$item = new stdclass;
		$item->type = 'DataFeedItem';

		$name = new stdclass;
		$name->type = 'TaxonName';
		$name->id = 'urn:lsid:organismnames.com:name:' . $row->id;
		$name->name = $row->nameComplete;

		if (isset($row->taxonAuthor))
		{
			$name->author = $row->taxonAuthor;
		}

		if (isset($row->rank))
		{
			$name->taxonRank = $row->rank;
		}

		$item->item = $name;
		$feed->dataFeedElement[] = $item;
	}

	return $feed;
}

if (0)
{
	$feed = get_container_list('E');

	print_r($feed);
}

if (0)
{

	$id = 'b174d24aeb9fa81d4f34b5b181b52d92';
	$obj = get_reference($id);
	print_r($obj);

	echo json_encode($obj) . "\n";
}

if (0)
{
	$obj = get_container('issn', '0260-1230');
	print_r($obj);
	echo json_encode($obj) . "\n";
}

if (0)
{
	$obj = get_reference_csl($id);
	print_r($obj);
}


if (0)
{
	$id = 4358279;
	$obj = get_name($id);

	print_r($obj);
	echo json_encode($obj) . "\n";

}

?>
