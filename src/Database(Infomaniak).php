<?php
// Fichier: src/Database.php

class Database {
    private static ?PDO $instance = null;
    private static ?array $config = null;
    // Valeurs locales par defaut pouvant etre surchargees via des variables d'environnement.
    private const DEFAULTS = [
        'host' => 'ppeu.myd.infomaniak.com',
        'name' => 'ppeu_uatifbxbd',
        'user' => 'ppeu_uecoridedb',
        'pass' => 'U@ecoridedb#25',
        'port' => 3306,
        'socket' => '',
    ];
    
    /**
     * Etablit la connexion PDO en utilisant le pattern Singleton.
     * @return PDO
     * @throws PDOException si la connexion echoue.
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $config = self::getConfig();
            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    $config['host'],
                    $config['port'],
                    $config['name']
                );
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];

                self::$instance = new PDO($dsn, $config['user'], $config['pass'], $options);
            } catch (PDOException $primaryException) {
                // Tentative de repli via le socket Unix (utile avec MAMP ou certains hebergeurs).
                if (!empty($config['socket']) && file_exists($config['socket'])) {
                    $socketDsn = sprintf(
                        'mysql:unix_socket=%s;dbname=%s;charset=utf8mb4',
                        $config['socket'],
                        $config['name']
                    );
                    try {
                        self::$instance = new PDO($socketDsn, $config['user'], $config['pass'], $options);
                    } catch (PDOException $secondaryException) {
                        throw new PDOException(
                            'Erreur de connexion a la base de donnees. Essais echoues sur le port '
                            . $config['port'] . ' et via le socket. Details: '
                            . $primaryException->getMessage() . ' | '
                            . $secondaryException->getMessage()
                        );
                    }
                } else {
                    throw new PDOException(
                        'Erreur de connexion a la base de donnees. Verifiez la configuration (HOST, PORT, USER, PASS). '
                        . 'Erreur originale: ' . $primaryException->getMessage()
                    );
                }
            }
        }

        return self::$instance;
    }

    private static function getConfig(): array {
        if (self::$config !== null) {
            return self::$config;
        }

        $config = self::DEFAULTS;
        $map = [
            'host' => ['DB_HOST', 'MYSQL_HOST', 'MYSQLHOST', 'INFOMANIAK_MYSQL_HOST', 'IK_DB_HOST'],
            'name' => ['DB_NAME', 'MYSQL_DATABASE', 'MYSQL_DBNAME', 'INFOMANIAK_MYSQL_DB', 'IK_DB_NAME'],
            'user' => ['DB_USER', 'MYSQL_USER', 'MYSQL_USERNAME', 'INFOMANIAK_MYSQL_USER', 'IK_DB_USER'],
            'pass' => ['DB_PASS', 'MYSQL_PASSWORD', 'MYSQL_PASS', 'INFOMANIAK_MYSQL_PASS', 'IK_DB_PASSWORD'],
            'port' => ['DB_PORT', 'MYSQL_PORT'],
            'socket' => ['DB_SOCKET', 'MYSQL_SOCKET'],
        ];

        foreach ($map as $key => $envKeys) {
            $value = self::env($envKeys);
            if ($value === null) {
                continue;
            }

            if ($key === 'port') {
                $port = filter_var($value, FILTER_VALIDATE_INT);
                if ($port !== false && $port > 0) {
                    $config[$key] = $port;
                }
                continue;
            }

            $config[$key] = $value;
        }

        // Support des URLs type DATABASE_URL=mysql://user:pass@host:port/dbname
        $url = self::env(['DATABASE_URL', 'JAWSDB_URL', 'CLEARDB_DATABASE_URL']);
        if ($url) {
            $parsed = parse_url($url);
            if ($parsed !== false) {
                if (!empty($parsed['host'])) {
                    $config['host'] = $parsed['host'];
                }
                if (!empty($parsed['path'])) {
                    $config['name'] = ltrim($parsed['path'], '/');
                }
                if (!empty($parsed['user'])) {
                    $config['user'] = $parsed['user'];
                }
                if (array_key_exists('pass', $parsed) && $parsed['pass'] !== '') {
                    $config['pass'] = $parsed['pass'];
                }
                if (!empty($parsed['port'])) {
                    $port = filter_var((string) $parsed['port'], FILTER_VALIDATE_INT);
                    if ($port !== false && $port > 0) {
                        $config['port'] = $port;
                    }
                }
            }
        }

        self::$config = $config;
        return $config;
    }

    private static function env(array $keys): ?string {
        foreach ($keys as $key) {
            $value = self::envValue($key);
            if ($value !== null) {
                return $value;
            }
        }
        return null;
    }

    private static function envValue(string $key): ?string {
        $candidates = [
            getenv($key),
            $_ENV[$key] ?? null,
            $_SERVER[$key] ?? null,
        ];

        foreach ($candidates as $value) {
            if ($value === false || $value === null) {
                continue;
            }
            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    continue;
                }
                return $trimmed;
            }
            return $value;
        }

        return null;
    }
}
