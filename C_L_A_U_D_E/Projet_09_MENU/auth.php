<?php
/**
 * MealCoach — Authentification
 * Session, CSRF, login/logout.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/config.php';
}
if (!function_exists('getDb')) {
    require_once __DIR__ . '/src/db.php';
}

// ─── CSRF ─────────────────────────────────────────────────────────────────────

/**
 * Génère (ou récupère) le token CSRF de la session courante.
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un token CSRF par comparaison en temps constant.
 */
function verifyCsrfToken(string $token): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    return hash_equals($sessionToken, $token);
}

/**
 * Retourne un champ input hidden HTML avec le token CSRF.
 */
function csrfField(): string
{
    $token = htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// ─── Vérification de session ──────────────────────────────────────────────────

/**
 * Retourne true si l'utilisateur est connecté.
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Redirige vers la page de login si non connecté.
 * À appeler en haut de chaque page protégée.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}

// ─── Login / Logout ───────────────────────────────────────────────────────────

/**
 * Tente de connecter l'utilisateur avec le mot de passe donné.
 * Retourne true si succès, false sinon.
 */
function login(string $password): bool
{
    $hash = getSetting('mot_de_passe_hash');

    if ($hash === null) {
        return false;
    }

    if (password_verify($password, $hash)) {
        // Regénérer l'ID de session pour prévenir la fixation de session
        session_regenerate_id(true);

        $_SESSION['logged_in']    = true;
        $_SESSION['logged_at']    = time();
        $_SESSION['csrf_token']   = bin2hex(random_bytes(32));

        return true;
    }

    return false;
}

/**
 * Déconnecte l'utilisateur et détruit la session.
 */
function logout(): void
{
    $_SESSION = [];

    // Supprimer le cookie de session
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
