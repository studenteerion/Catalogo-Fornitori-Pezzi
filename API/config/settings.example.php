<?php

declare(strict_types=1);

$dbHost = '127.0.0.1';
$dbPort = '3306';
$dbName = '';
$dbCharset = 'utf8mb4';

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $dbHost,
    $dbPort,
    $dbName,
    $dbCharset
);

return [
    'db' => [
        'dsn' => $dsn,
        'user' => '',
        'pass' => '',
    ],
];