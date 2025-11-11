<?php
// Fichier: vehicule_form.php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/VehicleManager.php';
if (file_exists(__DIR__ . '/src/Csrf.php')) {
    require_once __DIR__ . '/src/Csrf.php';
    Csrf::ensureToken();
}

$pdo = Database::getConnection();
if (!$pdo) {
    flash('profile_error', "Impossible d'accéder au formulaire véhicule.");
    header('Location: profil.php');
    exit;
}

$vehicleManager = new VehicleManager($pdo);
$userId = currentUserId();

$vehicleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

$editing = $vehicleId && $vehicleId > 0;
$vehicle = null;

if ($editing) {
    $vehicle = $vehicleManager->getVehicle($vehicleId, $userId);
    if (!$vehicle) {
        flash('profile_error', "Véhicule introuvable ou non autorisé.");
        header('Location: profil.php');
        exit;
    }
}

$brands = $vehicleManager->listBrands();
$brandSuggestions = [
    'Citroën',
    'Peugeot',
    'Renault',
    'Toyota',
    'Tesla',
    'Volkswagen',
    'BMW',
    'Mercedes-Benz',
    'Audi',
    'Hyundai',
];

if (empty($brands)) {
    foreach ($brandSuggestions as $suggestedBrand) {
        $vehicleManager->ensureBrand($suggestedBrand);
    }
    $brands = $vehicleManager->listBrands();
}
$errors = [];

$formData = [
    'brand_id' => $vehicle['brand_id'] ?? null,
    'new_brand' => '',
    'model' => $vehicle['model'] ?? '',
    'license_plate' => $vehicle['license_plate'] ?? '',
    'energy' => $vehicle['energy'] ?? '',
    'color' => $vehicle['color'] ?? '',
    'first_registration_date' => $vehicle['first_registration_date'] ?? '',
    'description' => $vehicle['description'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!class_exists('Csrf') || !Csrf::validateRequest($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session expirée. Veuillez réessayer.';
    } else {
        $formData['brand_id'] = $_POST['brand_id'] !== '' ? (int)$_POST['brand_id'] : null;
        $formData['new_brand'] = trim($_POST['new_brand'] ?? '');
        $formData['model'] = trim($_POST['model'] ?? '');
        $formData['license_plate'] = strtoupper(trim($_POST['license_plate'] ?? ''));
        $formData['energy'] = trim($_POST['energy'] ?? '');
        $formData['color'] = trim($_POST['color'] ?? '');
        $formData['first_registration_date'] = $_POST['first_registration_date'] ?? '';
        $formData['description'] = trim($_POST['description'] ?? '');

        if ($formData['model'] === '') {
            $errors[] = 'Le modèle est obligatoire.';
        }
        if ($formData['license_plate'] === '') {
            $errors[] = "L'immatriculation est obligatoire.";
        }

        if ($formData['first_registration_date'] !== '') {
            $date = DateTime::createFromFormat('Y-m-d', $formData['first_registration_date']);
            if (!$date) {
                $errors[] = 'Date de première immatriculation invalide.';
            }
        }

        if (empty($errors)) {
            $brandId = $formData['brand_id'];
            if ($brandId === null && $formData['new_brand'] !== '') {
                $brandId = $vehicleManager->ensureBrand($formData['new_brand']);
            }

            $payload = [
                'brand_id' => $brandId,
                'model' => $formData['model'],
                'license_plate' => $formData['license_plate'],
                'energy' => $formData['energy'] !== '' ? $formData['energy'] : null,
                'color' => $formData['color'] !== '' ? $formData['color'] : null,
                'first_registration_date' => $formData['first_registration_date'] !== '' ? $formData['first_registration_date'] : null,
                'description' => $formData['description'] !== '' ? $formData['description'] : null,
            ];

            if ($editing) {
                $ok = $vehicleManager->updateVehicle($vehicleId, $userId, $payload);
            } else {
                $newId = $vehicleManager->registerVehicle($userId, $payload);
                $ok = $newId !== null;
            }

            if ($ok) {
                flash('profile_success', $editing ? 'Véhicule mis à jour.' : 'Véhicule ajouté avec succès.');
                header('Location: profil.php');
                exit;
            } else {
                $errors[] = "Impossible d'enregistrer le véhicule. Vérifiez que l'immatriculation est unique.";
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
    <title><?= $editing ? 'Modifier un véhicule' : 'Ajouter un véhicule' ?> - EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <?php $page_title = ($editing ? 'Modifier un véhicule' : 'Ajouter un véhicule') . ' — EcoRide'; $page_desc="Gérez vos véhicules et associez-les à vos trajets."; require __DIR__ . '/includes/layout/seo.php'; ?>
</head>
<body class="bg-light">
<?php require __DIR__ . '/includes/layout/navbar.php'; ?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card search-tool-card p-4">
                <h1 class="h4 mb-3"><?= $editing ? 'Modifier le véhicule' : 'Ajouter un véhicule' ?></h1>
                <p class="text-muted mb-4">Renseignez les informations de votre véhicule pour les associer à vos trajets.</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="vehicule_form.php">
                    <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                    <?php if ($editing): ?>
                        <input type="hidden" name="id" value="<?= (int)$vehicleId ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="brand_id" class="form-label fw-semibold">Marque</label>
                        <select class="form-select" id="brand_id" name="brand_id">
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= (int)$brand['id'] ?>" <?= ((int)$formData['brand_id'] === (int)$brand['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($brand['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Ou bien saisissez une nouvelle marque :</small>
                        <input type="text" class="form-control mt-2" name="new_brand" placeholder="Nouvelle marque"
                               list="brandSuggestions"
                               value="<?= htmlspecialchars($formData['new_brand']) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="model" class="form-label fw-semibold">Modèle *</label>
                        <input type="text" class="form-control" id="model" name="model" required
                               value="<?= htmlspecialchars($formData['model']) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="license_plate" class="form-label fw-semibold">Immatriculation *</label>
                        <input type="text" class="form-control" id="license_plate" name="license_plate" required
                               value="<?= htmlspecialchars($formData['license_plate']) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="energy" class="form-label fw-semibold">Énergie</label>
                        <input type="text" class="form-control" id="energy" name="energy"
                               value="<?= htmlspecialchars($formData['energy']) ?>" placeholder="Essence, Diesel, Électrique...">
                    </div>

                    <div class="mb-3">
                        <label for="color" class="form-label fw-semibold">Couleur</label>
                        <input type="text" class="form-control" id="color" name="color"
                               value="<?= htmlspecialchars($formData['color']) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="first_registration_date" class="form-label fw-semibold">Date de première immatriculation</label>
                        <input type="date" class="form-control" id="first_registration_date" name="first_registration_date"
                               value="<?= htmlspecialchars($formData['first_registration_date']) ?>">
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Informations complémentaires (optionnel)"><?= htmlspecialchars($formData['description']) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="profil.php" class="btn btn-outline-secondary">Annuler</a>
                        <button type="submit" class="btn btn-success"><?= $editing ? 'Mettre à jour' : 'Ajouter' ?></button>
                    </div>
                </form>
                <?php if (!empty($brandSuggestions)): ?>
                    <datalist id="brandSuggestions">
                        <?php foreach ($brandSuggestions as $suggested): ?>
                            <option value="<?= htmlspecialchars($suggested) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/layout/footer.php'; ?>
