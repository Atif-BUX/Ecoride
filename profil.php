<?php
// Fichier: profil.php (restauré et enrichi)
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$user_id = (int)($_SESSION['user_id'] ?? 0);
$user_firstname = $_SESSION['user_firstname'] ?? 'Prénom';
$user_lastname = $_SESSION['user_lastname'] ?? 'Nom';
$user_email = $_SESSION['user_email'] ?? 'email@example.com';

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/TravelManager.php';
if (file_exists(__DIR__ . '/src/Csrf.php')) { require_once __DIR__ . '/src/Csrf.php'; Csrf::ensureToken(); }

$pdo = Database::getConnection();
$action_success = null;
$action_error = null;
$user_travels = [];
$user_reservations = [];

if ($pdo) {
    try {
        $travelManager = new TravelManager($pdo);

        // Actions POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            if (!class_exists('Csrf') || !Csrf::validateRequest($_POST['csrf_token'] ?? '')) {
                $action_error = 'Jeton CSRF invalide. Veuillez réessayer.';
            } else {
            if ($_POST['action'] === 'delete_travel') {
                $travel_to_delete = filter_input(INPUT_POST, 'travel_id', FILTER_VALIDATE_INT);
                if ($travel_to_delete) {
                    if ($travelManager->deleteTravel($travel_to_delete, $user_id)) {
                        $action_success = 'Le trajet a été supprimé avec succès.';
                    } else {
                        $action_error = "Erreur: suppression impossible ou non autorisée.";
                    }
                } else {
                    $action_error = 'Erreur: ID de trajet invalide.';
                }
            } elseif ($_POST['action'] === 'cancel_booking') {
                $travel_to_cancel = filter_input(INPUT_POST, 'travel_id', FILTER_VALIDATE_INT);
                if ($travel_to_cancel) {
                    if ($travelManager->cancelReservation($travel_to_cancel, $user_id)) {
                        $action_success = 'Réservation annulée.';
                    } else {
                        $action_error = "Erreur: annulation impossible.";
                    }
                } else {
                    $action_error = 'Erreur: ID de trajet invalide.';
                }
            }
        }
        }

        // Chargements
        $user_travels = $travelManager->getUserTravels($user_id);
        if (method_exists($travelManager, 'getUserReservations')) {
            $user_reservations = $travelManager->getUserReservations($user_id);
        }

    } catch (Exception $e) {
        error_log('Profil error: ' . $e->getMessage());
        $action_error = "Erreur lors du chargement de vos données.";
    }
} else {
    $action_error = "ERREUR FATALE: Connexion BD échouée.";
}

