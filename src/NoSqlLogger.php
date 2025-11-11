<?php
// Attempts to log events into MongoDB if available, else falls back to a JSON lines file under logs/.
class NoSqlLogger
{
    public static function log(string $collection, array $doc): void
    {
        $doc['ts'] = $doc['ts'] ?? date('c');
        // Try MongoDB extension first
        if (class_exists('MongoDB\Driver\Manager')) {
            try {
                $manager = new MongoDB\Driver\Manager(getenv('MONGO_URI') ?: 'mongodb://127.0.0.1:27017');
                $bulk = new MongoDB\Driver\BulkWrite();
                $bulk->insert($doc);
                $manager->executeBulkWrite('ecoride.' . preg_replace('/[^a-zA-Z0-9_]/', '_', $collection), $bulk);
                return;
            } catch (\Throwable $e) {
                // fallthrough to file log
            }
        }
        // Fallback to local logs
        $dir = __DIR__ . '/../logs';
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        $line = json_encode(['collection'=>$collection,'doc'=>$doc], JSON_UNESCAPED_UNICODE) . "\n";
        @file_put_contents($dir . '/nosql.log', $line, FILE_APPEND);
    }
}

