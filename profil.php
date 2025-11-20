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
require_once __DIR__ . '/src/UserManager.php';
require_once __DIR__ . '/src/UserProfileManager.php';
require_once __DIR__ . '/src/VehicleManager.php';
require_once __DIR__ . '/src/ReviewManager.php';
require_once __DIR__ . '/src/CreditManager.php';
if (file_exists(__DIR__ . '/src/Csrf.php')) { require_once __DIR__ . '/src/Csrf.php'; Csrf::ensureToken(); }

$pdo = Database::getConnection();
$action_success = null;
$action_error = null;
$user_travels = [];
$user_reservations = [];
$user_vehicles = [];
$user_reviews = [];
$pending_reviews = [];
$profileDetails = null;
$profilePhotoSrc = null;
$profileSinceText = null;
$profilePhone = null;
$profileAddress = null;
$profilePseudo = null;
$profileBio = null;
$averageRating = null;
$creditBalance = 0;
$recentTransactions = [];
$driverEarningsTotal = 0;

if (function_exists('flash')) {
    $flashSuccess = flash('profile_success');
    if ($flashSuccess) {
        $action_success = $flashSuccess;
    }
    $flashError = flash('profile_error');
    if ($flashError) {
        $action_error = $flashError;
    }
}

if ($pdo) {
    try {
        $profileManager = new UserProfileManager($pdo);
        $creditManager = new CreditManager($pdo);
        $vehicleManager = new VehicleManager($pdo);
        $reviewManager = new ReviewManager($pdo);
        $travelManager = new TravelManager($pdo);

        $profileDetails = $profileManager->getProfile($user_id);
        if ($profileDetails) {
            $user_firstname = $profileDetails['first_name'] ?? $user_firstname;
            $user_lastname = $profileDetails['last_name'] ?? $user_lastname;
            $_SESSION['user_firstname'] = $user_firstname;
            $_SESSION['user_lastname'] = $user_lastname;
            $profilePhotoSrc = $profileDetails['photo_src'] ?? null;
            $profilePhone = $profileDetails['phone'] ?? null;
            $profileAddress = $profileDetails['address'] ?? null;
            $profilePseudo = $profileDetails['pseudo'] ?? null;
            $profileBio = $profileDetails['bio'] ?? null;
            $creditBalance = (int)($profileDetails['credit_balance'] ?? 0);

            if (!empty($profileDetails['created_at'])) {
                try {
                    $profileSinceText = (new DateTime($profileDetails['created_at']))->format('d/m/Y');
                } catch (Exception $e) {
                    $profileSinceText = null;
                }
            }
        }

        $reviewManager->publishPendingForUser($user_id);
        $averageRating = $reviewManager->averageRatingForUser($user_id);
        $user_reviews = $reviewManager->listReviewsForUser($user_id);
        $user_vehicles = $vehicleManager->getVehiclesByUser($user_id);
        $pending_reviews = $travelManager->getPendingReviewsForPassenger($user_id);
        $recentTransactions = $creditManager->listTransactions($user_id, 5);

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
            } elseif ($_POST['action'] === 'driver_confirm_reservation') {
                $tId = filter_input(INPUT_POST, 'travel_id', FILTER_VALIDATE_INT);
                $pId = filter_input(INPUT_POST, 'passenger_id', FILTER_VALIDATE_INT);
                if ($tId && $pId) {
                    if ($travelManager->confirmReservation($tId, $pId)) {
                        $action_success = 'Réservation confirmée.';
                    } else {
                        $action_error = "Échec de la confirmation. Vérifiez la disponibilité et le solde.";
                    }
                } else {
                    $action_error = 'Paramètres de confirmation invalides.';
                }
            } elseif ($_POST['action'] === 'upload_avatar') {
                if (!isset($_FILES['avatar']) || !is_uploaded_file($_FILES['avatar']['tmp_name'])) {
                    $action_error = 'Aucun fichier reçu.';
                } else {
                    $file = $_FILES['avatar'];
                    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                        $action_error = 'Erreur lors du téléversement.';
                    } elseif (($file['size'] ?? 0) > 2 * 1024 * 1024) {
                        $action_error = 'Fichier trop volumineux (max 2 Mo).';
                    } else {
                        $tmp = $file['tmp_name'];
                        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
                        $mime  = $finfo ? finfo_file($finfo, $tmp) : ($file['type'] ?? 'application/octet-stream');
                        if ($finfo) { finfo_close($finfo); }
                        $allowed = ['image/jpeg','image/png','image/webp'];
                        if (!in_array($mime, $allowed, true)) {
                            $action_error = 'Format non supporté. JPEG, PNG ou WEBP uniquement.';
                        } else {
                            $binary = file_get_contents($tmp);
                            $pm = new UserProfileManager($pdo);
                            if ($pm->updateAvatar($user_id, $binary, $mime)) {
                                $action_success = 'Photo de profil mise à jour.';
                                $profileDetails = $pm->getProfile($user_id);
                                $profilePhotoSrc = $profileDetails['photo_src'] ?? null;
                            } else {
                                $action_error = "Impossible d'enregistrer la photo.";
                            }
                        }
                    }
                }
            } elseif ($_POST['action'] === 'remove_avatar') {
                $pm = new UserProfileManager($pdo);
                if ($pm->removeAvatar($user_id)) {
                    $action_success = 'Photo de profil supprimée.';
                    $profilePhotoSrc = null;
                } else {
                    $action_error = 'Impossible de supprimer la photo.';
                }
            } elseif ($_POST['action'] === 'export_data') {
                // Export RGPD minimal (JSON)
                try {
                    $export = [];
                    $export['user'] = $profileDetails ?: [];
                    $export['vehicles'] = $user_vehicles;
                    $export['travels'] = $user_travels;
                    $export['reservations'] = $user_reservations;
                    $export['reviews'] = $user_reviews;
                    $export['credit_transactions'] = $recentTransactions; // last 5; for full export, query dedicated manager

                    $json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    if ($json !== false) {
                        header('Content-Type: application/json; charset=UTF-8');
                        header('Content-Disposition: attachment; filename="ecoride_export_user_' . (int)$user_id . '.json"');
                        header('Cache-Control: no-store');
                        echo $json;
                        exit;
                    } else {
                        $action_error = "Échec de l'export.";
                    }
                } catch (Throwable $e) {
                    $action_error = "Erreur lors de l'export.";
                }
            } elseif ($_POST['action'] === 'start_travel') {
                $tId = filter_input(INPUT_POST, 'travel_id', FILTER_VALIDATE_INT);
                if ($tId && method_exists($travelManager, 'setTravelStatus') && $travelManager->setTravelStatus($tId, $user_id, 'in_progress')) {
                    $action_success = 'Trajet démarré.';
                } else {
                    $action_error = "Impossible de démarrer ce trajet.";
                }
            } elseif ($_POST['action'] === 'complete_travel') {
                $tId = filter_input(INPUT_POST, 'travel_id', FILTER_VALIDATE_INT);
                if ($tId && method_exists($travelManager, 'setTravelStatus') && $travelManager->setTravelStatus($tId, $user_id, 'completed')) {
                    $action_success = 'Trajet complété.';
                } else {
                    $action_error = "Impossible de clore ce trajet.";
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
            } elseif ($_POST['action'] === 'delete_account') {
                try {
                    $um = new UserManager($pdo);
                    if ($um->softDeleteUser($user_id)) {
                        session_regenerate_id(true);
                        $_SESSION = [];
                        if (ini_get('session.use_cookies')) {
                            $params = session_get_cookie_params();
                            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
                        }
                        session_destroy();
                        header('Location: index.php');
                        exit;
                    } else {
                        $action_error = "La suppression du compte a échoué.";
                    }
                } catch (Throwable $e) {
                    $action_error = "Erreur lors de la suppression du compte.";
                }
            }
        }
        }

        // Chargements
        $user_travels = $travelManager->getUserTravels($user_id);
        if (method_exists($travelManager, 'getUserReservations')) {
            $user_reservations = $travelManager->getUserReservations($user_id);
        }
        $driver_pending = method_exists($travelManager, 'getPendingReservationsForDriver') ? $travelManager->getPendingReservationsForDriver($user_id) : [];

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
    $driverEarningsTotal += (int)($travel['earnings'] ?? 0);
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
    <?php $page_title='Mon Profil — EcoRide'; $page_desc="Gérez vos trajets, réservations, véhicules et paramètres de compte."; require __DIR__ . '/includes/layout/seo.php'; ?>
