<?php
require_once('libs/Database.php');
require_once('libs/KnowRedCap.php');

$db = new Database;
$rc = new KnowRedCap($db);

define('DEBUG', false);

if ($argc > 1) {
    for ($i = 1; $i < $argc; $i++) {
        switch ($argv[$i]) {
            case "before":
                $rc->beforeSnapshot();
                break;
            case "after":
                $rc->afterSnapshot();
                break;
            case "compare":
                $rc->compare();
                break;
            case str_contains($argv[$i], 'export'):
                $rc->exportFile($argv[$i]);
                break;
            case "get-tables":
                $rc->getTables();
                break;
            case "help":
                helpMenu();
                break;
            case "h":
                helpMenu();
                break;
        }
    }
} else {
    echo "No arguments passed.\n";
}

if (DEBUG) {
    /* Displaying used memory */
    echo "Memory consumed: " . round(memory_get_usage() / 1024) . "KB\n";
    /* Displaing peak memory usage */
    echo "Peak usage: " . round(memory_get_peak_usage() / 1024) . "KB of memory.\n";
}
