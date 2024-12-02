<?php

include("TableProcessor.php");

class KnowRedCap
{

    private $db;

    private string $cachfilePath;

    private string $rootfilePath;

    private array $config;

    public array $tables;

    public function __construct($db)
    {
        $this->db = $db;
        $this->cachfilePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
        $this->rootfilePath = dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $this->config = include(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

        if (!file_exists($this->rootfilePath . "tables.php")) {
            $this->getTables();
            $this->tables = include($this->rootfilePath . 'tables.php');
        } else {
            $this->tables = include($this->rootfilePath . 'tables.php');
        }
    }

    public function beforeSnapshot()
    {
        $tableMatrix = $this->getRecordsCount();

        $this->createJsonFile('old_snapshot.json', $tableMatrix);
        // track updates
        $this->updateSetUp("before");
    }

    public function afterSnapshot()
    {
        $tableMatrix = $this->getRecordsCount();
        $this->createJsonFile('new_snapshot.json', $tableMatrix);
        // track updates
        $this->updateSetUp("after");
    }

    public function compare()
    {
        // Load JSON files into PHP arrays
        $file1 = file_get_contents($this->cachfilePath . 'old_snapshot.json');
        $file2 = file_get_contents($this->cachfilePath . 'new_snapshot.json');

        $json1 = json_decode($file1, true);
        $json2 = json_decode($file2, true);

        // Check if both files are valid JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            die("Error decoding JSON files.");
        }

        // Compare the two JSON arrays
        $differences = $this->compareArrays($json1, $json2);

        // Output the differences
        if (empty($differences['differences'])) {
            echo "\e[32mThere are no changes.\e[0m\n";
        } else {
            echo "Differences found:\n";
            foreach ($differences['differences'] as $difference) {
                echo $difference . "\n";
            }
            echo "------------------------------------------------\n";
            echo "Deletes found:\n";
            foreach ($differences['deletes'] as $delete) {
                echo $delete . "\n";
            }
            echo "Inserts found:\n";
            foreach ($differences['inserts'] as $inserts) {
                echo $inserts . "\n";
            }
            echo "------------------------------------------------\n";
            $this->compareUpdates();
        }
    }

    public function compareUpdates()
    {
        $file1 = file_get_contents($this->cachfilePath . 'before_processed_tables.json');
        $file2 = file_get_contents($this->cachfilePath . 'after_processed_tables.json');

        $json1 = json_decode($file1, true);
        $json2 = json_decode($file2, true);

        // Check if both files are valid JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            die("Error decoding JSON files.");
        }

        // Compare the two JSON arrays
        $differences = $this->compareUpdateArrays($json1, $json2);

