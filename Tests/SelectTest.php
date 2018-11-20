<?php

require dirname(__DIR__) . '/Query.php';

use chrismcgahan\query\Query;

$db = new Query([
    'host'     => 'localhost',
    'username' => 'root',
    'database' => 'test'
]);

print_r($db->getAll('SELECT * FROM pairs WHERE `key` IN (?)', [['foo', 'x']]));

print_r($db->getRow('SELECT * FROM pairs WHERE `key` = ?', ['x']));

print $db->getOne('SELECT * FROM pairs WHERE `key` = ?', ['x']) . "\n";

print_r($db->getCol('SELECT * FROM pairs', 2));