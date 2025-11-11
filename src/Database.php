<?php
// Fichier: src/Database.php

class Database {
    private static ?PDO $instance = null;
     // VEUILLEZ VÉRIFIER CES CONSTANTES POUR VOTRE ENVIRONNEMENT LOCAL
    private const DB_HOST = 'localhost';
    private const DB_NAME = 'ecoride_db';
    private const DB_USER = 'root';
    private const DB_PASS = 'password321';
    // Paramètres spécifiques à l'environnement
    // Sous XAMPP (Windows), le port MySQL par défaut est 3306 et aucun socket n'est utilisé
    private const DB_PORT = 3306;
    private const DB_SOCKET = '';
    
    /**
     * Établit la connexion PDO en utilisant le pattern Singleton.
     * @return PDO
     * @throws PDOException si la connexion échoue.
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    self::DB_HOST,
                    self::DB_PORT,
                    self::DB_NAME
                );
                $options = [
                    PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES  => false,
                ];

                self::$instance = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            } catch (PDOException $primaryException) {
                // Tentative de repli via le socket Unix (utile avec MAMP)
                if (!empty(self::DB_SOCKET) && file_exists(self::DB_SOCKET)) {
                    $socketDsn = sprintf(
                        'mysql:unix_socket=%s;dbname=%s;charset=utf8mb4',
                        self::DB_SOCKET,
                        self::DB_NAME
                    );
                    try {
                        self::$instance = new PDO($socketDsn, self::DB_USER, self::DB_PASS, $options);
                    } catch (PDOException $secondaryException) {
                        throw new PDOException(
                            "Erreur de connexion à la base de données. Essais échoués sur le port "
                            . self::DB_PORT . " et via le socket. Détails: "
                            . $primaryException->getMessage() . ' | '
                            . $secondaryException->getMessage()
                        );
                    }
                } else {
                    // Au lieu de retourner null (ce qui empêcherait le try/catch de covoiturages.php de fonctionner),
                    // nous relançons l'exception avec un message clair.
                    throw new PDOException(
                        "Erreur de connexion à la base de données. Vérifiez la configuration (HOST, PORT, USER, PASS). "
                        . "Erreur originale: " . $primaryException->getMessage()
                    );
                }
            }
        }
        // La méthode retourne toujours une instance PDO si la connexion réussit
        return self::$instance;
    }
}
