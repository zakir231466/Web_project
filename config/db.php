<?php

function getDbConnection()
{
    static $connection = null;

    if ($connection === null) {
        $connection = new mysqli('127.0.0.1', 'root', '', 'uiu_canteen');

        if ($connection->connect_error) {
            throw new RuntimeException('Database connection failed: ' . $connection->connect_error);
        }

        $connection->set_charset('utf8mb4');
    }

    return $connection;
}
