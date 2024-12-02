<?php

class TableProcessor {

    private $tables;
    private $db;
    private $rootfilePath;

    public function __construct($tables, $db, $rootfilePath) {
        $this->tables = $tables;
        $this->db = $db;
        $this->rootfilePath = $rootfilePath;
    }

    // Chunking function to split tables into smaller chunks
    public function chunkTables($tables, $chunkSize) {
        $chunks = array_chunk($tables, $chunkSize); 
        foreach ($chunks as $chunk) {
            yield $chunk; 
        }
    }

    // Generator function to yield each table in the chunk
    public function getTablesGenerator($tables) {
        foreach ($tables as $table) {
            yield $table; 
        }
    }

    public function processTables($tag) {
        $tables =  $this->tables;  // Get tables from the service
        
        // Initialize an array to store table information
        $tableData = [];

        // Using chunkTables to process in chunks
        foreach ($this->chunkTables($tables, 2) as $tableBatch) { 
            //echo "Processing batch: \n";
            // Process each batch (you can process all tables in the batch here)
            foreach ($this->getTablesGenerator($tableBatch) as $table) {
                $cols = $this->getColumns($table);
                if (count($this->processTablesUpdatesWatch($table, $cols)) > 0) {
                    $tableInfo = ["table" => $table ,"pk_name" => $cols[0],"rows" => $this->processTablesUpdatesWatch($table, $cols)];
                    $tableData[] = $tableInfo;
                } else {
                    $tableInfo = ["table" => $table ,"pk_name" => "NULL","rows" => 0];
                    $tableData[] = $tableInfo;
                }
            }
        }
        $jsonData = json_encode($tableData, JSON_PRETTY_PRINT);
    
        // Write the JSON data to a file 
        if (file_put_contents($this->rootfilePath."cache/{$tag}_processed_tables.json", $jsonData)) {
            echo "Table processing complete. Data written to processed_tables.json.\n";
        } else {
            echo "Error writing data to the file.\n";
        }

    }

    public function getColumns($table) {
		$this->db->query("SELECT COLUMN_NAME from information_schema.columns where table_schema = 'redcap' and table_name = '$table'");
        $tableColumns = [];
        foreach ($this->db->resultset() as $column) {
            $tableColumns[] = $column->COLUMN_NAME;
        }
		return $tableColumns;
	}

    private function processTablesUpdatesWatch($table, $cols) {
        $coalescedCols = "COALESCE(`".implode("`),COALESCE(`",$cols)."`)";

        $sql = "SELECT ".$cols[0].", MD5(CONCAT(". $coalescedCols .")) AS row_hash FROM $table";
        $this->db->query($sql);
        if ( $rowHashes = $this->db->fetchAll(PDO::FETCH_ASSOC) ) {
            $rows = [];
            foreach($rowHashes as $rowHashKey => $rowHash) {
                if (array_key_exists('row_hash', $rowHash) && !empty($rowHash['row_hash'])) {
                    $rw = [];
                    $rw['table_name']  = $table;
                    $rw['pk_row_name'] = $cols[0];
                    $rw['pk_row_id']   = $rowHashKey;
                    $rw['row_hash']    = $rowHash['row_hash'];
                    $rows[] = $rw;
                }
            }
            return $rows;

        } 
        return [];
        
    }
}
