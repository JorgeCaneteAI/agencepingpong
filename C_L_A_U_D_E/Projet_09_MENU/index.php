<?php
/**
 * MealCoach — Router principal
 * Mappe les routes vers les fichiers PHP correspondants.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// ─── Résolution de la route ───────────────────────────────────────────────────

$requestUri  = $_SERVER['REQUEST_URI'] ?? '/';
$basePath    = BASE_URL; // '/menus'

// Supprime le préfixe BASE_URL
$path = $requestUri;
if (str_starts_with($path, $basePath)) {
    $path = substr($path, strlen($basePath));
}

// Supprime query string
if (($qpos = strpos($path, '?')) !== false) {
    $path = substr($path, 0, $qpos);
}

// Normalise : supprime les slashes de début/fin
$route = trim($path, '/');

// ─── Logout ───────────────────────────────────────────────────────────────────

if ($route === 'logout') {
    logout();
    header('Location: ' . BASE_URL . '/login');
    exit;
}

// ─── Login POST ───────────────────────────────────────────────────────────────

if ($route === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfOk   = verifyCsrfToken($_POST['csrf_token'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($csrfOk && login($password)) {
        header('Location: ' . BASE_URL . '/');
        exit;
    }

    // Échec : on repasse en GET avec erreur
    $loginError = true;
    $route = 'login'; // continue vers la page login ci-dessous
}

// ─── Routes API (JSON) ────────────────────────────────────────────────────────

$apiRoutes = [
    'api/suivi'       => __DIR__ . '/src/api/suivi.php',
    'api/stock'       => __DIR__ . '/src/api/stock.php',
    'api/courses'     => __DIR__ . '/src/api/courses.php',
    'api/produits'    => __DIR__ . '/src/api/produits.php',
    'api/compositeur' => __DIR__ . '/src/api/compositeur.php',
];

if (array_key_exists($route, $apiRoutes)) {
    requireLogin();
    header('Content-Type: application/json; charset=UTF-8');
    $file = $apiRoutes[$route];
    if (file_exists($file)) {
        require $file;
    } else {
        http_response_code(501);
        echo json_encode(['error' => 'API endpoint not implemented']);
    }
    exit;
}

// ─── Routes Admin (login requis) ─────────────────────────────────────────────

$adminRoutes = [
    'admin'              => __DIR__ . '/admin/dashboard.php',
    'admin/import'       => __DIR__ . '/admin/import.php',
    'admin/catalogue'    => __DIR__ . '/admin/catalogue.php',
    'admin/stock'        => __DIR__ . '/admin/stock.php',
    'admin/semaines'     => __DIR__ . '/admin/semaines.php',
    'admin/historique'   => __DIR__ . '/admin/historique.php',
    'admin/settings'     => __DIR__ . '/admin/settings.php',
];

if (array_key_exists($route, $adminRoutes)) {
    requireLogin();
    $file = $adminRoutes[$route];
    if (file_exists($file)) {
        require $file;
    } else {
        http_response_code(501);
        require __DIR__ . '/front/404.php';
    }
    exit;
}

// ─── Routes Front (publiques) ─────────────────────────────────────────────────

$frontRoutes = [
    ''             => __DIR__ . '/front/dashboard.php',
    'login'        => __DIR__ . '/front/login.php',
    'semaine'      => __DIR__ . '/front/semaine.php',
    'jour'         => __DIR__ . '/front/jour.php',
    'batch'        => __DIR__ . '/front/batch.php',
    'courses'      => __DIR__ . '/front/courses.php',
    'stock'        => __DIR__ . '/front/stock.php',
    'compositeur'  => __DIR__ . '/front/compositeur.php',
    'tableau'      => __DIR__ . '/front/tableau-reference.php',
    'suivi'        => __DIR__ . '/front/suivi.php',
];

if (array_key_exists($route, $frontRoutes)) {
    $file = $frontRoutes[$route];
    if (file_exists($file)) {
        require $file;
    } else {
        http_response_code(501);
        // Placeholder pour page non encore créée
        echo '<!DOCTYPE html><html><body><p>Page en cours de création : ' . htmlspecialchars($route) . '</p></body></html>';
    }
    exit;
}

// ─── 404 ──────────────────────────────────────────────────────────────────────

http_response_code(404);
$file404 = __DIR__ . '/front/404.php';
if (file_exists($file404)) {
    require $file404;
} else {
    echo '<!DOCTYPE html><html><body><h1>404 — Page introuvable</h1><p><a href="' . BASE_URL . '/">Retour à l\'accueil</a></p></body></html>';
}
exit;
