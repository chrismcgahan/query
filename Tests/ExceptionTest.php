<?php

require dirname(__DIR__) . '/Query.php';

use chrismcgahan\query\Query;

$db = new Query([
    'host'     => 'localhost',
    'username' => 'root',
    'database' => 'test'
]);

try {
    $db->query('this is a bad query');
}
catch (Exception $e) {
    print $e->getMessage() . "\n";
}

try {
    $db->getOne('SELECT * FROM pairs', [1]);
}
catch (Exception $e) {
    print $e->getMessage() . "\n";
}