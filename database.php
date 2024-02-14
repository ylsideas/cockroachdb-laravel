<?php

$host = '127.0.0.1';
$db = 'default';
$user = 'root';
$pass = '';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$dsn = "pgsql:host=$host;port=26257;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    $pdo->exec(<<<heredoc
        DROP DATABASE IF EXISTS forge;
        DROP USER IF EXISTS forge;
        CREATE DATABASE forge;
        CREATE USER forge;
        GRANT ALL PRIVILEGES ON DATABASE forge TO forge;
        heredoc);

    $statements = [
        'SET CLUSTER SETTING kv.raft_log.disable_synchronization_unsafe = true;',
        'SET CLUSTER SETTING kv.range_merge.queue_interval = \'50ms\';',
        'SET CLUSTER SETTING jobs.registry.interval.gc = \'30s\';',
        'SET CLUSTER SETTING jobs.registry.interval.cancel = \'180s\';',
        'SET CLUSTER SETTING jobs.retention_time = \'15s\';',
        'SET CLUSTER SETTING sql.stats.automatic_collection.enabled = false;',
        'SET CLUSTER SETTING kv.range_split.by_load_merge_delay = \'5s\';',
        'ALTER RANGE default CONFIGURE ZONE USING "gc.ttlseconds" = 5;',
        'ALTER DATABASE system CONFIGURE ZONE USING "gc.ttlseconds" = 5;',
    ];

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }

} catch (PDOException $exception) {
    exit('Failed to creating database: ' . $exception->getMessage() . PHP_EOL);
}

echo 'Database & User created' . PHP_EOL;