        // Output the differences
        if (empty($differences)) {
            echo "\e[32mThere are no changes.\e[0m\n";
        } else {
            echo "Updates found:\n";
            foreach ($differences as $difference) {
                echo "  table ";
                echo "'" . $difference['table_name'] . "'";
                echo "  [UPDATE] PK Column Name: ";
                echo " '" . $difference['pk_row_name'] . "'";
                echo "  PK Row ID: ";
                echo $difference['pk_row_id'];
                echo "\n";
            }
            echo "------------------------------------------------\n";
        }
    }


    private function compareUpdateArrays($array1, $array2, $path = '')
    {

        $collection = [];

        // Compare arrays' keys
        foreach ($array1 as $key => $value) {

            $currentPath = $path ? "$path.$key" : $key;

            // If the key exists in array2, compare values
            if (array_key_exists($key, $array2) && $value['table'] === $array2[$key]['table']) {
                // If both values are arrays, recurse into them
                if (is_array($value) && is_array($array2[$key])) {

                    if (!is_int($value['rows']) && !is_int($array2[$key]['rows'])) {
                        $vrows = $value["rows"];
                        array_walk($array2[$key]["rows"], function ($filter) use (&$vrows) {
                            $vrows = array_filter($vrows, function ($entry) use ($filter) {
                                return $entry["row_hash"] != $filter["row_hash"];
                            });
                        });

                        if (count($vrows) > 0) {
                            $collection[] = array_shift($vrows);
                        }
                    }
                }
            }
        }
        return $collection;
    }



    private function compareArrays($array1, $array2, $path = '')
    {
        $differences = [];
        $deletes = [];
        $inserts = [];

        // Compare arrays' keys
        foreach ($array1 as $key => $value) {
            $currentPath = $path ? "$path.$key" : $key;

            // If the key exists in array2, compare values
            if (array_key_exists($key, $array2)) {
                // If both values are arrays, recurse into them
                if (is_array($value) && is_array($array2[$key])) {
                    $result = $this->compareArrays($value, $array2[$key], $currentPath);
                    if (!empty($result)) {
                        $differences = array_merge($differences, $result);
                    }
                } else {
                    // If values are different, store the difference
                    if ($value !== $array2[$key]) {
                        $differences[] = "  Difference at table '$currentPath' : $value vs {$array2[$key]}";
                        if ($array2[$key] < $value) {
                            // delete
                            $deletes[] = "  table '$currentPath' [DELETE] : OLD: $value vs. NEW: {$array2[$key]}";
                        }
                        if ($array2[$key] > $value) {
                            // insert
                            $inserts[] = "  table '$currentPath' [INSERT] : OLD: $value vs. NEW: {$array2[$key]}";
                        }
                    }
                }
            } else {
                // If the key doesn't exist in array2
                $differences[] = "Key '$currentPath' is missing in the second JSON file.";
            }
        }

        // Check for any extra keys in array2 that are not in array1
        foreach ($array2 as $key => $value) {
            $currentPath = $path ? "$path.$key" : $key;
            if (!array_key_exists($key, $array1)) {
                $differences[] = "Key '$currentPath' is extra in the second JSON file.";
            }
        }

        return ['differences' => $differences, 'deletes' => $deletes, 'inserts' => $inserts];
    }

    public function getRecordsCount()
    {
        $coll = [];
        foreach ($this->tables as $table) {
            $this->db->query("SELECT * from $table");
            $coll[$table] = $this->db->rowCount();
        }
        return $coll;
    }

    public function createJsonFile($fileName, $data)
    {
        $file = $this->cachfilePath . $fileName;
        if (file_exists($file)) {
            $this->deleteFile($file);
        }
        $data = $this->formatArrayToJson($data);
        file_put_contents($file, $data);
    }

    public function createPHPFile($fileName, $data)
    {
        $file = $this->rootfilePath . $fileName;
        if (file_exists($file)) {
            $this->deleteFile($file);
        }
        file_put_contents($file, $data);
    }

    public function createAnyFile($filePathName, $data)
    {
        file_put_contents($filePathName, $data);
    }

    public function deleteFile($fileName)
    {
        unlink($fileName);
    }

    private function formatArrayToJson($data)
    {
        $coll = [];
        foreach ($data as $key => $value) {
            $coll[] = "\"$key\":$value";
        }
        return "{" . implode(",", $coll) . "}";
    }

    public function exportFile($fileSplit)
    {
        $fileName = explode("=", $fileSplit)[1];
        $filePathName = $this->rootfilePath . $this->config['file_save_dir'] . DIRECTORY_SEPARATOR . $fileName;
        echo "Starting to generate file...\n";
        if (file_exists($filePathName)) {
            echo "\e[31mExport error, file exists!\e[0m\n";
            exit();
        } else {
            ob_start();
            $this->compare();
            $data = ob_get_clean();
            $this->createAnyFile($filePathName, $data);
        }
        echo "Done.\n";
    }

    private function getTablesService()
    {
        $this->db->query("SHOW TABLES");
        $dbTables = [];
        foreach ($this->db->resultset() as $table) {
            $dbTables[] = $table->Tables_in_redcap;
        }
        return $dbTables;
    }

    public function getTables()
    {
        echo "Fetching tables...\n";
        echo "Generating `tables.php` file...\n";
        $dbTables = $this->getTablesService();
        echo "Generating PHP File...\n";
        $data = "<?php\n" . "return ['" . implode("','", $dbTables) . "'];\n";
        $this->createPHPFile("tables.php", $data);
        echo "Done.\n";
    }

    public function updateSetUp($tag)
    {
        $tables = $this->getTablesService();
        $tableProcessor = new TableProcessor($tables, $this->db, $this->rootfilePath);
        $tableProcessor->processTables($tag);
    }
}

function helpMenu()
{
    echo "\e[32mThis is a small tool to get to know which tables in Redcap are affected by actions via DB inserts and deletes.\n" .
        "First get the tables from the DB in Redcap run: $ php learndb.php get-tables\n" .
        "1) Before you make a change (ex. fill out and submit a form) in Redcap run: $ php learndb.php before\n" .
        "2) After you make a change in Redcap run: $ php learndb.php after\n" .
        "3) To see what was changed in the DB tables, run: $ php learndb.php compare \e[0m \n";
}
