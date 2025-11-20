<?php
// Fichier: detail_trajet.php - 02/11/2025 - 18:15

// 1. Démarrer la session
session_start();

// 2. Définition des variables d'état
$is_logged_in = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
$travel_details = null; // Détails du trajet à afficher
$error_message = null;  // Message d'erreur initialisé à null
$success_message = null; // Message de succès pour les réservations
$travel_id = null;
$departure_date_formatted = null;
$departure_time_formatted = null;
$credit_cost_per_seat = null;

// 3. Inclusion des classes et connexion à la DB
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/TravelManager.php';
if (file_exists(__DIR__ . '/src/Csrf.php')) { require_once __DIR__ . '/src/Csrf.php'; Csrf::ensureToken(); }

$pdo = Database::getConnection();
$travelManager = $pdo ? new TravelManager($pdo) : null;
$creditManager = $pdo ? new CreditManager($pdo) : null;
$user_id = $is_logged_in ? (int)($_SESSION['user_id'] ?? 0) : 0;
$user_credit_balance = ($is_logged_in && $creditManager) ? $creditManager->getBalance($user_id) : null;

// 4. Récupération et validation de l'ID du trajet
$travel_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$pdo) {
    $error_message = "Erreur de connexion à la base de données.";
} elseif (!$travel_id) {
    $error_message = "ID de trajet non spécifié ou invalide.";
} elseif (!$travelManager) {
    $error_message = "Erreur lors de l'initialisation du gestionnaire de trajets.";
} else {
    // 5. Charger les détails du trajet (y compris les contacts si besoin)
    $travel_details = $travelManager->getTravelContactDetails($travel_id);

    if (!$travel_details) {
        $error_message = "Trajet introuvable.";
    } else {
        // 6. Formater les dates et heures pour l'affichage
        try {
            $date = new DateTime($travel_details['departure_date']);
            $time = new DateTime($travel_details['departure_time']);

            $departure_date_formatted = $date->format('d/m/Y');
            $departure_time_formatted = $time->format('H\hi');

        } catch (Exception $e) {
            error_log("Date parsing error: " . $e->getMessage());
            $departure_date_formatted = $travel_details['departure_date'];
            $departure_time_formatted = $travel_details['departure_time'];
        }

        // 7. Vérification de la disponibilité des places
        if ($travel_details['available_seats'] <= 0) {
            // Un message d'erreur n'est pas idéal ici, un simple état d'indisponibilité suffit.
            $travel_details['no_seats'] = true;
        }

        $credit_cost_per_seat = (int)ceil((float)$travel_details['price_per_seat']);
        // Stocker l'ID du conducteur pour la comparaison
        $driver_id = $travel_details['user_id'];
        // Charger la réservation de l'utilisateur si connecté
        $user_reservation = $is_logged_in ? $travelManager->getReservationForUser((int)$travel_id, $user_id) : null;
    }
}

