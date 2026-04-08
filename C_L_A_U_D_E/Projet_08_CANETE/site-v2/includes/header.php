<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agence Ping Pong — Communication créative au troc</title>
    <meta name="description" content="Agence Ping Pong, communication créative pour TPE et PME. Pas de devis, pas d'argent : on échange nos savoir-faire. Web, print, identité visuelle — au troc.">

    <!-- Open Graph -->
    <meta property="og:title" content="Agence Ping Pong — Communication créative au troc">
    <meta property="og:description" content="Agence Ping Pong, communication créative pour TPE et PME. Pas de devis, pas d'argent : on échange nos savoir-faire. Web, print, identité visuelle — au troc.">
    <meta property="og:image" content="<?= SITE_URL ?>/assets/img/og-image.jpg">
    <meta property="og:url" content="<?= SITE_URL ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/svg/nav/logo-pingpong.svg">

    <!-- Font preload -->
    <link rel="preload" href="assets/fonts/ClashDisplay-Variable.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="assets/fonts/SpaceGrotesk-Variable.woff2" as="font" type="font/woff2" crossorigin>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/tokens.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/nav.css">
    <link rel="stylesheet" href="assets/css/sections.css">
    <link rel="stylesheet" href="assets/css/responsive.css">

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ProfessionalService",
        "name": "Agence Ping Pong",
        "url": "<?= SITE_URL ?>",
        "description": "Agence de communication créative pour TPE et PME. Services web, print et identité visuelle en échange de compétences — sans argent.",
        "areaServed": "FR",
        "inLanguage": "fr"
    }
    </script>
</head>
<body>

<!-- Ball animation -->
<div id="ball-container" class="ball-container" aria-hidden="true">
    <svg id="ball-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" width="80" height="80">
        <defs>
            <radialGradient id="ball-gradient" cx="38%" cy="35%" r="55%">
                <stop offset="0%" stop-color="#ffffff" stop-opacity="0.9"/>
                <stop offset="40%" stop-color="#f5e6c8"/>
                <stop offset="100%" stop-color="#d4a853"/>
            </radialGradient>
            <filter id="ball-shadow" x="-30%" y="-30%" width="160%" height="160%">
                <feDropShadow dx="3" dy="4" stdDeviation="4" flood-color="rgba(0,0,0,0.25)"/>
            </filter>
        </defs>
        <circle cx="40" cy="40" r="36" fill="url(#ball-gradient)" filter="url(#ball-shadow)"/>
        <!-- Curved line detail (ping pong ball seam) -->
        <path d="M 15 30 Q 40 18 65 30" fill="none" stroke="rgba(180,130,60,0.4)" stroke-width="1.5" stroke-linecap="round"/>
        <path d="M 15 50 Q 40 62 65 50" fill="none" stroke="rgba(180,130,60,0.4)" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
</div>

<!-- Trajectory path -->
<svg id="trajectory-container" class="trajectory-container" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
    <path id="ball-trajectory" fill="none" stroke="none"/>
</svg>

<!-- Site header -->
<header id="site-header" class="site-header">
    <a href="#hero" class="site-header__logo" aria-label="Agence Ping Pong — retour à l'accueil">
        <!-- Two crossed rackets logo -->
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60" width="48" height="48" aria-hidden="true">
            <!-- Racket 1 (tilted left) -->
            <g transform="rotate(-30, 30, 30)">
                <ellipse cx="30" cy="20" rx="11" ry="13" fill="currentColor"/>
                <rect x="28.5" y="32" width="3" height="16" rx="1.5" fill="currentColor"/>
            </g>
            <!-- Racket 2 (tilted right, slightly transparent) -->
            <g transform="rotate(30, 30, 30)" opacity="0.6">
                <ellipse cx="30" cy="20" rx="11" ry="13" fill="currentColor"/>
                <rect x="28.5" y="32" width="3" height="16" rx="1.5" fill="currentColor"/>
            </g>
        </svg>
    </a>

    <button class="burger-btn" id="burger-btn" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="fullscreen-menu">
        <span class="burger-line"></span>
        <span class="burger-line"></span>
        <span class="burger-line"></span>
    </button>
</header>

<!-- Fullscreen navigation menu -->
<nav id="fullscreen-menu" class="fullscreen-menu" aria-hidden="true" aria-label="Navigation principale">
    <ul class="fullscreen-menu__list">
        <li class="fullscreen-menu__item">
            <a href="#hero" class="fullscreen-menu__link">Le Service</a>
        </li>
        <li class="fullscreen-menu__item">
            <a href="#concept" class="fullscreen-menu__link">L'Échange</a>
        </li>
        <li class="fullscreen-menu__item">
            <a href="#services" class="fullscreen-menu__link">Les Coups</a>
        </li>
        <li class="fullscreen-menu__item">
            <a href="#realisations" class="fullscreen-menu__link">Les Échanges</a>
        </li>
        <li class="fullscreen-menu__item">
            <a href="#contact" class="fullscreen-menu__link">À ton tour</a>
        </li>
    </ul>
</nav>

<main id="site-content" class="site-content">
