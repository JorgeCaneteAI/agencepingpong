<?php
/**
 * MealCoach — Configuration globale
 * Hosted at staging.agencepingpong.fr/menus/
 */

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Chemins absolus
define('BASE_PATH',    __DIR__);
define('DATA_PATH',    BASE_PATH . '/data');
define('DB_PATH',      DATA_PATH . '/mealcoach.db');
define('CONTENT_PATH', BASE_PATH . '/content');
define('VENDOR_PATH',  BASE_PATH . '/vendor');

// URL de base (sans slash final)
define('BASE_URL', '/menus');

// Session
define('SESSION_NAME',     'mealcoach_session');
define('SESSION_LIFETIME', 7 * 24 * 60 * 60); // 7 jours en secondes

// Valeurs par défaut
define('DEFAULT_BUDGET_MAX', 50);
define('DEFAULT_SAISON',     'printemps');

// Démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => BASE_URL . '/',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
