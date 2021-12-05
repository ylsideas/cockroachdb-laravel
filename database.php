<?php

$host = '127.0.0.1';
$db   = 'default';
$user = 'root';
$pass = '';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$dsn = "pgsql:host=$host;port=26257;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    $pdo->exec(<<<heredoc
CREATE DATABASE IF NOT EXISTS forge;
CREATE USER IF NOT EXISTS forge;
GRANT ALL ON DATABASE forge TO forge;
heredoc);
} catch (PDOException $exception) {
    exit('Failed to creating database: ' . $exception->getMessage() . PHP_EOL);
}

echo 'Database & User created' . PHP_EOL;