</head>
<body class="bg-light">
<?php require __DIR__ . '/includes/layout/navbar.php'; ?>

<main class="container py-5">
    <?php if ($action_error): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($action_error) ?></div>
    <?php elseif ($action_success): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($action_success) ?></div>
    <?php endif; ?>

    <h1 class="mb-4">Bonjour, <?= htmlspecialchars($user_firstname) ?></h1>

    <div class="card search-tool-card p-4 mb-4">
        <div class="d-flex flex-column flex-md-row align-items-center">
            <div class="mb-3 mb-md-0 me-md-4 text-center">
                <?php if (!empty($profilePhotoSrc)): ?>
                    <img src="<?= htmlspecialchars($profilePhotoSrc, ENT_QUOTES, 'UTF-8') ?>"
                         alt="Photo de profil"
                         class="rounded-circle border border-3"
                         style="width: 110px; height: 110px; object-fit: cover; border-color: var(--color-primary-light) !important;">
                <?php else: ?>
                    <div class="rounded-circle d-flex align-items-center justify-content-center border border-3"
                         style="width: 110px; height: 110px; border-color: var(--color-primary-light) !important; background-color: rgba(50, 205, 50, 0.08);">
                        <i class="fas fa-user fa-3x text-success"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="text-center text-md-start">
                <h2 class="h5 fw-bold mb-1"><?= htmlspecialchars(trim($user_firstname . ' ' . $user_lastname)) ?></h2>
                <p class="mb-2 text-muted">
                    <i class="fas fa-envelope me-2"></i><?= htmlspecialchars($user_email) ?>
                </p>
                <form method="POST" action="profil.php" enctype="multipart/form-data" class="d-inline-block mb-2 me-2">
                    <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                    <input type="hidden" name="action" value="upload_avatar">
                    <div class="input-group input-group-sm" style="max-width: 360px;">
                        <input class="form-control" type="file" name="avatar" accept="image/*" aria-label="Choisir une photo" required>
                        <button class="btn btn-success" type="submit"><i class="fas fa-upload me-1"></i>Mettre à jour</button>
                    </div>
                    <div class="form-text">JPEG, PNG ou WEBP. Max 2&nbsp;Mo.</div>
                </form>
                <form method="POST" action="profil.php" class="d-inline-block mb-2">
                    <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                    <input type="hidden" name="action" value="export_data">
                    <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="fas fa-file-export me-1"></i>Exporter mes données</button>
                </form>
                <?php if (!empty($profilePhotoSrc)): ?>
                <form method="POST" action="profil.php" class="d-inline-block ms-2 mb-2" onsubmit="return confirm('Supprimer votre photo de profil ?');">
                    <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                    <input type="hidden" name="action" value="remove_avatar">
                    <button class="btn btn-outline-danger btn-sm" type="submit"><i class="fas fa-trash-alt me-1"></i>Supprimer</button>
                </form>
                <?php endif; ?>
                <?php if (!empty($profilePseudo)): ?>
                    <p class="mb-2 text-muted"><i class="fas fa-id-card me-2"></i><?= htmlspecialchars($profilePseudo) ?></p>
                <?php endif; ?>
                <?php if (!empty($profilePhone)): ?>
                    <p class="mb-2 text-muted"><i class="fas fa-phone me-2"></i><?= htmlspecialchars($profilePhone) ?></p>
                <?php endif; ?>
                <?php if (!empty($profileAddress)): ?>
                    <p class="mb-2 text-muted"><i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($profileAddress) ?></p>
                <?php endif; ?>
                <?php if (!empty($profileSinceText)): ?>
                    <p class="mb-2 text-muted"><i class="fas fa-calendar-alt me-2"></i>Membre depuis le <?= htmlspecialchars($profileSinceText) ?></p>
                <?php endif; ?>
                <?php if ($averageRating !== null): ?>
                    <span class="badge bg-success-subtle text-success fw-semibold px-3 py-2">
                        <i class="fas fa-star me-1"></i>
                        <?= number_format($averageRating, 1, ',', ' ') ?>/5
                    </span>
                <?php endif; ?>
                <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 ms-md-2 mt-2 mt-md-0">
                    <i class="fas fa-coins me-1"></i><?= number_format($creditBalance, 0, ',', ' ') ?> crédits
                </span>                <form method="POST" action="profil.php" class="mt-3">
                    <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                    <input type="hidden" name="action" value="delete_account">
                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer définitivement votre compte ? Cette action est irréversible.');">
                        Supprimer mon compte
                    </button>
                </form>
                <?php if (!empty($profileBio)): ?>
                    <p class="mt-3 mb-0 text-muted"><?= nl2br(htmlspecialchars($profileBio)) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-4">
            <div class="card search-tool-card p-3 h-100">
                <h2 class="h5">Mes crédits</h2>
                <p class="display-6 fw-bold text-success mb-0"><?= number_format($creditBalance, 0, ',', ' ') ?></p>
                <p class="text-muted">Crédit disponible</p>
                <p class="small text-muted mb-0">Chaque réservation débite votre solde. Les annulations ou gains conducteur sont enregistrés ci-dessous.</p>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card search-tool-card p-3 h-100">
                <h2 class="h5">Derniers mouvements</h2>
                <?php if (!empty($recentTransactions)): ?>
                    <ul class="list-group list-group-flush small">
                        <?php foreach ($recentTransactions as $tx): ?>
                            <?php
                                $labels = [
                                    'reservation_debit'  => 'Réservation débitée',
                                    'reservation_refund' => 'Remboursement réservation',
                                    'reservation_credit' => 'Gain conducteur',
                                    'driver_refund'      => 'Remboursement conducteur',
                                    'manual_adjustment'  => 'Ajustement',
                                ];
                                $type = (string)($tx['type'] ?? '');
                                $pretty = $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
                                $amt = (int)($tx['amount'] ?? 0);
                            ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($pretty) ?></div>
                                    <div class="text-muted">
                                        <?= htmlspecialchars($tx['note'] ?? '') ?>
                                        <?php if (!empty($tx['created_at'])): ?>
                                            <span class="ms-1 small">· <?= htmlspecialchars(date('d/m/Y H:i', strtotime($tx['created_at']))) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="<?= ($amt >= 0) ? 'text-success' : 'text-danger' ?>">
                                    <?= $amt >= 0 ? '+' : '' ?><?= $amt ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">Aucun mouvement pour l'instant.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($driverEarningsTotal > 0): ?>
            <div class="col-12 col-lg-4">
                <div class="card search-tool-card p-3 h-100">
                    <h2 class="h5">Gains conducteur</h2>
                    <p class="display-6 fw-bold text-primary mb-0"><?= number_format($driverEarningsTotal, 0, ',', ' ') ?></p>
                    <p class="text-muted">Crédits cumulés sur vos trajets</p>
                    <p class="small text-muted mb-0">Ces gains incluent les réservations confirmées moins les frais de plateforme.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card search-tool-card p-3 h-100">
                <h2 class="h5">Mes Réservations (Passager)</h2>
                <?php if (!empty($user_reservations)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($user_reservations as $res): ?>
                            <?php
                                $status = $res['status'] ?? 'pending';
                                $statusMap = [
                                    'pending' => ['label' => 'En attente', 'class' => 'bg-warning text-dark'],
                                    'confirmed' => ['label' => 'Confirmée', 'class' => 'bg-success text-white'],
                                    'cancelled' => ['label' => 'Annulée', 'class' => 'bg-secondary text-white'],
                                ];
                                $statusInfo = $statusMap[$status] ?? $statusMap['pending'];
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="me-3">
                                    <div class="fw-bold"><?= htmlspecialchars($res['departure_city']) ?> → <?= htmlspecialchars($res['arrival_city']) ?></div>
                                    <div class="text-muted small">
                                        Le <?= htmlspecialchars(date('d/m/Y', strtotime($res['departure_date']))) ?> à <?= htmlspecialchars(date('H:i', strtotime($res['departure_time']))) ?> — <?= (int)$res['seats_booked'] ?> place(s)
                                    </div>
                                    <div class="text-muted small">Conducteur: <?= htmlspecialchars($res['first_name'] . ' ' . $res['last_name']) ?></div>
                                    <div class="text-muted small mt-1">
                                        <span class="badge <?= $statusInfo['class'] ?>">
                                            <?= $statusInfo['label'] ?>
                                        </span>
                                        <?php if (!empty($res['credit_spent'])): ?>
                                            <span class="ms-2"><i class="fas fa-coins me-1"></i><?= (int)$res['credit_spent'] ?> crédits</span>
                                        <?php endif; ?>
                                    </div>
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
                <?php if (!empty($driver_pending)): ?>
                    <div class="alert alert-warning" role="alert">
                        <strong>Demandes en attente</strong>
                    </div>
                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach ($driver_pending as $pending): ?>
                            <?php
                                $pPhoto = null;
                                if (!empty($pending['passenger_photo_bin'])) {
                                    $pm = !empty($pending['passenger_photo_mime']) ? $pending['passenger_photo_mime'] : 'image/jpeg';
                                    $pPhoto = 'data:' . $pm . ';base64,' . base64_encode($pending['passenger_photo_bin']);
                                } elseif (!empty($pending['passenger_photo_path'])) {
                                    $pPhoto = $pending['passenger_photo_path'];
                                }
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="me-3 d-flex">
                                    <?php if ($pPhoto): ?>
                                        <img src="<?= htmlspecialchars($pPhoto) ?>" alt="Photo passager" class="rounded-circle me-2" style="width: 36px; height: 36px; object-fit: cover; border: 2px solid var(--color-primary-light);">
                                    <?php else: ?>
                                        <i class="fas fa-user-circle fa-lg text-success me-2"></i>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold">
                                            <?= htmlspecialchars($pending['passenger_first_name'] . ' ' . $pending['passenger_last_name']) ?>
                                            (<?= htmlspecialchars($pending['passenger_email']) ?>)
                                        </div>
                                        <div class="text-muted small">
                                            Trajet <?= htmlspecialchars($pending['departure_city']) ?> → <?= htmlspecialchars($pending['arrival_city']) ?> •
                                            Le <?= htmlspecialchars(date('d/m/Y', strtotime($pending['departure_date']))) ?> à <?= htmlspecialchars(date('H:i', strtotime($pending['departure_time']))) ?> •
                                            Places: <?= (int)$pending['seats_booked'] ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <form method="POST" action="profil.php" class="mb-0" onsubmit="return confirm('Confirmer cette réservation ?');">
                                        <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                                        <input type="hidden" name="action" value="driver_confirm_reservation">
                                        <input type="hidden" name="travel_id" value="<?= (int)$pending['travel_id'] ?>">
                                        <input type="hidden" name="passenger_id" value="<?= (int)$pending['passenger_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check me-1"></i> Confirmer</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if (!empty($upcoming_travels)): ?>
                    <h6 class="text-success">À venir (<?= count($upcoming_travels) ?>)</h6>
                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach ($upcoming_travels as $t): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="me-3">
                                    <div class="fw-bold"><?= htmlspecialchars($t['departure_city']) ?> → <?= htmlspecialchars($t['arrival_city']) ?></div>
                                    <div class="text-muted small">Le <?= htmlspecialchars(date('d/m/Y', strtotime($t['departure_date']))) ?> à <?= htmlspecialchars(date('H:i', strtotime($t['departure_time']))) ?></div>
                                    <div class="text-muted small">Places: <?= (int)$t['available_seats'] ?>/<?= (int)$t['total_seats'] ?></div>
                                    <?php if (!empty($t['earnings'])): ?>
                                        <div class="text-muted small"><i class="fas fa-coins me-1"></i><?= (int)$t['earnings'] ?> crédits gagnés</div>
                                    <?php endif; ?>
                                    <div class="mt-1"><span class="badge bg-secondary">Statut: <?= htmlspecialchars($t['status'] ?? 'planned') ?></span></div>
                                </div>
                                <div class="text-end">
                                    <a class="btn btn-sm btn-outline-primary mb-2" href="modifier_trajet.php?id=<?= (int)$t['id'] ?>">Modifier</a>
                                    <?php $status = $t['status'] ?? 'planned'; ?>
                                    <?php if ($status === 'planned'): ?>
                                        <form method="POST" action="profil.php" class="d-inline">
                                            <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                                            <input type="hidden" name="action" value="start_travel">
                                            <input type="hidden" name="travel_id" value="<?= (int)$t['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Démarrer ce trajet ?');">Démarrer</button>
                                        </form>
                                    <?php elseif ($status === 'in_progress'): ?>
                                        <form method="POST" action="profil.php" class="d-inline">
                                            <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                                            <input type="hidden" name="action" value="complete_travel">
                                            <input type="hidden" name="travel_id" value="<?= (int)$t['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Clore ce trajet ?');">Arrivée à destination</button>
                                        </form>
                                    <?php endif; ?>
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
                                <?php if (!empty($t['earnings'])): ?>
                                    <div class="text-muted small"><i class="fas fa-coins me-1"></i><?= (int)$t['earnings'] ?> crédits gagnés</div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">Aucun trajet passé.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-4">
        <div class="col-12 col-lg-6">
            <div class="card search-tool-card p-3 h-100">
                <h2 class="h5">Avis à laisser</h2>
                <?php if (!empty($pending_reviews)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($pending_reviews as $pending): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="me-3">
                                    <div class="fw-bold">
                                        <?= htmlspecialchars($pending['departure_city']) ?> →
                                        <?= htmlspecialchars($pending['arrival_city']) ?>
                                    </div>
                                    <div class="text-muted small">
                                        Trajet du <?= htmlspecialchars(date('d/m/Y', strtotime($pending['departure_date']))) ?>
                                        à <?= htmlspecialchars(date('H:i', strtotime($pending['departure_time']))) ?>
                                    </div>
                                    <div class="text-muted small">
                                        Conducteur : <?= htmlspecialchars($pending['driver_first_name'] . ' ' . $pending['driver_last_name']) ?>
                                    </div>
                                </div>
                                <div>
                                    <a class="btn btn-sm btn-success"
                                       href="laisser_avis.php?travel_id=<?= (int)$pending['travel_id'] ?>">
                                        Laisser un avis
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">Vous n'avez aucun avis en attente.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<div class="container pb-5">
    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card search-tool-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h5 mb-0">Mes Véhicules</h2>
                    <a href="vehicule_form.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-plus me-1"></i>Ajouter
                    </a>
                </div>
                <?php if (!empty($user_vehicles)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($user_vehicles as $vehicle): ?>
                            <li class="list-group-item">
                                <div class="fw-bold">
                                    <?= htmlspecialchars($vehicle['brand_label'] ?? 'Marque inconnue') ?>
                                    <?= htmlspecialchars($vehicle['model']) ?>
                                </div>
                                <div class="text-muted small">
                                    Immatriculation : <?= htmlspecialchars($vehicle['license_plate']) ?>
                                    <?php if (!empty($vehicle['energy'])): ?>
                                        • <?= htmlspecialchars($vehicle['energy']) ?>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($vehicle['color']) || !empty($vehicle['first_registration_date'])): ?>
                                    <div class="text-muted small">
                                        <?php if (!empty($vehicle['color'])): ?>
                                            Couleur : <?= htmlspecialchars($vehicle['color']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($vehicle['first_registration_date'])): ?>
                                            <?= !empty($vehicle['color']) ? ' • ' : '' ?>Première immatriculation :
                                            <?= htmlspecialchars(date('d/m/Y', strtotime($vehicle['first_registration_date']))) ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="mt-2">
                                    <a href="vehicule_form.php?id=<?= (int)$vehicle['id'] ?>" class="btn btn-sm btn-outline-secondary me-2">
                                        Modifier
                                    </a>
                                    <a href="vehicule_supprimer.php?id=<?= (int)$vehicle['id'] ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Supprimer ce véhicule ?');">
                                        Supprimer
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">Vous n'avez encore enregistré aucun véhicule.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card search-tool-card p-3 h-100">
                <h2 class="h5">Avis reçus</h2>
                <?php if (!empty($user_reviews)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($user_reviews as $review): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="fw-bold">
                                            Trajet <?= htmlspecialchars($review['departure_city']) ?> → <?= htmlspecialchars($review['arrival_city']) ?>
                                        </div>
                                        <div class="text-muted small">
                                            Le <?= htmlspecialchars(date('d/m/Y', strtotime($review['departure_date']))) ?>
                                            • Par <?= htmlspecialchars($review['reviewer_first_name'] . ' ' . $review['reviewer_last_name']) ?>
                                        </div>
                                    </div>
                                    <span class="badge bg-warning text-dark align-self-start">
                                        <i class="fas fa-star me-1"></i><?= (int)$review['rating'] ?>/5
                                    </span>
                                </div>
                                <?php if (!empty($review['comment'])): ?>
                                    <p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">Vous n'avez pas encore reçu d'avis.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/layout/footer.php'; ?>



