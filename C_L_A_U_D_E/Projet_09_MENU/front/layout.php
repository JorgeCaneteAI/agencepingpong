<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <meta name="api-base-url" content="<?= BASE_URL ?>/api">
    <title><?= htmlspecialchars($pageTitle) ?> — MealCoach</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="front">

    <main class="page-content">
        <?= $content ?>
    </main>

    <nav class="bottom-nav" role="navigation" aria-label="Navigation principale">
        <a href="<?= BASE_URL ?>/"
           class="nav-item<?= $activeNav === 'dashboard' ? ' active' : '' ?>"
           aria-label="Accueil">
            <span class="nav-icon">&#9750;</span>
            <span class="nav-label">Accueil</span>
        </a>
        <a href="<?= BASE_URL ?>/semaine"
           class="nav-item<?= $activeNav === 'semaine' ? ' active' : '' ?>"
           aria-label="Semaine">
            <span class="nav-icon">&#9783;</span>
            <span class="nav-label">Semaine</span>
        </a>
        <a href="<?= BASE_URL ?>/courses"
           class="nav-item<?= $activeNav === 'courses' ? ' active' : '' ?>"
           aria-label="Courses">
            <span class="nav-icon">&#9749;</span>
            <span class="nav-label">Courses</span>
        </a>
        <a href="<?= BASE_URL ?>/compositeur"
           class="nav-item<?= $activeNav === 'compositeur' ? ' active' : '' ?>"
           aria-label="Composer">
            <span class="nav-icon">&#9733;</span>
            <span class="nav-label">Composer</span>
        </a>
        <button type="button"
                class="nav-item<?= in_array($activeNav, ['stock', 'tableau', 'suivi', 'batch']) ? ' active' : '' ?>"
                onclick="togglePlusMenu(event)"
                aria-label="Plus"
                aria-expanded="false"
                aria-controls="plusMenu">
            <span class="nav-icon">&hellip;</span>
            <span class="nav-label">Plus</span>
        </button>
    </nav>

    <!-- Plus menu overlay -->
    <div id="plusMenu" class="plus-menu" role="dialog" aria-label="Menu supplémentaire" hidden>
        <div class="plus-menu-overlay" onclick="togglePlusMenu(event)"></div>
        <nav class="plus-menu-content">
            <a href="<?= BASE_URL ?>/stock"
               class="plus-menu-item<?= $activeNav === 'stock' ? ' active' : '' ?>">
                Stock / Garde-manger
            </a>
            <a href="<?= BASE_URL ?>/tableau"
               class="plus-menu-item<?= $activeNav === 'tableau' ? ' active' : '' ?>">
                Tableau de référence
            </a>
            <a href="<?= BASE_URL ?>/suivi"
               class="plus-menu-item<?= $activeNav === 'suivi' ? ' active' : '' ?>">
                Suivi du jour
            </a>
            <a href="<?= BASE_URL ?>/batch"
               class="plus-menu-item<?= $activeNav === 'batch' ? ' active' : '' ?>">
                Batch cooking
            </a>
            <hr class="plus-menu-separator">
            <a href="<?= BASE_URL ?>/admin" class="plus-menu-item plus-menu-item--secondary">
                Back office
            </a>
            <a href="<?= BASE_URL ?>/logout" class="plus-menu-item plus-menu-item--danger">
                Déconnexion
            </a>
        </nav>
    </div>

    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
