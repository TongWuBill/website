<?php

function get_db() {
    static $db = null;

    if ($db === null) {
        $config = require __DIR__ . '/config.php';
        $db = new PDO('sqlite:' . $config['db_path']);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    return $db;
}