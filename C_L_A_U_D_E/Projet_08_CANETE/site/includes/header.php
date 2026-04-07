<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Agence Ping Pong — Communication visuelle au troc</title>
    <meta name="description" content="Agence de communication visuelle qui fonctionne au troc. Identité visuelle, print, site internet, photo — échangés contre vos produits. Pas d'argent, un échange de valeurs.">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:title" content="Agence Ping Pong — Communication visuelle au troc">
    <meta property="og:description" content="On échange nos compétences créatives contre vos produits. Identité visuelle, print, web, photo.">
    <meta property="og:image" content="<?= SITE_URL ?>/assets/img/og-image.png">
    <meta property="og:url" content="<?= SITE_URL ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">

    <!-- Favicon -->
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">

    <!-- Fonts preload -->
    <link rel="preload" href="assets/fonts/SpaceGrotesk-Variable.woff2" as="font" type="font/woff2" crossorigin>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/tokens.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/nav.css">
    <link rel="stylesheet" href="assets/css/sections.css">
    <link rel="stylesheet" href="assets/css/responsive.css">

    <!-- Schema.org -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ProfessionalService",
        "name": "Agence Ping Pong",
        "description": "Agence de communication visuelle fonctionnant au troc",
        "url": "<?= SITE_URL ?>",
        "image": "<?= SITE_URL ?>/assets/img/og-image.png",
        "knowsAbout": ["identité visuelle", "print", "site internet", "photographie", "colorimétrie"]
    }
    </script>
</head>
<body>

<!-- Canvas 3D (derrière tout le DOM) -->
<canvas id="canvas-3d"></canvas>

<!-- Header / Navigation -->
<header class="site-header">
    <a href="#hero" class="site-header__logo">Ping Pong</a>

    <nav class="site-nav desktop-only">
        <a href="#concept" class="site-nav__link" data-section="concept">Concept</a>
        <a href="#services" class="site-nav__link" data-section="services">Services</a>
        <a href="#realisations" class="site-nav__link" data-section="realisations">Réalisations</a>
        <a href="#contact" class="site-nav__link" data-section="contact">Contact</a>
    </nav>

    <button class="site-header__burger mobile-only" aria-label="Menu">
        <span></span>
        <span></span>
    </button>
</header>

<!-- Menu mobile -->
<div class="mobile-menu__blocker"></div>
<nav class="mobile-menu">
    <a href="#concept" class="mobile-menu__link">Concept</a>
    <a href="#services" class="mobile-menu__link">Services</a>
    <a href="#realisations" class="mobile-menu__link">Réalisations</a>
    <a href="#contact" class="mobile-menu__link">Contact</a>
</nav>

<main id="site-content">
