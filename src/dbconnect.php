<?php

/**
 * MySQL 接続テスト用。
 * 接続情報はハードコードせず、docker-compose.yml 経由の環境変数から読み取る。
 */

function getDbConnection(): PDO
{
    $host = getenv('DB_HOST');
    $dbName = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $password = getenv('DB_PASSWORD');

    $dsn = "mysql:host={$host};dbname={$dbName};charset=utf8mb4";

    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
