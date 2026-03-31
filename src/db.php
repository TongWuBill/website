<?php

date_default_timezone_set('America/New_York');

// Absolute path to the SQLite file — anchored to this file's location (/src/db.php)
// so it resolves correctly regardless of where PHP is invoked from.
define('DB_PATH', realpath(__DIR__ . '/../database/portfolio.sqlite')
    ?: __DIR__ . '/../database/portfolio.sqlite');

function get_db_path(): string {
    return DB_PATH;
}

function get_db(): PDO {
    static $db = null;

    if ($db === null) {
        $path = get_db_path();

        if (!file_exists($path)) {
            throw new RuntimeException("SQLite database not found at: $path");
        }

        $db = new PDO('sqlite:' . $path);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    return $db;
}
