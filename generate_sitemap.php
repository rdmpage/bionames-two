<?php
/**
 * @file generate_sitemap.php
 *
 * Generates a sitemap index (sitemap.xml) plus gzipped child sitemaps for
 * BioNames. Run from the CLI:
 *
 *     php generate_sitemap.php               # full run (~5M URLs)
 *     php generate_sitemap.php --limit=1000  # smoke test: 1000 URLs per type
 *
 * The site has far more URLs than the sitemap protocol's 50,000-per-file /
 * 50 MB limit, so output is split into gzipped child sitemaps under sitemaps/
 * and tied together by a sitemap index at the web root. robots.txt already
 * declares:  Sitemap: https://bionames.org/sitemap.xml
 *
 * Regenerate periodically (e.g. weekly via cron) as the database grows:
 *     0 3 * * 0  cd /path/to/site && php generate_sitemap.php
 */

require_once __DIR__ . '/config.inc.php';   // provides $config['pdo']

// --- Configuration ----------------------------------------------------------

// Production base URL. Sitemap URLs must be absolute and point at the live
// site regardless of where the script is run, so this is independent of the
// local/remote $config web_root.
define('BASE_URL', 'https://bionames.org/');

define('OUTPUT_DIR',   __DIR__ . '/sitemaps');
define('INDEX_FILE',   __DIR__ . '/sitemap.xml');
define('MAX_PER_FILE', 50000);   // sitemap protocol hard limit

// Which entity types to include.
$SOURCES = [
	'clusters' => [
		'enabled' => true,
		'sql'     => "SELECT id FROM names WHERE id = cluster_id",
		'path'    => 'cluster/',           // /cluster/{id}
	],
	'names' => [
		'enabled' => true,
		// One page per name. Canonical form is
		// /names/urn:lsid:organismnames.com:name:{names.id} (see
		// entity_canonical_url()). To skip names that are already a cluster
		// representative (id = cluster_id, surfaced via /cluster/), add:
		//   WHERE cluster_id IS NULL OR id != cluster_id
		'sql'     => "SELECT id FROM names",
		'path'    => 'names/urn:lsid:organismnames.com:name:',
	],
	'references' => [
		'enabled' => true,
		'sql'     => "SELECT DISTINCT sici FROM names WHERE sici IS NOT NULL AND sici != ''",
		'path'    => 'references/',         // /references/{sici}
	],
	'journals' => [
		'enabled' => true,
		'sql'     => "SELECT issn FROM issn_journal WHERE issn IS NOT NULL AND issn != ''",
		'path'    => 'issn/',              // /issn/{issn}
	],
];

// --- CLI args ---------------------------------------------------------------

$limit = 0;
foreach ($argv as $arg)
{
	if (preg_match('/^--limit=(\d+)$/', $arg, $m))
	{
		$limit = (int)$m[1];
	}
}

// --- Helpers ----------------------------------------------------------------

// Open a new gzipped child sitemap and write the opening boilerplate.
function open_child($path)
{
	$gz = gzopen($path, 'wb9');
	gzwrite($gz, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
	gzwrite($gz, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n");
	return $gz;
}

function close_child($gz)
{
	gzwrite($gz, '</urlset>' . "\n");
	gzclose($gz);
}

// --- Main -------------------------------------------------------------------

$pdo = $config['pdo'];

if (!is_dir(OUTPUT_DIR))
{
	mkdir(OUTPUT_DIR, 0755, true);
}

// Remove stale child sitemaps from a previous run so the index never points
// at files that no longer reflect the data.
foreach (glob(OUTPUT_DIR . '/sitemap-*.xml.gz') as $old)
{
	unlink($old);
}

$child_files = [];   // relative paths for the index

foreach ($SOURCES as $name => $source)
{
	if (empty($source['enabled']))
	{
		continue;
	}

	$sql = $source['sql'];
	if ($limit > 0)
	{
		$sql .= ' LIMIT ' . $limit;
	}

	$stmt = $pdo->query($sql);

	$file_index = 0;
	$in_file    = 0;
	$total      = 0;
	$gz         = null;

	while (($id = $stmt->fetchColumn(0)) !== false)
	{
		// Start a new child file when needed.
		if ($gz === null || $in_file >= MAX_PER_FILE)
		{
			if ($gz !== null)
			{
				close_child($gz);
			}
			$file_index++;
			$in_file  = 0;
			$rel      = 'sitemaps/sitemap-' . $name . '-' . $file_index . '.xml.gz';
			$child_files[] = $rel;
			$gz = open_child(__DIR__ . '/' . $rel);
		}

		$loc = BASE_URL . $source['path'] . rawurlencode((string)$id);
		gzwrite($gz, '<url><loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc></url>' . "\n");

		$in_file++;
		$total++;
	}

	if ($gz !== null)
	{
		close_child($gz);
	}

	echo str_pad($name, 12) . ": " . number_format($total) . " URLs in " . $file_index . " file(s)\n";
}

// --- Sitemap index ----------------------------------------------------------

$index  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$index .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($child_files as $rel)
{
	$loc = BASE_URL . $rel;
	$index .= '  <sitemap><loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc></sitemap>' . "\n";
}
$index .= '</sitemapindex>' . "\n";

file_put_contents(INDEX_FILE, $index);

echo "index       : " . count($child_files) . " child sitemap(s) -> " . INDEX_FILE . "\n";

?>
