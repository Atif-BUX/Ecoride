<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();
if (!userHasRole('ADMIN')) { http_response_code(403); echo 'Accès refusé (ADMIN).'; exit; }

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Csrf.php';
Csrf::ensureToken();
$pdo = Database::getConnection();
$msg = null;

// Suspend/unsuspend toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_user'])) {
    if (!Csrf::validateRequest($_POST['csrf_token'] ?? '')) {
        $msg = 'Jeton CSRF invalide.';
    } else {
        $uid = (int)($_POST['user_id'] ?? 0);
        $val = (int)($_POST['new_active'] ?? 1);
        try {
            $stmt = $pdo->prepare('UPDATE users SET is_active = :a WHERE id = :id');
            $stmt->execute([':a' => $val, ':id' => $uid]);
            $msg = 'Statut utilisateur mis à jour.';
        } catch (Throwable $e) { $msg = "Échec de mise à jour."; }
    }
}

// Data for charts
$tripsPerDay = [];
$platformPerDay = [];
try {
    $tripsPerDay = $pdo->query("SELECT departure_date AS d, COUNT(*) AS c FROM travels GROUP BY departure_date ORDER BY departure_date")->fetchAll(PDO::FETCH_ASSOC);
    $platformPerDay = $pdo->query("SELECT DATE(COALESCE(confirmed_at, booking_date)) AS d, SUM(GREATEST(0, credit_spent - driver_credit)) AS total FROM reservations WHERE status='confirmed' GROUP BY DATE(COALESCE(confirmed_at, booking_date)) ORDER BY d")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// Users list
$users = [];
try { $users = $pdo->query("SELECT id, first_name, last_name, email, is_active FROM users ORDER BY id DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) {}

require_once __DIR__ . '/includes/layout/header.php';
?>
<main class="container py-4">
  <h1 class="mb-3">Tableau de bord Admin</h1>
  <?php if ($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <div class="row g-4">
    <div class="col-md-6">
      <div class="card p-3">
        <h5>Nombre de covoiturages par jour</h5>
        <canvas id="chartTrips"></canvas>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card p-3">
        <h5>Crédits gagnés par la plateforme (par jour)</h5>
        <canvas id="chartPlatform"></canvas>
      </div>
    </div>
  </div>

  <h3 class="mt-4">Utilisateurs</h3>
  <div class="table-responsive">
    <table class="table table-sm align-middle">
      <thead>
        <tr><th>#</th><th>Nom</th><th>Email</th><th>Actif</th><th>Action</th></tr>
      </thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= (int)$u['id'] ?></td>
          <td><?= htmlspecialchars(($u['first_name'] ?? '').' '.($u['last_name'] ?? '')) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= ((int)$u['is_active'] === 1 ? 'Oui' : 'Non') ?></td>
          <td>
            <form method="post" class="d-inline">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
              <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
              <input type="hidden" name="new_active" value="<?= ((int)$u['is_active'] === 1 ? 0 : 1) ?>">
              <button name="toggle_user" value="1" class="btn btn-sm <?= ((int)$u['is_active']===1?'btn-outline-danger':'btn-success') ?>">
                <?= ((int)$u['is_active']===1?'Suspendre':'Activer') ?>
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const trips = <?= json_encode($tripsPerDay, JSON_UNESCAPED_UNICODE) ?>;
  const platf = <?= json_encode($platformPerDay, JSON_UNESCAPED_UNICODE) ?>;
  const tLabels = trips.map(x => x.d);
  const tData = trips.map(x => Number(x.c));
  const pLabels = platf.map(x => x.d);
  const pData = platf.map(x => Number(x.total || 0));
  new Chart(document.getElementById('chartTrips'), { type:'line', data:{ labels:tLabels, datasets:[{ label:'Covoiturages', data:tData, borderColor:'#32CD32' }] }, options:{ responsive:true } });
  new Chart(document.getElementById('chartPlatform'), { type:'line', data:{ labels:pLabels, datasets:[{ label:'Crédits', data:pData, borderColor:'#1e8449' }] }, options:{ responsive:true } });
</script>

<?php require __DIR__ . '/includes/layout/footer.php'; ?>

