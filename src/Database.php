<?php
// Fichier: src/Database.php

class Database {
    private static ?PDO $instance = null;
    // VEUILLEZ VÉRIFIER CES CONSTANTES POUR VOTRE ENVIRONNEMENT LOCAL
    private const DB_HOST = 'localhost';
    private const DB_NAME = 'ecoride_db';
    private const DB_USER = 'root';
    private const DB_PASS = '';

    /**
     * Établit la connexion PDO en utilisant le pattern Singleton.
     * @return PDO
     * @throws PDOException si la connexion échoue.
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . self::DB_HOST . ";dbname=" . self::DB_NAME . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES  => false,
                ];

                self::$instance = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            } catch (PDOException $e) {
                // Au lieu de retourner null (ce qui empêcherait le try/catch de covoiturages.php de fonctionner),
                // nous relançons l'exception avec un message clair.
                throw new PDOException("Erreur de connexion à la base de données. Veuillez vérifier les constantes de configuration (HOST, NAME, USER, PASS) dans src/Database.php. Erreur originale: " . $e->getMessage());
            }
        }
        // La méthode retourne toujours une instance PDO si la connexion réussit
        return self::$instance;
    }
}