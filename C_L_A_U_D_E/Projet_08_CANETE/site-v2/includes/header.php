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
    <!-- Force scroll to top on every page load (must be inline, before render) -->
    <script>
      if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
      // Clear any hash in URL so browser doesn't auto-scroll to an anchor
      if (window.location.hash) {
        history.replaceState(null, '', window.location.pathname);
      }
      window.scrollTo(0, 0);
    </script>
</head>
<body>

<!-- Ball animation -->
<div id="ball-container" class="ball-container" aria-hidden="true">
    <svg id="ball-svg" xmlns="http://www.w3.org/2000/svg" viewBox="200 280 200 200" width="80" height="80">
        <!-- Ombre fond -->
        <path d="M369.37,378.43c-1.67-8.58-4.95-16.65-9.73-24-4.79-7.35-10.85-13.61-18.03-18.59-7.43-5.17-15.72-8.74-24.64-10.62-8.92-1.88-17.95-1.96-26.83-.23-8.57,1.67-16.65,4.95-24,9.74-7.35,4.79-13.61,10.85-18.6,18.03-5.17,7.43-8.74,15.72-10.62,24.64-1.88,8.92-1.96,17.95-.23,26.83,1.67,8.58,4.95,16.65,9.74,24,4.79,7.35,10.85,13.61,18.02,18.6,7.43,5.17,15.72,8.74,24.64,10.62,8.92,1.88,17.95,1.96,26.83.23,8.58-1.67,16.65-4.95,24-9.74,7.35-4.79,13.61-10.85,18.59-18.03,5.17-7.43,8.74-15.72,10.62-24.64,1.88-8.92,1.96-17.95.23-26.83Z" fill="#30254b"/>
        <!-- Balle -->
        <path d="M357.8,402.87c-6.37,30.2-36.12,49.59-66.32,43.22-16.36-3.45-29.54-13.76-37.18-27.28-1.18-2.09-2.23-4.26-3.14-6.49-4.06-9.98-5.29-21.23-2.91-32.55,6.37-30.2,36.12-49.59,66.32-43.23,18.04,3.8,32.23,15.95,39.37,31.56,2,4.37,3.45,9.02,4.27,13.82,1.16,6.75,1.09,13.83-.41,20.93Z" fill="#e2e1e1"/>
        <!-- Ombre sombre -->
        <g mix-blend-mode="darken" opacity=".87">
            <path d="M357.8,402.87c-6.37,30.2-36.12,49.59-66.32,43.22-16.36-3.45-29.54-13.76-37.18-27.28-1.18-2.09-2.23-4.26-3.14-6.49.37.19.73.38,1.09.56,19.94,10.43,42.94,18.26,64.74,12.66,14.06-3.62,26.59-12.9,34.14-25.31,3.42-5.62,5.8-11.85,7.08-18.3,1.16,6.75,1.09,13.83-.41,20.93Z" fill="#bdbdbc"/>
        </g>
        <!-- Reflet -->
        <ellipse cx="290.77" cy="353.91" rx="17.1" ry="10.9" transform="translate(-95.54 107.82) rotate(-18.1)" fill="#fff"/>
    </svg>
</div>

<!-- Trajectory path -->
<svg id="trajectory-container" class="trajectory-container" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
    <path id="ball-trajectory" fill="none" stroke="none"/>
</svg>

