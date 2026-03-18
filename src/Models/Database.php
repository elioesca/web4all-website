<?php

namespace App\Models;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $config = require __DIR__ . '/../../config/database.php';

            $uri = 'mysql:host=' . $config['host']
                . ';port=' . $config['port']
                . ';dbname=' . $config['dbname']
                . ';charset=' . $config['charset'];

            try {
                self::$connection = new PDO(
                    $uri,
                    $config['username'],
                    $config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                die('Erreur de connexion à la base de données : ' . $e->getMessage());
            }
        }

        return self::$connection;
    }
}