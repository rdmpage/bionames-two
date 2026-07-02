<?php

require_once (dirname(__FILE__) . '/config.inc.php');


//----------------------------------------------------------------------------------------
// retrieve data from database
//
// Pass $params (an array) to run the query as a prepared statement with bound
// parameters. Use this whenever the query contains user-supplied values, e.g.:
//     db_get('SELECT * FROM names WHERE id = ?', [$id]);
// $sql without $params is still run verbatim for static, trusted queries.
function db_get($sql, $params = null)
{
	global $config;

	$pdo = $config['pdo'];

	if ($params === null)
	{
		$stmt = $pdo->query($sql);
	}
	else
	{
		$stmt = $pdo->prepare($sql);
		$stmt->execute($params);
	}

	$data = array();

	while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

		$item = new stdclass;
		
		$keys = array_keys($row);
	
		foreach ($keys as $k)
		{
			if ($row[$k] != '')
			{
				$item->{$k} = $row[$k];
			}
		}
	
		$data[] = $item;
	}	
	return $data;	
}

//----------------------------------------------------------------------------------------
function db_put($sql)
{
	global $config;
	
	$pdo = $config['pdo'];
	
	$stmt = $pdo->prepare($sql);
	
	if (!$stmt)
	{
		echo "\nPDO::errorInfo():\n";
		print_r($pdo->errorInfo());
	}	
	
	$stmt->execute();
	
	if (!$stmt)
	{
		echo "\nPDO::errorInfo():\n";
		print_r($pdo->errorInfo());
	}	
	
}

//----------------------------------------------------------------------------------------
function obj_to_sql($obj, $table_name = 'table')
{
	// to $sql
	$keys = array();
	$values = array();
	
	foreach ($obj as $k => $v)
	{
		$keys[] = '"' . $k . '"'; // must be double quotes
	
		if (is_array($v))
		{
			$values[] = "'" . str_replace("'", "''", json_encode(array_values($v))) . "'";
		}
		elseif(is_object($v))
		{
			$values[] = "'" . str_replace("'", "''", json_encode($v)) . "'";
		}
		elseif (preg_match('/^POINT/', $v))
		{
			$values[] = "ST_GeomFromText('" . $v . "', 4326)";
		}
		else
		{				
			$values[] = "'" . str_replace("'", "''", $v) . "'";
		}					
	}
	
	//$sql = 'INSERT OR IGNORE INTO `' . $table_name . '` (' . join(",", $keys) . ') VALUES (' . join(",", $values) . ') ON CONFLICT DO NOTHING;';					
	$sql = 'REPLACE INTO `' . $table_name . '` (' . join(",", $keys) . ') VALUES (' . join(",", $values) . ');';					

	return $sql;
}

?>
