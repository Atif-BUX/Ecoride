<?php
// Fichier: modifier_trajet.php - 02/11/2025

// 1. Démarrer la session et contrôle de l'utilisateur
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: connexion.php");
    exit();
}

// 2. Inclusion des classes et variables
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/TravelManager.php';
// CSRF helper
if (file_exists(__DIR__ . '/src/Csrf.php')) { require_once __DIR__ . '/src/Csrf.php'; Csrf::ensureToken(); }
$pdo = Database::getConnection();

$travelManager = $pdo ? new TravelManager($pdo) : null;
$travel_data = null;
$error_message = null;
$success_message = null;
$user_id = (int)$_SESSION['user_id'];
$travel_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// 3. Contrôle de l'ID du trajet
if (!$travel_id || !$travelManager) {
    $error_message = "ID de trajet non fourni ou erreur de base de données.";
} else {
    // Tenter de récupérer les données pour pré-remplir le formulaire
    // On utilise getTravelById qui n'expose pas de contacts
    $travel_data = $travelManager->getTravelById($travel_id);

    // 4. Vérification d'existence et de propriété
    if (!$travel_data) {
        $error_message = "Trajet introuvable.";
    } elseif ((int)$travel_data['user_id'] !== $user_id) {
        // Sécurité : L'utilisateur n'est pas le propriétaire
        $error_message = "Accès refusé. Vous n'êtes pas le propriétaire de ce trajet.";
        $travel_data = null; // Empêche l'affichage du formulaire
    }
}

// 5. Traitement du formulaire de modification (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST" && $travel_data) {
    if (!class_exists('Csrf') || !Csrf::validateRequest($_POST['csrf_token'] ?? '')) {
        $error_message = "Jeton CSRF invalide. Veuillez réessayer.";
    } else {
    // Validation et nettoyage des données (comme dans proposer_trajet.php)
    $data = [
            'depart'        => filter_input(INPUT_POST, 'depart', FILTER_SANITIZE_SPECIAL_CHARS),
            'arrivee'       => filter_input(INPUT_POST, 'arrivee', FILTER_SANITIZE_SPECIAL_CHARS),
            'date_depart'   => filter_input(INPUT_POST, 'date_depart', FILTER_SANITIZE_SPECIAL_CHARS),
            'heure_depart'  => filter_input(INPUT_POST, 'heure_depart', FILTER_SANITIZE_SPECIAL_CHARS),
            'seats'         => filter_input(INPUT_POST, 'seats', FILTER_VALIDATE_INT),
            'price'         => filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT),
        // Conserver la valeur brute; on protègera à l'affichage
            'description'   => trim(filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW) ?? ''),
            'car_details'   => trim(filter_input(INPUT_POST, 'car_details', FILTER_UNSAFE_RAW) ?? '')
    ];

    $is_valid = true;
    if (empty($data['depart']) || empty($data['arrivee']) || empty($data['date_depart']) || $data['seats'] <= 0 || $data['price'] < 0) {
        $error_message = "Veuillez remplir tous les champs obligatoires (départ, arrivée, date, places, prix).";
        $is_valid = false;
    }

    if ($is_valid) {
        // Appeler la méthode adaptée (gère car_details si disponible)
        $updated = method_exists($travelManager, 'updateTravelWithOptionalCarDetails')
            ? $travelManager->updateTravelWithOptionalCarDetails($travel_id, $user_id, $data)
            : $travelManager->updateTravel($travel_id, $user_id, $data);
        if ($updated) {
            $success_message = "Le trajet a été mis à jour avec succès !";
            // Recharger les données pour que le formulaire affiche les nouvelles valeurs
            $travel_data = $travelManager->getTravelById($travel_id);
        } else {
            $error_message = "Erreur lors de la mise à jour du trajet. Veuillez réessayer.";
        }
    } else {
        // Si validation échoue, utiliser les données POST non validées pour remplir les champs
        // Cela permet de ne pas perdre ce que l'utilisateur a tapé
        $travel_data = array_merge($travel_data, $data);
    }
    }
}

// Assurez-vous d'avoir une valeur par défaut si travel_data n'est pas défini (en cas d'erreur de sécurité ou d'ID manquant)
$travel_data = $travel_data ?? [
        'departure_city' => '', 'arrival_city' => '', 'departure_date' => '', 'departure_time' => '12:00',
        'available_seats' => 1, 'price_per_seat' => 5.00, 'description' => '', 'car_details' => ''
];

