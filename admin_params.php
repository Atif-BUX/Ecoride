<?php
// Simple admin panel to view/set system parameters
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/SystemParameterManager.php';
require_once __DIR__ . '/src/RoleManager.php';
if (file_exists(__DIR__ . '/src/Csrf.php')) { require_once __DIR__ . '/src/Csrf.php'; Csrf::ensureToken(); }

$pdo = Database::getConnection();
$userId = (int)($_SESSION['user_id'] ?? 0);
$isAdmin = false;

try {
    $roleMgr = new RoleManager($pdo);
    // Check directly in DB to avoid stale session state
    $isAdmin = $roleMgr->userHasRole($userId, 'ADMIN');
} catch (Throwable $e) {
    $isAdmin = false;
}

if (!$isAdmin) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Accès refusé</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><div class="container py-5"><div class="alert alert-danger">Accès refusé. Cette page est réservée aux administrateurs.</div></div></body></html>';
    exit;
}

$paramMgr = new SystemParameterManager($pdo);
$message = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_auto_confirm') {
    if (!class_exists('Csrf') || !Csrf::validateRequest($_POST['csrf_token'] ?? '')) {
        $error = 'Jeton CSRF invalide. Veuillez réessayer.';
    } else {
        $value = ($_POST['booking_auto_confirm'] ?? '0') === '1' ? '1' : '0';
        if ($paramMgr->set('booking_auto_confirm', $value, 'default')) {
            $message = 'Paramètre mis à jour.';
        } else {
            $error = "Échec de la mise à jour du paramètre.";
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_demo_passwords') {
    if (!class_exists('Csrf') || !Csrf::validateRequest($_POST['csrf_token'] ?? '')) {
        $error = 'Jeton CSRF invalide. Veuillez réessayer.';
    } else {
        try {
            $pdo->beginTransaction();
            $hash = password_hash('password321', PASSWORD_DEFAULT);

            // Ensure driver exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => 'jean.dupont@test.fr']);
            $driverId = $stmt->fetchColumn();
            if ($driverId === false) {
                $ins = $pdo->prepare('INSERT INTO users (email, password, first_name, last_name, credit_balance, is_driver, is_passenger, is_active) VALUES (:e,:p, :fn,:ln, 50, 1, 0, 1)');
                $ins->execute([':e' => 'jean.dupont@test.fr', ':p' => $hash, ':fn' => 'Jean', ':ln' => 'Dupont']);
            } else {
                $upd = $pdo->prepare('UPDATE users SET password = :p, is_driver = 1 WHERE id = :id');
                $upd->execute([':p' => $hash, ':id' => (int)$driverId]);
            }

            // Ensure passenger exists
            $stmt->execute([':email' => 'john.wick@gmail.com']);
            $passengerId = $stmt->fetchColumn();
            if ($passengerId === false) {
                $ins = $pdo->prepare('INSERT INTO users (email, password, first_name, last_name, credit_balance, is_driver, is_passenger, is_active) VALUES (:e,:p, :fn,:ln, 50, 0, 1, 1)');
                $ins->execute([':e' => 'john.wick@gmail.com', ':p' => $hash, ':fn' => 'John', ':ln' => 'Wick']);
            } else {
                $upd = $pdo->prepare('UPDATE users SET password = :p, is_driver = 0, is_passenger = 1 WHERE id = :id');
                $upd->execute([':p' => $hash, ':id' => (int)$passengerId]);
            }

            $pdo->commit();
            $message = "Identifiants démo réinitialisés (password321).";
        } catch (Throwable $e) {
            try { $pdo->rollBack(); } catch (Throwable $ignored) {}
            $error = "Erreur lors de la réinitialisation: " . $e->getMessage();
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_reset_ttl') {
    if (!class_exists('Csrf') || !Csrf::validateRequest($_POST['csrf_token'] ?? '')) {
        $error = 'Jeton CSRF invalide. Veuillez réessayer.';
    } else {
        $ttl = (int)($_POST['password_reset_ttl_minutes'] ?? 60);
        $ttl = max(5, min(240, $ttl));
        if ($paramMgr->set('password_reset_ttl_minutes', (string)$ttl, 'default')) {
            $message = 'Durée de validité mise à jour (' . $ttl . ' min).';
        } else {
            $error = "Échec de la mise à jour de la durée de validité.";
        }
    }
}

$params = $paramMgr->listAll('default');
$auto = (string)($params['booking_auto_confirm'] ?? '0');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres Système - EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Paramètres Système</h1>
        <div class="d-flex align-items-center gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="index.php">← Retour au site</a>
            <a class="btn btn-sm btn-outline-primary" href="admin_needs.php">Voir les besoins exprimés</a>
            <?php $nosqlActive = class_exists('MongoDB\\Driver\\Manager'); ?>
            <span class="badge <?= $nosqlActive ? 'bg-success' : 'bg-secondary' ?>">
                NoSQL: <?= $nosqlActive ? 'MongoDB actif' : 'Fallback fichier' ?>
            </span>
        </div>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">Réservations — Confirmation automatique</div>
        <div class="card-body">
            <form method="POST" action="admin_params.php">
                <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                <input type="hidden" name="action" value="set_auto_confirm">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="booking_auto_confirm" id="autoOn" value="1" <?= $auto === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="autoOn">Activer (confirmer immédiatement)</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="booking_auto_confirm" id="autoOff" value="0" <?= $auto !== '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="autoOff">Désactiver (réservations en attente)</label>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Enregistrer</button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Comptes démo</div>
        <div class="card-body">
            <p class="text-muted">Définit les mots de passe pour <code>jean.dupont@test.fr</code> (conducteur) et <code>john.wick@gmail.com</code> (passager) sur <strong>password321</strong>. Crée les comptes s’ils n’existent pas.</p>
            <form method="POST" action="admin_params.php" onsubmit="return confirm('Réinitialiser les mots de passe démo ?');">
                <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                <input type="hidden" name="action" value="reset_demo_passwords">
                <button type="submit" class="btn btn-outline-secondary">Réinitialiser les mots de passe démo</button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Sécurité — Réinitialisation mot de passe</div>
        <div class="card-body">
            <form method="POST" action="admin_params.php" class="row g-2 align-items-end">
                <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                <input type="hidden" name="action" value="set_reset_ttl">
                <?php 
                    $spmVal = (string)($params['password_reset_ttl_minutes'] ?? '60');
                    $spmVal = $spmVal === '' ? '60' : $spmVal;
                ?>
                <div class="col-auto">
                    <label for="ttl" class="form-label">Durée de validité du lien (minutes)</label>
                    <input type="number" id="ttl" name="password_reset_ttl_minutes" class="form-control" min="5" max="240" value="<?= htmlspecialchars($spmVal) ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
                <div class="col-12"><small class="text-muted">Valeur recommandée : 30–60 minutes.</small></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Paramètres (configuration "default")</div>
        <div class="card-body">
            <?php if (!empty($params)): ?>
                <table class="table table-sm">
                    <thead><tr><th>Propriété</th><th>Valeur</th></tr></thead>
                    <tbody>
                    <?php foreach ($params as $k => $v): ?>
                        <tr><td><?= htmlspecialchars($k) ?></td><td><?= htmlspecialchars((string)$v) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted mb-0">Aucun paramètre.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
