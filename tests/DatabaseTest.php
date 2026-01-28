<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config.inc.php';
require_once __DIR__ . '/../sqlite.php';

class DatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        global $config;

        // Skip tests if database doesn't exist or isn't accessible
        if (!isset($config['pdo']) || !$config['pdo']) {
            $this->markTestSkipped('Database not configured or not accessible');
        }

        // Check if names table exists
        try {
            $stmt = $config['pdo']->query("SELECT name FROM sqlite_master WHERE type='table' AND name='names'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                $this->markTestSkipped('Names table does not exist in database');
            }
        } catch (Exception $e) {
            $this->markTestSkipped('Cannot access database: ' . $e->getMessage());
        }
    }

    public function testDatabaseConnection()
    {
        global $config;

        $this->assertNotNull($config['pdo'], 'PDO connection should be initialized');
        $this->assertInstanceOf(PDO::class, $config['pdo'], 'Config should contain a PDO instance');
    }

    public function testDatabaseQuery()
    {
        $sql = "SELECT COUNT(*) as count FROM names";
        $data = db_get($sql);

        $this->assertIsArray($data, 'db_get should return an array');
        $this->assertNotEmpty($data, 'Query result should not be empty');
        $this->assertIsObject($data[0], 'First result should be an object');
        $this->assertObjectHasProperty('count', $data[0], 'Result should have count property');
        $this->assertIsNumeric($data[0]->count, 'Count should be numeric');
        $this->assertGreaterThanOrEqual(0, $data[0]->count, 'Count should be non-negative');
    }

    public function testDatabaseStructure()
    {
        // Test that key columns exist in names table
        $sql = "SELECT id, nameComplete, genusPart, taxonAuthor FROM names LIMIT 1";
        $data = db_get($sql);

        $this->assertIsArray($data, 'db_get should return an array');

        if (!empty($data)) {
            $this->assertObjectHasProperty('id', $data[0], 'Result should have id');
            $this->assertObjectHasProperty('nameComplete', $data[0], 'Result should have nameComplete');
        }
    }

    public function testSearchNamesFunction()
    {
        require_once __DIR__ . '/../objects.php';

        // Test exact name search (will return empty if name doesn't exist, which is fine)
        $results = search_names('Homo sapiens');

        $this->assertIsObject($results, 'search_names should return an object');
        $this->assertEquals('DataFeed', $results->type, 'Result should be a DataFeed');
        $this->assertIsArray($results->dataFeedElement, 'Result should have dataFeedElement array');
    }

    public function testGenusSearch()
    {
        require_once __DIR__ . '/../objects.php';

        // Test genus search
        $results = search_names('genus:Homo');

        $this->assertIsObject($results, 'search_names should return an object');
        $this->assertEquals('DataFeed', $results->type, 'Result should be a DataFeed');
        $this->assertIsArray($results->dataFeedElement, 'Result should have dataFeedElement array');
    }
}
