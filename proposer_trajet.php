<?php
// Fichier: proposer_trajet.php - 02/11/2025

// 1. Démarrer la session
session_start();

// 2. CONTRÔLE D'ACCÈS : Rediriger si l'utilisateur n'est PAS connecté
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    // Décommenter si vous voulez forcer la redirection
    // header("Location: connexion.php");
    // exit();
}

// 3. Définir les variables de navigation et d'affichage
$is_logged_in = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
$firstname = $_SESSION['user_firstname'] ?? 'Utilisateur';
$errors = []; // Initialisation du tableau d'erreurs

// **********************************************
// CODE : INCLUSIONS DES CLASSES BDD
// **********************************************
// Inclure la classe Database (requise pour Database::getConnection())
$database_path = __DIR__ . '/src/Database.php';
if (!file_exists($database_path)) {
    die("ERREUR FATALE D'INCLUSION : Le fichier Database.php est introuvable.");
}
require_once $database_path;

// Inclure la classe TravelManager
$travel_manager_path = __DIR__ . '/src/TravelManager.php';
if (!file_exists($travel_manager_path)) {
    die("ERREUR FATALE D'INCLUSION : Le fichier TravelManager.php est introuvable.");
}
require_once $travel_manager_path;


// --- GESTION DE L'INSERTION DU TRAJET ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!class_exists('Csrf') || !Csrf::validateRequest($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Jeton CSRF invalide. Veuillez réessayer.';
    }

    // 1. Valider les données (ce bloc vérifie l'existence dans $_POST)
    $required_fields = ['depart', 'arrivee', 'date_depart', 'heure_depart', 'seats', 'price'];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "Le champ '{$field}' est requis.";
        }
    }

    // 2. Traitement et nettoyage des entrées (DÉFINITION DES VARIABLES ICI)
    $depart = htmlspecialchars($_POST['depart'] ?? ''); // Utiliser l'opérateur de coalescence pour éviter les erreurs
    $arrivee = htmlspecialchars($_POST['arrivee'] ?? '');
    $date_depart = $_POST['date_depart'] ?? '';
    $heure_depart = $_POST['heure_depart'] ?? '';

    // Conversion et validation des entiers/nombres
    $seats = filter_var($_POST['seats'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0, 'flags' => FILTER_FLAG_ALLOW_FRACTION]]);

    // Champ facultatif (maintenant défini avec les autres)
    // Stocker en base la valeur brute (on échappera à l'affichage)
    $description = trim($_POST['description'] ?? '');
    // Nouveau: details du vehicule (facultatif)
    $car_details = trim($_POST['car_details'] ?? '');

    // Vérifier les filtres (doit être après la définition des variables)
    if ($seats === false) { $errors[] = "Le nombre de places n'est pas valide."; }
    if ($price === false) { $errors[] = "Le prix n'est pas valide."; }

    // 3. Si aucune erreur, procéder à l'insertion
    if (empty($errors)) {

        // L'ID de l'utilisateur est obligatoire
        $user_id = $_SESSION['user_id'] ?? 0;
        if ($user_id <= 0) {
            $errors[] = "Erreur fatale : L'ID utilisateur est manquant dans la session. Veuillez vous reconnecter.";
        }

        $pdo = Database::getConnection();

        // VÉRIFICATION CRITIQUE DE LA CONNEXION :
        if (!$pdo) {
            $errors[] = "ERREUR FATALE: La connexion à la base de données a échoué. Vérifiez Database.php.";
        }

        // Si tout est OK, on procède avec le Manager
        if (empty($errors)) {

            // Préparation des données pour le Manager (tableau associatif)
            $travelData = [
                    'depart'      => $depart,
                    'arrivee'     => $arrivee,
                    'date_depart' => $date_depart,
                    'heure_depart'=> $heure_depart,
                    'seats'       => $seats,
                    'price'       => $price,
                    'description' => $description,
                    'car_details' => $car_details
            ];

            // 1. Instanciation du Manager
            $travelManager = new TravelManager($pdo);

            // 2. Appel de la méthode d'insertion
            // Utiliser la variante qui gère car_details si disponible
            if (method_exists($travelManager, 'createTravelWithOptionalCarDetails')) {
                $success = $travelManager->createTravelWithOptionalCarDetails($user_id, $travelData);
            } else {
                $success = $travelManager->createTravel($user_id, $travelData);
            }

            if ($success) {
                // Redirection après succès (méthode PRG - Post/Redirect/Get)
                $_SESSION['success_message'] = "Votre trajet de {$depart} à {$arrivee} a été publié avec succès !";
                header("Location: covoiturages.php");
                exit();
            } else {
                // Le Manager a retourné false (erreur SQL)
                $errors[] = "Échec de la publication du trajet. Veuillez vérifier les logs du serveur.";
            }
        }
    }
}
// Fin du bloc de traitement POST
// **********************************************
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposer un Trajet - EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
<video autoplay muted loop playsinline id="bg-video">
    <source src="medias/ontheroad.mp4" type="video/mp4">
</video>

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

                    <?php if ($is_logged_in): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="proposer_trajet.php">Proposer</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profil.php">Profil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="deconnexion.php">Déconnexion</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="connexion.php">Connexion</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main class="container py-5">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            **Erreur(s) :**
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="p-4 shadow-sm search-tool-card">
                <h1 class="text-center mb-4">Proposer un Trajet</h1>
                <p class="text-center lead">Bonjour, <?php echo htmlspecialchars($firstname); ?>. Partagez les détails de votre voyage.</p>

                <form action="proposer_trajet.php" method="POST" id="trajetForm" novalidate>
                    <?= class_exists('Csrf') ? Csrf::input() : '' ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="depart" class="form-label fw-bold">Ville de Départ</label>
                            <input type="text" class="form-control" id="depart" name="depart" required placeholder="Ex: Paris">
                        </div>
                        <div class="col-md-6">
                            <label for="arrivee" class="form-label fw-bold">Ville d'Arrivée</label>
                            <input type="text" class="form-control" id="arrivee" name="arrivee" required placeholder="Ex: Lyon">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="date_depart" class="form-label fw-bold">Date de Départ</label>
                            <input type="date" class="form-control" id="date_depart" name="date_depart" required>
                        </div>
                        <div class="col-md-6">
                            <label for="heure_depart" class="form-label fw-bold">Heure de Départ</label>
                            <input type="time" class="form-control" id="heure_depart" name="heure_depart" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="seats" class="form-label fw-bold">Nombre de Places</label>
                            <input type="number" class="form-control" id="seats" name="seats" required min="1" max="8" placeholder="1 à 8">
                        </div>
                        <div class="col-md-6">
                            <label for="price" class="form-label fw-bold">Prix par Passager (€)</label>
                            <input type="number" class="form-control" id="price" name="price" required min="0" step="0.5" placeholder="Ex: 25.50">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label fw-bold">Description du Trajet (Facultatif)</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Informations importantes sur les bagages, les détours, etc."></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="car_details" class="form-label fw-bold">Détails du véhicule (Facultatif)</label>
                        <input type="text" class="form-control" id="car_details" name="car_details" maxlength="255" placeholder="Ex: Renault Clio - Blanche">
                        <div class="form-text">Modèle, couleur, options utiles (max 255 caractères).</div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="main-btn btn btn-lg">
                            <i class="fas fa-car me-2"></i> Publier le Trajet
                        </button>
                    </div>
                </form> </div>  </div>
    </div>

</main>
<?php require __DIR__ . '/includes/layout/footer.php'; ?>