// Pour pré-remplir les champs avec les données actuelles ou les données POST échouées
$depart_value = htmlspecialchars($travel_data['departure_city']);
$arrivee_value = htmlspecialchars($travel_data['arrival_city']);
$date_value = htmlspecialchars($travel_data['departure_date']);
$heure_value = htmlspecialchars($travel_data['departure_time']);
$seats_value = htmlspecialchars($travel_data['available_seats']);
$price_value = htmlspecialchars(number_format($travel_data['price_per_seat'], 2, '.', ''));
// Pour éviter d'afficher des entités HTML déjà encodées, décoder puis échapper
$description_value = htmlspecialchars(html_entity_decode($travel_data['description'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));
$car_details_value = htmlspecialchars($travel_data['car_details'] ?? '');

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le Trajet #<?= $travel_id ?> - EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --color-primary-dark: #1e8449;
            --color-primary-light: #32CD32;
            --color-neutral-white: #ffffff;
        }
    </style>
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
                    <li class="nav-item">
                        <a class="nav-link" href="covoiturages.php">Covoiturages</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="proposer_trajet.php">Proposer</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profil.php">Profil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="deconnexion.php">Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
            <a href="profil.php#trajets" class="text-muted small mb-3 d-block text-decoration-none"><i class="fas fa-arrow-left me-2"></i> Retour à la gestion des trajets</a>

            <h1 class="text-center mb-4" style="color: var(--color-primary-dark);"><i class="fas fa-edit me-2"></i> Modifier mon Trajet</h1>

            <?php if ($error_message): ?>
                <div class="alert alert-danger text-center" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success text-center" role="alert">
                    <?= htmlspecialchars($success_message) ?>
                    <a href="profil.php#trajets" class="alert-link fw-bold ms-3">Retourner à mon profil</a>
                </div>
            <?php endif; ?>

            <?php if (!$error_message || ($error_message && $travel_data)): // Afficher le formulaire seulement si les données sont chargées ou si l'erreur vient d'un POST ?>

                <div class="card shadow-lg p-4 search-tool-card">
                    <h2 class="h5 mb-4 text-center">Trajet N°<?= $travel_id ?> : Modifier les informations</h2>
                    <form action="modifier_trajet.php?id=<?= $travel_id ?>" method="POST" novalidate>
                        <?= class_exists('Csrf') ? Csrf::input() : '' ?>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="depart" class="form-label fw-bold">Ville de Départ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="depart" name="depart" value="<?= $depart_value ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="arrivee" class="form-label fw-bold">Ville d'Arrivée <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="arrivee" name="arrivee" value="<?= $arrivee_value ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="date_depart" class="form-label fw-bold">Date de Départ <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="date_depart" name="date_depart" value="<?= $date_value ?>" required min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="heure_depart" class="form-label fw-bold">Heure de Départ <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="heure_depart" name="heure_depart" value="<?= $heure_value ?>" required>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="seats" class="form-label fw-bold">Nombre de Places <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="seats" name="seats" value="<?= $seats_value ?>" required min="1" max="8">
                                <small class="form-text text-muted">Places disponibles pour les passagers.</small>
                            </div>
                            <div class="col-md-6">
                                <label for="price" class="form-label fw-bold">Prix par Passager (€) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?= $price_value ?>" required min="0">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">Informations Supplémentaires</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= $description_value ?></textarea>
                            <small class="form-text text-muted">Précisions sur les bagages, le lieu de rencontre, etc.</small>
                        </div>

                        <div class="mb-4">
                            <label for="car_details" class="form-label fw-bold">Détails du véhicule (facultatif)</label>
                            <input type="text" class="form-control" id="car_details" name="car_details" maxlength="255" value="<?= $car_details_value ?>" placeholder="Ex: Renault Clio - Blanche">
                            <small class="form-text text-muted">Modèle, couleur, options utiles (max 255 caractères).</small>
                        </div>

                        <button type="submit" class="btn main-btn w-100 p-2"><i class="fas fa-save me-2"></i> Enregistrer les Modifications</button>

                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

</main>
<?php require __DIR__ . '/includes/layout/footer.php'; ?>




