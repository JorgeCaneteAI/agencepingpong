<?php
/**
 * MealCoach — Page de connexion
 * Page standalone (sans layout.php).
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config.php';
}
if (!function_exists('csrfField')) {
    require_once __DIR__ . '/../auth.php';
}

// $loginError peut être définie par le router (POST échoué)
$loginError = $loginError ?? false;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title>Connexion — MealCoach</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="login-page">

    <div class="login-wrapper">
        <div class="login-card">

            <h1 class="login-title">MealCoach</h1>
            <p class="login-subtitle">Coaching nutritionnel</p>

            <?php if ($loginError): ?>
                <div class="alert alert--error" role="alert">
                    Mot de passe incorrect. Réessaie.
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/login" class="login-form" novalidate>
                <?= csrfField() ?>

                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input<?= $loginError ? ' form-input--error' : '' ?>"
                        autocomplete="current-password"
                        autofocus
                        required
                    >
                </div>

                <button type="submit" class="btn btn--primary btn--full">
                    Se connecter
                </button>
            </form>

        </div>
    </div>

</body>
</html>
