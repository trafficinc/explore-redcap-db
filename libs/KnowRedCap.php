<?php


class KnowRedCap {
    private $db;
    private string $cachfilePath;
    private string $rootfilePath;

    public array $tables;

//  die("\e[31mError, `tables.php` does not exist yet, please run `php learndb.php get-tables` to create the file.\e[0m");
    public function __construct($db) {
        $this->db = $db;
        $this->cachfilePath = dirname(__DIR__).DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
        $this->rootfilePath = dirname(__DIR__).DIRECTORY_SEPARATOR;
        if (!file_exists($this->rootfilePath."tables.php")) {
            $this->getTables();
            $this->tables = include($this->rootfilePath.'tables.php');
        } else {
            $this->tables = include($this->rootfilePath.'tables.php');
        }
    }

    public function beforeSnapshot() {
        $tableMatrix = $this->getRecordsCount();

        $this->createJsonFile('old_snapshot.json', $tableMatrix);
    }

    public function afterSnapshot() {
        $tableMatrix = $this->getRecordsCount();
        $this->createJsonFile('new_snapshot.json', $tableMatrix);
    }

    public function compare() {
        // Load JSON files into PHP arrays
        $file1 = file_get_contents($this->cachfilePath.'old_snapshot.json');
        $file2 = file_get_contents($this->cachfilePath.'new_snapshot.json');

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
            echo "Deletes found:\n";
            foreach ($differences['deletes'] as $delete) {
                echo $delete . "\n";
            }
            echo "Inserts found:\n";
            foreach ($differences['inserts'] as $inserts) {
                echo $inserts . "\n";
            }
        }
    }


    private function compareArrays($array1, $array2, $path = '') {
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
                    $result = compareArrays($value, $array2[$key], $currentPath);
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
    
        return ['differences' => $differences,'deletes' => $deletes, 'inserts' => $inserts];
    }

    public function getRecordsCount() {
        $coll = [];
        foreach($this->tables as $table) {
            $this->db->query("SELECT * from $table");
            $coll[$table] = $this->db->rowCount();
        }
        return $coll;
    }

    public function createJsonFile($fileName, $data) {
        $file = $this->cachfilePath.$fileName;
		if (file_exists($file)) {
			$this->deleteFile($file);
		}
        $data = $this->formatArrayToJson($data);
		file_put_contents($file, $data);
    }

    public function createPHPFile($fileName, $data) {
        $file = $this->rootfilePath.$fileName;
		if (file_exists($file)) {
			$this->deleteFile($file);
		}
		file_put_contents($file, $data);
    }

    public function deleteFile($fileName) {
		unlink($fileName);
	}

    private function formatArrayToJson($data) {
        $coll = [];
        foreach($data as $key => $value) {
            $coll[] = "\"$key\":$value";
        }
        return "{".implode(",",$coll)."}";
    }

    public function getTables() {
        echo "Fetching tables...\n";
        $this->db->query("SHOW TABLES");
        echo "Generating `tables.php` file...\n";
        $dbTables = [];
        foreach ($this->db->resultset() as $table) {
            $dbTables[] = $table->Tables_in_redcap;
        }
        echo "Generating PHP File...\n";
        $data = "<?php\n" . "return ['".implode("','", $dbTables)."'];\n";
        $this->createPHPFile("tables.php", $data);
        echo "Done.\n";
    }

}

function helpMenu() {
    echo "\e[32mThis is a small tool to get to know which tables in Redcap are affected by actions via DB inserts and deletes.\n" . 
                "First get the tables from the DB in Redcap run: $ php learndb.php get-tables\n" . 
                "1) Before you make a change (ex. fill out and submit a form) in Redcap run: $ php learndb.php before\n" . 
                "2) After you make a change in Redcap run: $ php learndb.php after\n" . 
                "3) To see what was changed in the DB tables, run: $ php learndb.php compare \e[0m \n";
}