<?php

function db_column_exists(PDO $pdo, string $table, string $column): bool
{
    static $cache = [];

    $key = strtoupper($table) . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);

    $cache[$key] = (bool) $stmt->fetchColumn();
    return $cache[$key];
}
