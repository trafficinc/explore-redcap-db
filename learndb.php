<?php
require_once('libs/Database.php');
require_once('libs/KnowRedCap.php');

$db = new Database;
$rc = new KnowRedCap($db);

if ($argc > 1) {
    for ($i = 1; $i < $argc; $i++) {
        switch($argv[$i]) {
            case "before":
                $rc->beforeSnapshot();
            break;
            case "after":  
                $rc->afterSnapshot();
            break;
            case "compare":
                $rc->compare();
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




