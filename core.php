<?php

require_once (dirname(__FILE__) . '/objects.php');

//----------------------------------------------------------------------------------------
// From easyrdf/lib/parser/ntriples
function unescapeString($str)
    {
        if (strpos($str, '\\') === false) {
            return $str;
        }

        $mappings = array(
            't' => chr(0x09),
            'b' => chr(0x08),
            'n' => chr(0x0A),
            'r' => chr(0x0D),
            'f' => chr(0x0C),
            '\"' => chr(0x22),
            '\'' => chr(0x27)
        );
        foreach ($mappings as $in => $out) {
            $str = preg_replace('/\x5c([' . $in . '])/', $out, $str);
        }
        
        while (preg_match('/\\\(U)([0-9A-F]{8})/', $str, $matches) ||
               preg_match('/\\\(u)([0-9A-F]{4})/', $str, $matches)) {
            $no = hexdec($matches[2]);
            if ($no < 128) {                // 0x80
                $char = chr($no);
            } elseif ($no < 2048) {         // 0x800
                $char = chr(($no >> 6) + 192) .
                        chr(($no & 63) + 128);
            } elseif ($no < 65536) {        // 0x10000
                $char = chr(($no >> 12) + 224) .
                        chr((($no >> 6) & 63) + 128) .
                        chr(($no & 63) + 128);
            } elseif ($no < 2097152) {      // 0x200000
                $char = chr(($no >> 18) + 240) .
                        chr((($no >> 12) & 63) + 128) .
                        chr((($no >> 6) & 63) + 128) .
                        chr(($no & 63) + 128);
            } else {
                # FIXME: throw an exception instead?
                $char = '';
            }
            $str = str_replace('\\' . $matches[1] . $matches[2], $char, $str);
        }
         
        return $str;
    }

//----------------------------------------------------------------------------------------
function clean_literal($string)
{
	$string = unescapeString($string);

	return $string;
}

//----------------------------------------------------------------------------------------
function get_one_literal($literal, $preferred_language = "en")
{
	$value = '';
	
	if (is_object($literal))
	{
		$value = clean_literal($literal->{"@value"});
	}
	else
	{						
		if (is_array($literal))
		{
			$index = 0;
			
			// if we have strings in multiple languages, we might want to chose one
			// we prefer (e.g., if displaying website in Chinese we might want Chinese
			// values if we have them)
			$n = count($literal);
			if ($n > 1)
			{
				
				for ($i = 0; $i < $n; $i++)
				{
					if (is_object($literal[$i]))
					{
						if (isset($literal[$i]->{'@language'}) && ($literal[$i]->{'@language'} == $preferred_language))
						{
							$index = $i;
						}
					}
				}
			}		
			$value = get_one_literal($literal[$index], $preferred_language);
		}
		else
		{
			$value = clean_literal($literal);
		}
	}	
	return $value;
}


//----------------------------------------------------------------------------------------
// entity is JSON-LD compatible, and in an array as we may have multiple graphs,
// such as an entity and things connected to that entity
function get_entity($namespace, $id)
{	
	$entity = [];
	
	switch ($namespace)
	{
		case 'doi':
			$sici = get_reference_id_from_doi($id);
			$entity = get_entity('references', $sici);
			break;
	
		case 'issn':
		case 'oclc':
			$doc = get_container($namespace, $id);
			$entity[] = $doc;
			
			$doc = get_container_works_list($namespace, $id);
			$entity[] = $doc;
			break;
			
		case 'names':			
			$doc = get_name($id);
			$entity[] = $doc;
			break;	
			
		case 'references':
			$doc = get_reference($id);
			$entity[] = $doc;

			$doc = get_names_in_reference($id);
			$entity[] = $doc;
			break;			

		default:
			$entity[] = null;
			break;
	
	}
	
	return $entity;
}



//----------------------------------------------------------------------------------------
function search($text)
{
	$feed = search_names($text);

	return $feed;
}


//----------------------------------------------------------------------------------------
function entity_name($entity)
{
	$name = 'Unknown';
	
	if (isset($entity->name))
	{
		$name = $entity->name;
	}	
	
	return $name;
}

//----------------------------------------------------------------------------------------
function entity_alternate_names($entity)
{
	$alternate_names = [];
	
	if (isset($entity->alternateName))
	{
		foreach ($entity->alternateName as $alternateName)
		{
			$alternate_names[] = $alternateName;
		}
	}
	
	return $alternate_names;
}


if (0)
{
	$entity = get_entity(1);
	print_r($entity);
}



?>
