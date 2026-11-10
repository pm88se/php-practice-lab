<?php

declare(strict_types=1);

/**
 * Open an independent MySQL connection. Call twice to simulate two sessions.
 * Defaults are only for the local Docker lab; override via MYSQL_* env vars.
 */
function mysqlLabConnection(): PDO
{
    $host = getenv('MYSQL_HOST') ?: '127.0.0.1';
    $port = getenv('MYSQL_PORT') ?: '3307';
    $db = getenv('MYSQL_DATABASE') ?: 'practice_lab';
    $user = getenv('MYSQL_USER') ?: 'lab';
    $password = getenv('MYSQL_PASSWORD');
    if ($password === false) {
        $password = 'lab';
    }

    return new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db),
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ],
    );
}
