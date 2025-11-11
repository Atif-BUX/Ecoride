<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();
if (!userHasRole('EMPLOYE') && !userHasRole('ADMIN')) {
    http_response_code(403);
    echo 'Accès refusé. Espace employé.';
    exit;
}

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/ReviewManager.php';
require_once __DIR__ . '/src/Csrf.php';
Csrf::ensureToken();

$pdo = Database::getConnection();
$rm = new ReviewManager($pdo);
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validateRequest($_POST['csrf_token'] ?? '')) {
        $message = 'Jeton CSRF invalide.';
    } else {
        $rid = (int)($_POST['review_id'] ?? 0);
        $act = $_POST['action'] ?? '';
        if ($rid > 0 && in_array($act, ['approve','reject'], true)) {
            $ok = $act === 'approve' ? $rm->publishReview($rid) : $rm->rejectReview($rid);
            $message = $ok ? 'Action effectuée.' : "Échec de l'action.";
        }
    }
}

// List pending reviews
$sql = "SELECT r.id, r.rating, r.comment, r.created_at,
               drv.first_name AS driver_first, drv.last_name AS driver_last,
               pas.first_name AS passenger_first, pas.last_name AS passenger_last,
               t.departure_city, t.arrival_city, t.departure_date
        FROM reviews r
        JOIN users drv ON drv.id = r.reviewed_user_id
        JOIN users pas ON pas.id = r.reviewer_id
        JOIN travels t ON t.id = r.travel_id
        WHERE r.status = 'pending'
        ORDER BY r.created_at DESC";
$pending = [];
try { $pending = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) { $message = 'Erreur de lecture.'; }

require_once __DIR__ . '/includes/layout/header.php';
?>
<main class="container py-4">
  <h1 class="mb-3">Modération des avis</h1>
  <?php if ($message): ?><div class="alert alert-info"><?= htmlspecialchars($message) ?></div><?php endif; ?>

  <?php if (empty($pending)): ?>
    <p>Aucun avis en attente.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Trajet</th>
            <th>Passager</th>
            <th>Conducteur</th>
            <th>Note</th>
            <th>Commentaire</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($pending as $row): ?>
          <tr>
            <td><?= (int)$row['id'] ?></td>
            <td><?= htmlspecialchars($row['departure_city']) ?> → <?= htmlspecialchars($row['arrival_city']) ?> (<?= htmlspecialchars($row['departure_date']) ?>)</td>
            <td><?= htmlspecialchars(($row['passenger_first'] ?? '').' '.($row['passenger_last'] ?? '')) ?></td>
            <td><?= htmlspecialchars(($row['driver_first'] ?? '').' '.($row['driver_last'] ?? '')) ?></td>
            <td><?= (int)$row['rating'] ?>/5</td>
            <td><?= htmlspecialchars($row['comment'] ?? '') ?></td>
            <td>
              <form method="post" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="review_id" value="<?= (int)$row['id'] ?>">
                <button name="action" value="approve" class="btn btn-success btn-sm">Publier</button>
              </form>
              <form method="post" class="d-inline ms-1">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="review_id" value="<?= (int)$row['id'] ?>">
                <button name="action" value="reject" class="btn btn-outline-danger btn-sm">Rejeter</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/layout/footer.php'; ?>

