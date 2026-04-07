<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title><?= htmlspecialchars($pageTitle) ?> — MealCoach Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="admin">

    <header class="admin-header">
        <a href="<?= BASE_URL ?>/" class="admin-back-link" aria-label="Retour au front office">
            &larr; Front
        </a>
        <h1 class="admin-header-title"><?= htmlspecialchars($pageTitle) ?></h1>
    </header>

    <main class="page-content">
        <?= $content ?>
    </main>

    <nav class="bottom-nav bottom-nav--admin" role="navigation" aria-label="Navigation admin">
        <a href="<?= BASE_URL ?>/admin"
           class="nav-item<?= $activeNav === 'admin-dashboard' ? ' active' : '' ?>"
           aria-label="Stats">
            <span class="nav-icon">&#9776;</span>
            <span class="nav-label">Stats</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/import"
           class="nav-item<?= $activeNav === 'admin-import' ? ' active' : '' ?>"
           aria-label="Import">
            <span class="nav-icon">&#8679;</span>
            <span class="nav-label">Import</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/catalogue"
           class="nav-item<?= $activeNav === 'admin-catalogue' ? ' active' : '' ?>"
           aria-label="Produits">
            <span class="nav-icon">&#9776;</span>
            <span class="nav-label">Produits</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/stock"
           class="nav-item<?= $activeNav === 'admin-stock' ? ' active' : '' ?>"
           aria-label="Stock">
            <span class="nav-icon">&#9878;</span>
            <span class="nav-label">Stock</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/historique"
           class="nav-item<?= $activeNav === 'admin-historique' ? ' active' : '' ?>"
           aria-label="Historique">
            <span class="nav-icon">&#9737;</span>
            <span class="nav-label">Histo</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/settings"
           class="nav-item<?= $activeNav === 'admin-settings' ? ' active' : '' ?>"
           aria-label="Paramètres">
            <span class="nav-icon">&#9881;</span>
            <span class="nav-label">Config</span>
        </a>
    </nav>

    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
