<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (file_exists(__DIR__ . '/src/Csrf.php')) { require_once __DIR__ . '/src/Csrf.php'; Csrf::ensureToken(); }
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/PasswordResetManager.php';
require_once __DIR__ . '/src/UserManager.php';

$pdo = Database::getConnection();
$token = $_GET['token'] ?? '';
$error = null; $success = null; $valid = null;

if ($pdo && is_string($token) && $token !== '') {
    $pr = new PasswordResetManager($pdo);
    $valid = $pr->getValidByToken($token);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!class_exists('Csrf') || !Csrf::validateRequest($_POST['csrf_token'] ?? '')) {
        $error = 'Jeton CSRF invalide.';
    } elseif (!$pdo) {
        $error = 'Erreur de base de données.';
    } else {
        $token = $_POST['token'] ?? '';
        $pwd = $_POST['password'] ?? '';
        $pwd2 = $_POST['password_confirm'] ?? '';
        if ($token === '' || $pwd === '' || $pwd2 === '') {
            $error = 'Veuillez remplir tous les champs.';
        } elseif ($pwd !== $pwd2) {
            $error = 'Les mots de passe ne correspondent pas.';
        } elseif (strlen($pwd) < 8) {
            $error = 'Mot de passe trop court (8 caractères min.).';
        } else {
            $hash = password_hash($pwd, PASSWORD_DEFAULT);
            $pr = new PasswordResetManager($pdo);
            if ($pr->useTokenAndUpdatePassword($token, $hash)) {
                $success = 'Votre mot de passe a été réinitialisé. Vous pouvez vous connecter.';
            } else {
                $error = 'Lien invalide ou expiré.';
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
    <title>Réinitialiser le mot de passe — EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <?php $page_title='Réinitialiser le mot de passe — EcoRide'; $page_desc='Définissez un nouveau mot de passe pour votre compte.'; require __DIR__ . '/includes/layout/seo.php'; ?>
</head>
<body class="bg-light">
<?php require __DIR__ . '/includes/layout/navbar.php'; ?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card p-4 shadow-lg">
                <h1 class="h4 mb-3">Réinitialiser le mot de passe</h1>
                <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success) ?>
                        <div class="mt-2"><a class="btn btn-success btn-sm" href="connexion.php">Se connecter</a></div>
                    </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                    <?php if ($valid): ?>
                        <form method="POST" action="reinitialiser_mot_de_passe.php?token=<?= urlencode($token) ?>" class="row g-3">
                            <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            <div class="col-12">
                                <label for="password" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            </div>
                            <div class="col-12">
                                <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required minlength="8">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success w-100">Mettre à jour le mot de passe</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">Lien invalide ou expiré. Veuillez recommencer la procédure.</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/layout/footer.php'; ?>
</body>
</html>