// Gestion du POST de réservation
if ($pdo && $travelManager && $_SERVER["REQUEST_METHOD"] === 'POST' && isset($_POST['action'])) {
    if (!class_exists('Csrf') || !Csrf::validateRequest($_POST['csrf_token'] ?? '')) {
        $error_message = "Jeton CSRF invalide. Veuillez réessayer.";
    } else {
    if ($_POST['action'] === 'reserve') {
        $seats_to_book = filter_input(INPUT_POST, 'seats_to_book', FILTER_VALIDATE_INT);
        if (!$is_logged_in) {
            $error_message = "Vous devez être connecté pour réserver.";
        } elseif (!$travel_details) {
            $error_message = "Trajet introuvable.";
        } elseif ($user_id === (int)($driver_id ?? 0)) {
            $error_message = "Vous ne pouvez pas réserver votre propre trajet.";
        } elseif ($travelManager->hasActiveReservation((int)$travel_id, $user_id)) {
            $error_message = "Vous avez déjà une réservation pour ce trajet.";
        } elseif (!$seats_to_book || $seats_to_book <= 0) {
            $error_message = "Nombre de places à réserver invalide.";
        } elseif ((int)$travel_details['available_seats'] < $seats_to_book) {
            $error_message = "Pas assez de places disponibles.";
        } else {
            $credit_needed = (int)ceil((float)$travel_details['price_per_seat'] * $seats_to_book);
            if ($creditManager && !$creditManager->hasSufficientBalance($user_id, $credit_needed)) {
                $error_message = "Crédits insuffisants ({$credit_needed} crédits nécessaires).";
            } else {
            $ok = $travelManager->reserveSeats((int)$travel_id, $user_id, (int)$seats_to_book);
            if ($ok) {
                $success_message = ($seats_to_book > 1 ? "Réservation confirmée ({$seats_to_book} places)." : "Réservation confirmée.") . " {$credit_needed} crédits débités.";
                // Recharger les détails pour refléter la nouvelle dispo
                $travel_details = $travelManager->getTravelContactDetails((int)$travel_id);
                $credit_cost_per_seat = (int)ceil((float)$travel_details['price_per_seat']);
                $user_reservation = $travelManager->getReservationForUser((int)$travel_id, $user_id);
                // Ajuste le message selon le statut réel
                if (isset($user_reservation['status']) && $user_reservation['status'] === 'confirmed') {
                    $success_message = ($seats_to_book > 1 ? "Réservation confirmée ({$seats_to_book} places)." : "Réservation confirmée.") . " {$credit_needed} crédits débités.";
                } else {
                    $success_message = ($seats_to_book > 1 ? "Réservation en attente ({$seats_to_book} places)." : "Réservation en attente.") . " Aucun crédit débité pour l'instant.";
                }
                if ($creditManager) { $user_credit_balance = $creditManager->getBalance($user_id); }
            } else {
                $error_message = "Échec de la réservation. Veuillez réessayer.";
            }
        }

        // Déterminer si le trajet est passé
        $is_past = false;
        try {
            $depDT = new DateTime(trim(($travel_details['departure_date'] ?? '') . ' ' . ($travel_details['departure_time'] ?? '00:00:00')));
            $is_past = ($depDT < new DateTime());
        } catch (Throwable $e) { $is_past = false; }
        }
    } elseif ($_POST['action'] === 'cancel_reservation') {
        if (!$is_logged_in) {
            $error_message = "Vous devez être connecté pour annuler.";
        } elseif (!$travel_details) {
            $error_message = "Trajet introuvable.";
        } elseif (!$travelManager->hasActiveReservation((int)$travel_id, $user_id)) {
            $error_message = "Aucune réservation active à annuler.";
        } else {
            $ok = $travelManager->cancelReservation((int)$travel_id, $user_id);
            if ($ok) {
                $success_message = "Réservation annulée. Vos crédits ont été remboursés.";
                $travel_details = $travelManager->getTravelContactDetails((int)$travel_id);
                $credit_cost_per_seat = (int)ceil((float)$travel_details['price_per_seat']);
                $user_reservation = null;
                if ($creditManager) { $user_credit_balance = $creditManager->getBalance($user_id); }
            } else {
                $error_message = "Échec de l'annulation. Veuillez réessayer.";
            }
        }
    } elseif ($_POST['action'] === 'confirm_reservation') {
        if (!$is_logged_in) {
            $error_message = "Vous devez être connecté pour confirmer.";
        } elseif (!$travel_details) {
            $error_message = "Trajet introuvable.";
        } else {
            $ok = $travelManager->confirmReservation((int)$travel_id, $user_id);
            if ($ok) {
                $success_message = "Réservation confirmée. Vos crédits ont été débités.";
                $travel_details = $travelManager->getTravelContactDetails((int)$travel_id);
                $user_reservation = $travelManager->getReservationForUser((int)$travel_id, $user_id);
                if ($creditManager) { $user_credit_balance = $creditManager->getBalance($user_id); }
            } else {
                $error_message = "Échec de la confirmation. Vérifiez la disponibilité et votre solde.";
            }
        }
    } elseif ($_POST['action'] === 'update_reservation') {
        $new_seats = filter_input(INPUT_POST, 'new_seats', FILTER_VALIDATE_INT);
        if (!$is_logged_in) {
            $error_message = "Vous devez être connecté pour modifier la réservation.";
        } elseif (!$travel_details) {
            $error_message = "Trajet introuvable.";
        } elseif (!$travelManager->hasActiveReservation((int)$travel_id, $user_id)) {
            $error_message = "Aucune réservation active à modifier.";
        } elseif (!$new_seats || $new_seats <= 0) {
            $error_message = "Nombre de places invalide.";
        } else {
            $ok = $travelManager->updateReservationSeats((int)$travel_id, $user_id, (int)$new_seats);
            if ($ok) {
                $success_message = "Réservation mise à jour.";
                $travel_details = $travelManager->getTravelContactDetails((int)$travel_id);
                $user_reservation = $travelManager->getReservationForUser((int)$travel_id, $user_id);
            } else {
                $error_message = "Échec de la mise à jour. Vérifiez la disponibilité.";
            }
        }
    }
}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Trajet - EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <?php $page_title='Détails du trajet — EcoRide'; $page_desc="Consultez les informations détaillées d\'un trajet et réservez en toute simplicité."; require __DIR__ . '/includes/layout/seo.php'; ?>
    
</head>
<body class="bg-light">

<?php require __DIR__ . '/includes/layout/navbar.php'; ?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <a href="covoiturages.php" class="text-muted small mb-3 d-block text-decoration-none"><i class="fas fa-arrow-left me-2"></i> Retour aux trajets</a>

            <?php if ($error_message): ?>
                <div class="alert alert-danger text-center" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php elseif ($success_message): ?>
                <div class="alert alert-success text-center" role="alert">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php elseif ($travel_details): ?>

                <h1 class="text-center mb-4" style="color: var(--color-primary-dark);"><?= htmlspecialchars($travel_details['departure_city']) ?> <i class="fas fa-arrow-right mx-2"></i> <?= htmlspecialchars($travel_details['arrival_city']) ?></h1>

                <?php if (isset($travel_details['no_seats']) && $travel_details['no_seats']): ?>
                    <div class="alert alert-danger text-center fw-bold" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i> Désolé, ce trajet n'a plus de places disponibles.
                    </div>
                <?php endif; ?>

                <div class="card shadow-lg mb-4 search-tool-card">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0 fw-bold">
                            <i class="fas fa-calendar-alt me-2"></i> Départ : <?= $departure_date_formatted ?> à <?= $departure_time_formatted ?>
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <p class="fw-bold mb-1"><i class="fas fa-euro-sign me-2"></i> Prix par passager :</p>
                                <span class="fs-4 fw-bold" style="color: var(--color-primary-dark);">
                                    <?= htmlspecialchars(number_format($travel_details['price_per_seat'], 2, ',', ' ')) ?> €
                                </span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="fw-bold mb-1"><i class="fas fa-chair me-2"></i> Places disponibles :</p>
                                <!-- Affichage en vert si places > 0, sinon en rouge -->
                                <span class="fs-4 fw-bold <?= $travel_details['available_seats'] > 0 ? 'text-success' : 'text-danger' ?>">
                                   <?= htmlspecialchars($travel_details['available_seats']) ?>
                                   / <?= htmlspecialchars($travel_details['total_seats'] ?? $travel_details['available_seats']) ?>
                                </span>
                            </div>
                            <?php if ($is_logged_in): ?>
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-coins me-2"></i>
                                        Ce trajet coûte <?= $credit_cost_per_seat ?> crédit(s) par place.
                                        Votre solde : <?= (int)($user_credit_balance ?? 0) ?> crédit(s).
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <hr>

                        <?php if ($is_logged_in && isset($driver_id) && (int)$_SESSION['user_id'] !== (int)$driver_id): ?>
                            <?php if (!empty($is_past) && $is_past): ?>
                                <div class="alert alert-warning" role="alert">
                                    <i class="fas fa-info-circle me-1"></i> Ce trajet est passé. Les réservations ne sont plus possibles.
                                </div>
                            <?php else: ?>
                            <?php if (!empty($user_reservation)): ?>
                                <div class="alert alert-success" role="alert">
                                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                                        <div class="mb-2 mb-md-0">
                                            <i class="fas fa-check-circle me-2"></i>
                                            Réservation active: <?= (int)$user_reservation['seats_booked'] ?> place(s).
                                        </div>
                                        <div class="d-flex gap-2">
                                            <?php if (($user_reservation['status'] ?? '') === 'pending'): ?>
                                                <form method="POST" action="detail_trajet.php?id=<?= (int)$travel_id ?>" class="mb-0">
                                                    <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                                                    <input type="hidden" name="action" value="confirm_reservation">
                                                    <button type="submit" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check me-1"></i> Confirmer
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" action="detail_trajet.php?id=<?= (int)$travel_id ?>" class="mb-0 d-flex align-items-end gap-2">
                                                <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                                                <input type="hidden" name="action" value="update_reservation">
                                                <?php $maxSeats = (int)$user_reservation['seats_booked'] + (int)$travel_details['available_seats']; ?>
                                                <div class="">
                                                    <label for="new_seats" class="form-label fw-bold small mb-1">Modifier places</label>
                                                    <input type="number" class="form-control form-control-sm" id="new_seats" name="new_seats" min="1" max="<?= $maxSeats ?>" value="<?= (int)$user_reservation['seats_booked'] ?>" required>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm mt-3 mt-md-0">
                                                    <i class="fas fa-save me-1"></i> Mettre à jour
                                                </button>
                                            </form>
                                            <form method="POST" action="detail_trajet.php?id=<?= (int)$travel_id ?>" class="mb-0">
                                                <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                                                <input type="hidden" name="action" value="cancel_reservation">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-times me-1"></i> Annuler
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ((int)$travel_details['available_seats'] > 0): ?>
                                <form method="POST" action="detail_trajet.php?id=<?= (int)$travel_id ?>" class="row g-2 align-items-end mb-3">
                                    <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                                    <input type="hidden" name="action" value="reserve">
                                    <div class="col-sm-4">
                                        <label for="seats_to_book" class="form-label fw-bold">Nombre de places</label>
                                        <input type="number" class="form-control" id="seats_to_book" name="seats_to_book" min="1" max="<?= (int)$travel_details['available_seats'] ?>" value="1" required>
                                    </div>
                                    <div class="col-sm-8">
                                        <button type="submit" class="btn btn-success mt-3 mt-sm-0 w-100">
                                            <i class="fas fa-ticket-alt me-2"></i> Réserver ma place
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                            <?php endif; ?>
                        <?php elseif ($is_logged_in && isset($driver_id) && (int)$_SESSION['user_id'] === (int)$driver_id): ?>
                            <div class="alert alert-info" role="alert">
                                Vous êtes le conducteur de ce trajet.
                            </div>
                        <?php endif; ?>

                        <hr>

                        <h3 class="h6 fw-bold mb-2"><i class="fas fa-comment-dots me-2"></i> Description :</h3>
                        <p class="text-muted fst-italic">
                            <?php if (!empty($travel_details['description'])): ?>
                                <?= nl2br(htmlspecialchars(html_entity_decode($travel_details['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?>
                            <?php else: ?>
                                Pas de description supplémentaire fournie par le conducteur.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($travel_details['car_details'])): ?>
                            <hr>
                            <h3 class="h6 fw-bold mb-2"><i class="fas fa-car-side me-2"></i> Détails du véhicule :</h3>
                            <p class="text-muted mb-0">
                                <?= htmlspecialchars($travel_details['car_details']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-lg mb-4 search-tool-card border-secondary">
                    <div class="card-header bg-secondary text-white">
                        <h3 class="h5 mb-0 fw-bold">
                            <i class="fas fa-user-circle me-2"></i> Détails du Conducteur
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php
                            $driverPhoto = null;
                            if (!empty($travel_details['user_photo_bin'])) {
                                $m = !empty($travel_details['user_photo_mime']) ? $travel_details['user_photo_mime'] : 'image/jpeg';
                                $driverPhoto = 'data:' . $m . ';base64,' . base64_encode($travel_details['user_photo_bin']);
                            } elseif (!empty($travel_details['user_photo_path'])) {
                                $driverPhoto = $travel_details['user_photo_path'];
                            }
                        ?>
                        <div class="d-flex align-items-center mb-2">
                            <?php if ($driverPhoto): ?>
                                <img src="<?= htmlspecialchars($driverPhoto) ?>" alt="Photo conducteur" class="rounded-circle me-3" style="width: 56px; height: 56px; object-fit: cover; border: 2px solid var(--color-primary-light);">
                            <?php else: ?>
                                <i class="fas fa-user-circle fa-2x text-success me-2"></i>
                            <?php endif; ?>
                            <p class="mb-0 fw-bold">
                                <?= htmlspecialchars($travel_details['first_name']) ?> <?= htmlspecialchars($travel_details['last_name']) ?>
                            </p>
                        </div>

                        <?php if ($is_logged_in): ?>
                            <?php
                            // Vérifier si l'utilisateur connecté est le conducteur lui-même
                            $is_driver = $is_logged_in && (int)$_SESSION['user_id'] === (int)$driver_id;
                            ?>

                            <?php if (!$is_driver): ?>
                                <p class="mb-2 text-success">
                                    <i class="fas fa-envelope me-2"></i> Email : <?= htmlspecialchars($travel_details['email']) ?>
                                </p>
                                <p class="mb-2 text-success">
                                    <i class="fas fa-phone-alt me-2"></i> Téléphone : <?= htmlspecialchars($travel_details['phone_number'] ?? 'Non renseigné') ?>
                                </p>
                                <div class="alert alert-success mt-3" role="alert">
                                    **Vous pouvez contacter le conducteur directement** en utilisant les coordonnées ci-dessus pour finaliser votre arrangement.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mt-3" role="alert">
                                    **C'est votre propre trajet.** Vous gérez les réservations et les détails depuis <a href="profil.php#trajets" class="alert-link fw-bold">votre profil.</a>
                                </div>
                                <p class="mt-2 small">
                                    <a href="modifier_trajet.php?id=<?= (int)$travel_id ?>" class="text-decoration-none">
                                        <i class="fas fa-edit me-1"></i> Modifier ce trajet
                                    </a>
                                </p>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="alert alert-warning mt-3" role="alert">
                                **Connectez-vous** pour voir l'email et le numéro de téléphone du conducteur et finaliser votre réservation.
                                <a href="connexion.php" class="alert-link fw-bold">Se connecter maintenant</a>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
</main>
<?php require __DIR__ . "/includes/layout/footer.php"; ?>





