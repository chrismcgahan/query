<?php

require dirname(__DIR__) . '/Query.php';

use chrismcgahan\query\Query;

$db = new Query([
    'host'     => 'localhost',
    'username' => 'root',
    'database' => 'test'
]);

$insertId = $db->insert('pairs', [
    'key'   => time(),
    'value' => date('Y-m-d H:i:s')
]);

print $insertId . "\n";

print_r($db->getRow('SELECT * FROM pairs WHERE `id` = ?', [$insertId]));

$db->update('pairs', [
    'value' => 'updated'
], ['id' => $insertId]);

print_r($db->getRow('SELECT * FROM pairs WHERE `id` = ?', [$insertId]));

$db->update('pairs', [
    'value' => 'updated again'
], "`id`=$insertId");

print_r($db->getRow('SELECT * FROM pairs WHERE `id` = ?', [$insertId]));