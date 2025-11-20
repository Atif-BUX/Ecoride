<?php
// Fichier: admin_needs.php — Liste des besoins exprimés (logger NoSQL)
session_start();

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/RoleManager.php';
if (file_exists(__DIR__ . '/src/Csrf.php')) { require_once __DIR__ . '/src/Csrf.php'; Csrf::ensureToken(); }

$pdo = Database::getConnection();
$userId = (int)($_SESSION['user_id'] ?? 0);
$isAdmin = false;

try {
    $roleMgr = new RoleManager($pdo);
    $isAdmin = $roleMgr->userHasRole($userId, 'ADMIN');
} catch (Throwable $e) {
    $isAdmin = false;
}

if (!$isAdmin) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Accès refusé</title>' .
         '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>' .
         '<body class="bg-light"><div class="container py-5"><div class="alert alert-danger">Accès refusé. Cette page est réservée aux administrateurs.</div></div></body></html>';
    exit;
}

// Lecture du log fallback (logs/nosql.log)
$logPath = __DIR__ . '/logs/nosql.log';
$limit = max(1, min(1000, (int)($_GET['limit'] ?? 100)));
$entries = [];
$source = 'file';

// 1) Try MongoDB first (if driver is present)
if (class_exists('MongoDB\\Driver\\Manager')) {
    try {
        $manager = new MongoDB\Driver\Manager(getenv('MONGO_URI') ?: 'mongodb://127.0.0.1:27017');
        $filter = [];
        $options = ['sort' => ['ts' => -1], 'limit' => $limit];
        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor = $manager->executeQuery('ecoride.needs', $query);
        foreach ($cursor as $doc) {
            // $doc is stdClass; access properties defensively
            $entries[] = [
                'ts'        => isset($doc->ts) ? (string)$doc->ts : '',
                'user_id'   => isset($doc->user_id) ? (int)$doc->user_id : 0,
                'departure' => isset($doc->departure) ? (string)$doc->departure : '',
                'arrival'   => isset($doc->arrival) ? (string)$doc->arrival : '',
                'date'      => isset($doc->date) ? (string)$doc->date : '',
                'note'      => isset($doc->note) ? (string)$doc->note : '',
            ];
        }
        $source = 'mongo';
    } catch (Throwable $e) {
        // fall back to file
        $entries = [];
        $source = 'file';
    }
}

// 2) Fallback to file log when Mongo not used or failed
if ($source === 'file') {
    if (is_file($logPath) && is_readable($logPath)) {
        $lines = @file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        // Walk backward, pick only collection 'needs'
        for ($i = count($lines) - 1; $i >= 0 && count($entries) < $limit; $i--) {
            $row = json_decode($lines[$i], true);
            if (json_last_error() === JSON_ERROR_NONE && ($row['collection'] ?? '') === 'needs' && !empty($row['doc'])) {
                $doc = (array)$row['doc'];
                $entries[] = [
                    'ts'        => (string)($doc['ts'] ?? ''),
                    'user_id'   => (int)($doc['user_id'] ?? 0),
                    'departure' => (string)($doc['departure'] ?? ''),
                    'arrival'   => (string)($doc['arrival'] ?? ''),
                    'date'      => (string)($doc['date'] ?? ''),
                    'note'      => (string)($doc['note'] ?? ''),
                ];
            }
        }
    }
}

?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Besoins exprimés — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style> body { background: #f8f9fa; } </style>
    </head>
<body>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Besoins exprimés (dernier <?= htmlspecialchars((string)$limit) ?>)</h1>
        <div class="d-flex align-items-center gap-2">
            <span class="badge <?= $source === 'mongo' ? 'bg-success' : 'bg-secondary' ?>">NoSQL : <?= $source === 'mongo' ? 'MongoDB' : 'Fichier' ?></span>
            <a class="btn btn-outline-secondary btn-sm" href="admin_params.php">Paramètres</a>
        </div>
    </div>
    <p class="text-muted mb-3">Source détaillée : <?= $source === 'mongo' ? 'MongoDB (ecoride.needs)' : 'logs/nosql.log' ?></p>

    <form class="row g-2 mb-3" method="get">
        <div class="col-auto">
            <label for="limit" class="col-form-label">Limite</label>
        </div>
        <div class="col-auto">
            <input type="number" class="form-control" id="limit" name="limit" value="<?= htmlspecialchars((string)$limit) ?>" min="1" max="1000">
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" type="submit">Appliquer</button>
        </div>
    </form>

    <?php if (empty($entries)): ?>
        <div class="alert alert-info">Aucune demande enregistrée (ou fichier de log indisponible).</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                <tr>
                    <th>Date/Heure</th>
                    <th>Utilisateur</th>
                    <th>Départ</th>
                    <th>Arrivée</th>
                    <th>Date souhaitée</th>
                    <th>Message</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['ts']) ?></td>
                        <td><?= (int)$e['user_id'] ?: '-' ?></td>
                        <td><?= htmlspecialchars($e['departure']) ?></td>
                        <td><?= htmlspecialchars($e['arrival']) ?></td>
                        <td><?= htmlspecialchars($e['date']) ?></td>
                        <td><?= nl2br(htmlspecialchars($e['note'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