// Séparer à venir / passés
$upcoming_travels = [];
$past_travels = [];
$current_datetime = new DateTime();
foreach ($user_travels as $travel) {
    $raw = trim(($travel['departure_date'] ?? '') . ' ' . ($travel['departure_time'] ?? ''));
    try { $dt = new DateTime($raw); } catch (Exception $e) { $dt = null; }
    if ($dt) {
        if ($dt > $current_datetime) { $upcoming_travels[] = $travel; } else { $past_travels[] = $travel; }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
<header class="main-header text-white py-3 sticky-top">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <svg id="logo-animation" width="40" height="40" viewBox="0 0 100 100" class="me-2">
                    <circle class="wheel-circle" cx="50" cy="50" r="45" stroke="#32CD32" stroke-width="5" fill="none" />
                    <g class="wheel-spokes" stroke="#32CD32" stroke-width="4" stroke-linecap="round">
                        <line x1="50" y1="5" x2="50" y2="95" />
                        <line x1="15" y1="27" x2="85" y2="73" />
                        <line x1="85" y1="27" x2="15" y2="73" />
                    </g>
                    <text class="logo-text" x="50" y="60" text-anchor="middle" font-family="Montserrat, sans-serif" font-size="45" font-weight="bold" fill="#FFFFFF">ER</text>
                </svg>
                <span class="text-white fw-bold">EcoRide</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="covoiturages.php">Covoiturages</a></li>
                    <li class="nav-item"><a class="nav-link" href="proposer_trajet.php">Proposer</a></li>
                    <li class="nav-item"><a class="nav-link active" href="profil.php">Profil</a></li>
                    <li class="nav-item"><a class="nav-link" href="deconnexion.php">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main class="container py-5">
    <?php if ($action_error): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($action_error) ?></div>
    <?php elseif ($action_success): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($action_success) ?></div>
    <?php endif; ?>

    <h1 class="mb-4">Bonjour, <?= htmlspecialchars($user_firstname) ?></h1>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card search-tool-card p-3 h-100">
                <h2 class="h5">Mes Réservations (Passager)</h2>
                <?php if (!empty($user_reservations)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($user_reservations as $res): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="me-3">
                                    <div class="fw-bold"><?= htmlspecialchars($res['departure_city']) ?> → <?= htmlspecialchars($res['arrival_city']) ?></div>
                                    <div class="text-muted small">
                                        Le <?= htmlspecialchars(date('d/m/Y', strtotime($res['departure_date']))) ?> à <?= htmlspecialchars(date('H:i', strtotime($res['departure_time']))) ?> — <?= (int)$res['seats_booked'] ?> place(s)
                                    </div>
                                    <div class="text-muted small">Conducteur: <?= htmlspecialchars($res['first_name'] . ' ' . $res['last_name']) ?></div>
                                </div>
                                <div class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary mb-2" href="detail_trajet.php?id=<?= (int)$res['travel_id'] ?>">Voir</a>
                                    <form method="POST" action="profil.php" onsubmit="return confirm('Annuler cette réservation ?');">
                                        <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                                        <input type="hidden" name="action" value="cancel_booking">
                                        <input type="hidden" name="travel_id" value="<?= (int)$res['travel_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Annuler</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">Aucune réservation active.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card search-tool-card p-3 h-100">
                <h2 class="h5">Mes Trajets (Conducteur)</h2>
                <?php if (!empty($upcoming_travels)): ?>
                    <h6 class="text-success">À venir (<?= count($upcoming_travels) ?>)</h6>
                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach ($upcoming_travels as $t): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="me-3">
                                    <div class="fw-bold"><?= htmlspecialchars($t['departure_city']) ?> → <?= htmlspecialchars($t['arrival_city']) ?></div>
                                    <div class="text-muted small">Le <?= htmlspecialchars(date('d/m/Y', strtotime($t['departure_date']))) ?> à <?= htmlspecialchars(date('H:i', strtotime($t['departure_time']))) ?></div>
                                    <div class="text-muted small">Places: <?= (int)$t['available_seats'] ?>/<?= (int)$t['total_seats'] ?></div>
                                </div>
                                <div class="text-end">
                                    <a class="btn btn-sm btn-outline-primary mb-2" href="modifier_trajet.php?id=<?= (int)$t['id'] ?>">Modifier</a>
                                    <form method="POST" action="profil.php" onsubmit="return confirm('Supprimer ce trajet ?');">
                                        <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                                        <input type="hidden" name="action" value="delete_travel">
                                        <input type="hidden" name="travel_id" value="<?= (int)$t['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <h6 class="text-secondary">Historique (<?= count($past_travels) ?>)</h6>
                <?php if (!empty($past_travels)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($past_travels as $t): ?>
                            <li class="list-group-item">
                                <div class="fw-bold"><?= htmlspecialchars($t['departure_city']) ?> → <?= htmlspecialchars($t['arrival_city']) ?></div>
                                <div class="text-muted small">Le <?= htmlspecialchars(date('d/m/Y', strtotime($t['departure_date']))) ?> à <?= htmlspecialchars(date('H:i', strtotime($t['departure_time']))) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">Aucun trajet passé.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/layout/footer.php'; ?>