<!-- Site header -->
<header id="site-header" class="site-header">
  <div class="site-header__inner">
    <a href="#hero" class="site-header__logo" id="site-logo" aria-label="Agence Ping Pong — retour à l'accueil">
        <div class="site-header__logo-svg" aria-hidden="true">
            <?php include __DIR__ . '/../assets/svg/logo/logofond.svg'; ?>
            <?php include __DIR__ . '/../assets/svg/logo/logobois.svg'; ?>
            <?php include __DIR__ . '/../assets/svg/logo/logodroite.svg'; ?>
            <?php include __DIR__ . '/../assets/svg/logo/logogauche.svg'; ?>
            <?php include __DIR__ . '/../assets/svg/logo/logoballe.svg'; ?>
            <?php include __DIR__ . '/../assets/svg/logo/logoclair.svg'; ?>
            <?php include __DIR__ . '/../assets/svg/logo/logoclairclair.svg'; ?>
        </div>
    </a>

    <!-- Nav desktop — Mac OS 8 menu bar -->
    <nav class="site-nav" aria-label="Navigation principale">
        <!-- Left: text menu items (Title Case) -->
        <div class="site-nav__menus">
            <a href="#concept" class="site-nav__link" data-section="concept">L'échange</a>
            <a href="#services" class="site-nav__link" data-section="services">Les coups</a>
            <a href="#realisations" class="site-nav__link" data-section="realisations">Les échanges</a>
            <a href="#projets" class="site-nav__link" data-section="projets">Projets</a>
            <a href="#contact" class="site-nav__link" data-section="contact">Contact</a>
        </div>

        <!-- Right: clock + icon buttons (Mac OS 8 style) -->
        <div class="site-nav__icons">
            <!-- Heure locale — Mac OS 8 style -->
            <span class="site-nav__clock" id="nav-clock"></span>
            <!-- Pong: mini écran avec jeu -->
            <a href="#pong-game" class="site-nav__icon" data-section="pong-game" aria-label="Partie de Pong">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="28" height="28">
                    <rect x="4" y="3" width="24" height="18" rx="2" fill="#B8C4D8" stroke="#1B2A4A" stroke-width="1.5"/>
                    <rect x="6" y="5" width="20" height="14" rx="1" fill="#1B2A4A"/>
                    <rect x="8" y="8" width="1.5" height="6" fill="#FFFFFF"/>
                    <rect x="22.5" y="9" width="1.5" height="6" fill="#FFFFFF"/>
                    <circle cx="16" cy="12" r="1.2" fill="#E63946"/>
                    <line x1="16" y1="5.5" x2="16" y2="18.5" stroke="#FFFFFF" stroke-width="0.5" stroke-dasharray="1.5 1.5"/>
                    <rect x="12" y="21" width="8" height="2" rx="0.5" fill="#A0AABE" stroke="#1B2A4A" stroke-width="0.8"/>
                    <rect x="9" y="23" width="14" height="2" rx="1" fill="#8892A4" stroke="#1B2A4A" stroke-width="0.8"/>
                    <rect x="7" y="6" width="4" height="2" rx="0.5" fill="#FFFFFF" opacity="0.15"/>
                </svg>
                <!-- Mac OS 8 tooltip window -->
                <div class="site-nav__tooltip">
                    <div class="site-nav__tooltip-bar">
                        <span class="site-nav__tooltip-close"></span>
                        <span class="site-nav__tooltip-title">Pong.app</span>
                    </div>
                    <div class="site-nav__tooltip-body">Une petite partie ?</div>
                </div>
            </a>
            <!-- Contact: enveloppe colorée -->
            <a href="#contact" class="site-nav__icon site-nav__icon--contact" data-section="contact" aria-label="Contact">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="28" height="28">
                    <rect x="3" y="7" width="26" height="18" rx="1.5" fill="#F5F0EB" stroke="#1B2A4A" stroke-width="1.5"/>
                    <polygon points="3,7 16,17 29,7" fill="#E8E3DE" stroke="#1B2A4A" stroke-width="1.2" stroke-linejoin="round"/>
                    <circle cx="16" cy="19" r="3.5" fill="#E63946"/>
                    <circle cx="16" cy="19" r="2" fill="#FF6B76"/>
                    <circle cx="16" cy="19" r="0.8" fill="#E63946"/>
                    <line x1="3" y1="25" x2="12" y2="17" stroke="#C4BFBA" stroke-width="0.8"/>
                    <line x1="29" y1="25" x2="20" y2="17" stroke="#C4BFBA" stroke-width="0.8"/>
                </svg>
                <!-- Mac OS 8 tooltip window -->
                <div class="site-nav__tooltip">
                    <div class="site-nav__tooltip-bar">
                        <span class="site-nav__tooltip-close"></span>
                        <span class="site-nav__tooltip-title">Contact</span>
                    </div>
                    <div class="site-nav__tooltip-body">Envoie la balle !</div>
                </div>
            </a>
            <!-- Remonter: flèche avec globe/terre -->
            <a href="#hero" class="site-nav__icon site-nav__icon--top" id="nav-back-to-top" aria-label="Remonter en haut">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="28" height="28">
                    <circle cx="16" cy="16" r="12" fill="#2ABFBF" stroke="#1B2A4A" stroke-width="1.5"/>
                    <circle cx="16" cy="16" r="9" fill="#5AD4D4"/>
                    <polygon points="16,6 22,16 19,16 19,24 13,24 13,16 10,16" fill="#FFFFFF" stroke="#1B2A4A" stroke-width="1" stroke-linejoin="round"/>
                    <ellipse cx="12" cy="11" rx="3" ry="2" fill="#FFFFFF" opacity="0.25"/>
                </svg>
                <!-- Mac OS 8 tooltip window -->
                <div class="site-nav__tooltip">
                    <div class="site-nav__tooltip-bar">
                        <span class="site-nav__tooltip-close"></span>
                        <span class="site-nav__tooltip-title">Remonter</span>
                    </div>
                    <div class="site-nav__tooltip-body">Retour au service</div>
                </div>
            </a>
        </div>
    </nav>

    <button class="burger-btn" id="burger-btn" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="fullscreen-menu">
        <span class="burger-line"></span>
        <span class="burger-line"></span>
        <span class="burger-line"></span>
    </button>
  </div>
</header>

<!-- Fullscreen navigation menu -->
<nav id="fullscreen-menu" class="fullscreen-menu" aria-hidden="true" aria-label="Navigation principale">
    <div class="fullscreen-menu__window">
        <div class="fullscreen-menu__titlebar">
            <span class="fullscreen-menu__titlebar-btn"></span>
            <span class="fullscreen-menu__titlebar-btn"></span>
            <span class="fullscreen-menu__titlebar-btn"></span>
            <span class="fullscreen-menu__titlebar-title">Navigation</span>
        </div>
        <ul class="fullscreen-menu__list">
            <li class="fullscreen-menu__item">
                <span class="fullscreen-menu__num">01</span>
                <a href="#hero" class="fullscreen-menu__link">Le Service</a>
            </li>
            <li class="fullscreen-menu__item">
                <span class="fullscreen-menu__num">02</span>
                <a href="#concept" class="fullscreen-menu__link">L'Échange</a>
            </li>
            <li class="fullscreen-menu__item">
                <span class="fullscreen-menu__num">03</span>
                <a href="#services" class="fullscreen-menu__link">Les Coups</a>
            </li>
            <li class="fullscreen-menu__item">
                <span class="fullscreen-menu__num">04</span>
                <a href="#realisations" class="fullscreen-menu__link">Les Échanges</a>
            </li>
            <li class="fullscreen-menu__item">
                <span class="fullscreen-menu__num">05</span>
                <a href="#contact" class="fullscreen-menu__link">À ton tour</a>
            </li>
        </ul>
    </div>
    <div class="fullscreen-menu__footer">
        <span>bonjour@agencepingpong.fr</span>
        <span>07 67 78 37 73</span>
    </div>
</nav>

<main id="site-content" class="site-content">
