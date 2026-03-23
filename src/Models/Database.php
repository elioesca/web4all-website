<?php

namespace App\Models;

use PDO;
use PDOException;

/**
 * Database
 *
 * Singleton PDO pour la connexion à la base de données.
 * Gestion centralisée de la configuration et du comportement de l'objet PDO.
 */
class Database
{
    /**
     * Instance PDO en cache (singleton).
     */
    private static ?PDO $connection = null;

    /**
     * Récupère la connexion PDO. Initialise la connexion une seule fois.
     *
     * @return PDO
     */
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
                        // Exceptions pour faciliter la gestion d'erreurs en amont.
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        // Mode de récupération des résultats : tableau associatif.
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                // Arrêt immédiat si la connexion échoue.
                die('Erreur de connexion à la base de données : ' . $e->getMessage());
            }
        }

        return self::$connection;
    }
}