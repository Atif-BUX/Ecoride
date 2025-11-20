<?php
// Fichier: src/Database.php

class Database {
    private static ?PDO $instance = null;
    private static ?array $config = null;
    private static array $envFileValues = [];
    private static bool $envFileLoaded = false;

    private const DEFAULTS = [
        'host' => 'localhost',
        'name' => 'ecoride_db',
        'user' => 'root',
        'pass' => 'root',
        'port' => 3306,
        'socket' => '/Applications/MAMP/tmp/mysql/mysql.sock',
    ];

    private const ENV_FILE_ORDER = ['.env', '.env.local'];

    private const ENV_MAP = [
        'host' => ['DB_HOST', 'MYSQL_HOST', 'MYSQLHOST'],
        'name' => ['DB_NAME', 'MYSQL_DATABASE', 'MYSQL_DBNAME'],
        'user' => ['DB_USER', 'MYSQL_USER', 'MYSQL_USERNAME'],
        'pass' => ['DB_PASS', 'MYSQL_PASSWORD', 'MYSQL_PASS'],
        'port' => ['DB_PORT', 'MYSQL_PORT'],
        'socket' => ['DB_SOCKET', 'MYSQL_SOCKET'],
    ];

    /**
     * Établit la connexion PDO en utilisant le pattern Singleton.
     * @return PDO
     * @throws PDOException si la connexion échoue.
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
                    PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES  => false,
                ];

                self::$instance = new PDO($dsn, $config['user'], $config['pass'], $options);
            } catch (PDOException $primaryException) {
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
                            "Erreur de connexion à la base de données. Essais échoués sur le port "
                            . $config['port'] . " et via le socket. Détails: "
                            . $primaryException->getMessage() . ' | '
                            . $secondaryException->getMessage()
                        );
                    }
                } else {
                    throw new PDOException(
                        "Erreur de connexion à la base de données. Vérifiez la configuration (HOST, PORT, USER, PASS). "
                        . "Erreur originale: " . $primaryException->getMessage()
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
        foreach (self::ENV_MAP as $key => $envNames) {
            foreach ($envNames as $envName) {
                $value = self::envValue($envName);
                if ($value === null) {
                    continue;
                }
                if ($key === 'port') {
                    $port = filter_var($value, FILTER_VALIDATE_INT);
                    if ($port !== false && $port > 0) {
                        $config[$key] = $port;
                    }
                } else {
                    $config[$key] = $value;
                }
                break;
            }
        }

        $databaseUrl = self::envValue('DATABASE_URL')
            ?? self::envValue('JAWSDB_URL')
            ?? self::envValue('CLEARDB_DATABASE_URL');

        if ($databaseUrl) {
            $parsed = parse_url($databaseUrl);
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
                if (array_key_exists('pass', $parsed)) {
                    $config['pass'] = $parsed['pass'] ?? '';
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
        return self::$config;
    }

    private static function envValue(string $key): ?string {
        self::loadEnvFiles();

        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        if (array_key_exists($key, self::$envFileValues)) {
            return self::$envFileValues[$key];
        }

        return null;
    }

    private static function loadEnvFiles(): void {
        if (self::$envFileLoaded) {
            return;
        }

        $baseDir = dirname(__DIR__);
        foreach (self::ENV_FILE_ORDER as $fileName) {
            $path = $baseDir . DIRECTORY_SEPARATOR . $fileName;
            if (!is_file($path) || !is_readable($path)) {
                continue;
            }
            self::$envFileValues = array_merge(
                self::$envFileValues,
                self::parseEnvFile($path)
            );
        }

        self::$envFileLoaded = true;
    }

    private static function parseEnvFile(string $path): array {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        $parsed = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || str_starts_with($trimmed, ';')) {
                continue;
            }

            if (str_starts_with($trimmed, 'export ')) {
                $trimmed = substr($trimmed, 7);
            }

            if (!str_contains($trimmed, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $trimmed, 2);
            $key = trim($key);
            $value = trim($value);

            if ($value !== '' && (str_starts_with($value, '"') || str_starts_with($value, "'"))) {
                $quote = $value[0];
                if (str_ends_with($value, $quote)) {
                    $value = substr($value, 1, -1);
                }
            }

            $parsed[$key] = $value;
        }

        return $parsed;
    }
}
