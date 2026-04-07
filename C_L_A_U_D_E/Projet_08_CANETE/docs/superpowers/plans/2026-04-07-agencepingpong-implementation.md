# agencepingpong.fr — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Construire le site vitrine one-page d'Agence Ping Pong — agence de communication visuelle au troc — avec raquette 3D animée au scroll, design sombre/chaud inspiré oryzo.ai.

**Architecture:** Site PHP vanilla one-page. Le HTML est rendu côté serveur via PHP (includes header/footer). Tout le dynamisme est côté front : GSAP ScrollTrigger pour les animations au scroll, Lenis pour le smooth scroll, Three.js pour la raquette 3D en canvas fixe derrière le DOM. Un formulaire de contact envoie un email via PHP mail() et stocke en DB.

**Tech Stack:** PHP 8.x, CSS custom properties (grille 16 col), GSAP 3.x (ScrollTrigger + SplitText), Lenis, Three.js, MySQL, o2switch.

**Spec:** `docs/superpowers/specs/2026-04-07-agencepingpong-design.md`
**Référence visuelle:** `mirror_oryzo/ANALYSIS.md`

---

## File Map

```
site/
├── index.php                          ← Point d'entrée, assemble les sections
├── .htaccess                          ← Rewrites, cache, sécurité
├── config.example.php                 ← Template config (sans secrets)
├── includes/
│   ├── config.php                     ← Connexion DB, constantes (gitignored)
│   ├── header.php                     ← <head>, nav desktop, nav mobile, ouverture <body>
│   ├── footer.php                     ← Footer HTML, scripts JS, fermeture </body>
│   └── functions.php                  ← Helpers PHP (sanitize, csrf, mail)
├── sections/
│   ├── hero.php                       ← Section 1 : hero + baseline
│   ├── concept.php                    ← Section 2 : explication troc
│   ├── services.php                   ← Section 3 : 4 services
│   ├── realisations.php               ← Section 4 : grille projets par métier
│   └── contact.php                    ← Section 5 : formulaire contact
├── assets/
│   ├── css/
│   │   ├── reset.css                  ← Reset/normalize minimal
│   │   ├── tokens.css                 ← Custom properties : couleurs, typo, grille
│   │   ├── base.css                   ← Styles globaux (body, headings, links, selection)
│   │   ├── components.css             ← Boutons, dashed lines, containers
│   │   ├── nav.css                    ← Navigation desktop + mobile
│   │   ├── sections.css               ← Styles par section (hero, concept, etc.)
│   │   └── responsive.css             ← Overrides mobile (<768px)
│   ├── js/
│   │   ├── vendors/
│   │   │   ├── gsap.min.js
│   │   │   ├── ScrollTrigger.min.js
│   │   │   ├── SplitText.min.js
│   │   │   ├── lenis.min.js
│   │   │   └── three.min.js
│   │   ├── app.js                     ← Init Lenis + GSAP + nav + scroll
│   │   ├── animations.js              ← Toutes les animations ScrollTrigger
│   │   └── scene3d.js                 ← Three.js : raquette + balle
│   ├── models/
│   │   └── raquette.glb               ← Modèle 3D (à créer/sourcer)
│   ├── fonts/
│   │   ├── SpaceGrotesk-Variable.woff2
│   │   └── SpaceGrotesk-Variable.woff
│   └── img/
│       ├── realisations/              ← Visuels projets (webp)
│       ├── og-image.png
│       └── favicon.svg
├── api/
│   └── contact.php                    ← Endpoint POST formulaire contact
└── .gitignore
```

---

## Task 1 : Scaffolding projet + config

**Files:**
- Create: `site/.gitignore`
- Create: `site/config.example.php`
- Create: `site/includes/config.php`
- Create: `site/includes/functions.php`

- [ ] **Step 1: Créer le .gitignore**

```gitignore
# Secrets
includes/config.php

# OS
.DS_Store
Thumbs.db

# IDE
.vscode/
.idea/

# Cache
*.log
```

- [ ] **Step 2: Créer config.example.php**

```php
<?php
// Copier ce fichier en includes/config.php et remplir les valeurs
define('DB_HOST', 'localhost');
define('DB_NAME', 'cajo3558_pingpong');
define('DB_USER', 'cajo3558_pingpong');
define('DB_PASS', ''); // <-- Mot de passe ici

define('SITE_URL', 'https://agencepingpong.fr');
define('SITE_NAME', 'Agence Ping Pong');
define('CONTACT_EMAIL', ''); // <-- Email de réception contact

// Anti-CSRF
define('CSRF_SECRET', ''); // <-- Chaîne aléatoire 32+ caractères
```

- [ ] **Step 3: Créer includes/config.php (local, gitignored)**

Même contenu que config.example.php mais avec les vraies valeurs pour le dev local. Ce fichier ne sera JAMAIS commité.

- [ ] **Step 4: Créer includes/functions.php**

```php
<?php

function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function connectDb(): ?PDO {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        return null;
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add site/.gitignore site/config.example.php site/includes/functions.php
git commit -m "feat: scaffolding projet — config, helpers, gitignore"
```

---

## Task 2 : Design system CSS (tokens + reset + base)

**Files:**
- Create: `site/assets/css/reset.css`
- Create: `site/assets/css/tokens.css`
- Create: `site/assets/css/base.css`

- [ ] **Step 1: Créer reset.css**

```css
*,
*::before,
*::after {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    -webkit-text-size-adjust: 100%;
    -moz-text-size-adjust: 100%;
    text-size-adjust: 100%;
}

body {
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

img, picture, video, canvas, svg {
    display: block;
    max-width: 100%;
}

input, button, textarea, select {
    font: inherit;
    color: inherit;
}

a {
    color: inherit;
    text-decoration: none;
}

ul, ol {
    list-style: none;
}

button {
    background: none;
    border: none;
    cursor: pointer;
}
```

- [ ] **Step 2: Créer tokens.css**

```css
@font-face {
    font-family: 'Space Grotesk';
    src: url('../fonts/SpaceGrotesk-Variable.woff2') format('woff2');
    font-weight: 300 700;
    font-display: swap;
}

:root {
    /* --- Couleurs --- */
    --color-bg: #100904;
    --color-text: #ffedd7;
    --color-accent: #dc5000;
    --color-muted: #6c5f51;
    --color-dark: #382416;
    --color-pure-white: #fff;
    --color-pure-black: #000;

    /* --- Responsive unit --- */
    --inner-width: 100vw;
    --vh: 1vh;
    --screen-unit: min(
        var(--inner-width) / (1920 - 60 * 2),
        calc(var(--vh) * 100) / 1024 * 1.25
    );

    /* --- Typographie --- */
    --font-display: 'Space Grotesk', sans-serif;
    --font-body: 'Space Grotesk', sans-serif;

    --h1: calc(140 * var(--screen-unit));
    --h2: calc(68 * var(--screen-unit));
    --h3: calc(45 * var(--screen-unit));
    --h4: calc(32 * var(--screen-unit));
    --h5: calc(20 * var(--screen-unit));
    --body1: calc(38 * var(--screen-unit));
    --body2: calc(24 * var(--screen-unit));
    --body3: calc(18 * var(--screen-unit));
    --btn-size: calc(16 * var(--screen-unit));

    /* --- Grille --- */
    --grid-columns: 16;
    --site-padding-x: 3.125vw;
    --grid-gap: calc(24 / 1800 * var(--inner-width));
    --grid-column-width: calc(
        (var(--inner-width) - 2 * var(--site-padding-x) - (var(--grid-columns) - 1) * var(--grid-gap))
        / var(--grid-columns)
    );

    /* --- Espacement --- */
    --site-padding-y: calc(var(--site-padding-x) * 2);
    --section-gap: calc(var(--site-padding-x) * 4);

    /* --- Transitions --- */
    --ease-default: cubic-bezier(0.25, 0.1, 0.25, 1);
    --duration-fast: 0.18s;
    --duration-medium: 0.4s;
}

/* --- Mobile overrides --- */
@media (max-width: 767.98px) {
    :root {
        --grid-columns: 4;
        --site-padding-x: calc(16 / 375 * 100vw);

        --h1: calc(64 * var(--screen-unit));
        --h2: calc(38 * var(--screen-unit));
        --h3: calc(28 * var(--screen-unit));
        --h4: calc(20 * var(--screen-unit));
        --body1: calc(20 * var(--screen-unit));
        --body2: calc(16 * var(--screen-unit));
        --body3: calc(14 * var(--screen-unit));
    }
}
```

- [ ] **Step 3: Créer base.css**

```css
html {
    font-family: var(--font-body);
    font-size: var(--body3);
    font-weight: 400;
    line-height: 1.5;
    color: var(--color-text);
    background-color: var(--color-bg);
    overflow-x: hidden;
    scrollbar-width: none;
}

html::-webkit-scrollbar {
    display: none;
}

body {
    overflow-x: hidden;
}

/* --- Headings --- */
h1, h2, h3, h4, h5 {
    font-family: var(--font-display);
    font-weight: 500;
    margin: 0;
    padding: 0;
}

h1 {
    font-size: var(--h1);
    line-height: 0.9;
    text-transform: uppercase;
}

h2 {
    font-size: var(--h2);
    line-height: 0.95;
    text-transform: uppercase;
}

h3 {
    font-size: var(--h3);
    line-height: 1;
    text-transform: uppercase;
}

h4 {
    font-size: var(--h4);
    line-height: 1.1;
    text-transform: uppercase;
}

h5 {
    font-size: var(--h5);
    font-weight: 600;
    line-height: 1.2;
}

/* --- Texte --- */
.body1 {
    font-size: var(--body1);
    line-height: 1.3;
}

.body2 {
    font-size: var(--body2);
    line-height: 1.4;
}

/* --- Selection --- */
::selection {
    background-color: var(--color-accent);
    color: var(--color-text);
}

/* --- Links --- */
a:hover {
    color: var(--color-accent);
}

/* --- Container --- */
.o-container {
    width: 100%;
    padding-left: var(--site-padding-x);
    padding-right: var(--site-padding-x);
}

/* --- Section base --- */
.section {
    position: relative;
    width: 100%;
}

.section__inner {
    position: relative;
    width: 100%;
    min-height: calc(var(--vh) * 100);
    display: flex;
    flex-direction: column;
    justify-content: center;
}

/* --- Utilities --- */
.desktop-only {
    display: block;
}

.mobile-only {
    display: none;
}

@media (max-width: 767.98px) {
    .desktop-only {
        display: none !important;
    }

    .mobile-only {
        display: block !important;
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add site/assets/css/reset.css site/assets/css/tokens.css site/assets/css/base.css
git commit -m "feat: design system CSS — tokens, reset, base styles"
```

---

## Task 3 : Composants CSS (boutons, dashed lines, nav)

**Files:**
- Create: `site/assets/css/components.css`
- Create: `site/assets/css/nav.css`

- [ ] **Step 1: Créer components.css**

```css
/* --- Boutons --- */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 3em;
    padding: 1.2em 2em;
    text-transform: uppercase;
    font-weight: 500;
    font-size: var(--btn-size);
    background-color: var(--color-text);
    color: var(--color-bg);
    transition:
        background-color var(--duration-fast) var(--ease-default),
        color var(--duration-fast) var(--ease-default),
        transform var(--duration-fast) var(--ease-default);
    cursor: pointer;
    border: none;
    letter-spacing: 0.05em;
}

.btn:hover {
    background-color: var(--color-bg);
    color: var(--color-text);
    transform: scale(1.02);
}

.btn--dark {
    background-color: var(--color-dark);
    color: var(--color-text);
}

.btn--dark:hover {
    box-shadow: 0 0 10px 3px #ff8c0099, 0 0 60px 45px #e6500a40;
    border: 2px solid var(--color-accent);
}

.btn--accent {
    background-color: var(--color-accent);
    color: var(--color-bg);
}

.btn--accent:hover {
    background-color: var(--color-text);
    color: var(--color-bg);
}

.btn--large {
    padding: 1.5em 3em;
}

/* --- Dashed lines --- */
.o-dashline {
    width: 100%;
    height: 1px;
    background: repeating-linear-gradient(
        90deg,
        var(--color-muted) 0 2px,
        transparent 0 4px
    );
}

/* --- Glow effect --- */
.glow {
    box-shadow: 0 0 10px 3px #ff8c0099, 0 0 60px 45px #e6500a40;
}

/* --- Texte muted --- */
.text-muted {
    color: var(--color-muted);
}

.text-accent {
    color: var(--color-accent);
}
```

- [ ] **Step 2: Créer nav.css**

```css
/* --- Header --- */
.site-header {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 100;
    padding: 1.5em var(--site-padding-x);
    display: flex;
    align-items: center;
    justify-content: space-between;
    pointer-events: none;
}

.site-header > * {
    pointer-events: auto;
}

/* --- Logo --- */
.site-header__logo {
    font-family: var(--font-display);
    font-size: var(--h5);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--color-text);
}

/* --- Nav desktop --- */
.site-nav {
    display: flex;
    gap: 2em;
}

.site-nav__link {
    font-size: var(--btn-size);
    text-transform: uppercase;
    font-weight: 500;
    letter-spacing: 0.05em;
    color: var(--color-text);
    transition: opacity var(--duration-fast) var(--ease-default);
    position: relative;
    padding-bottom: 0.3em;
}

.site-nav__link::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: repeating-linear-gradient(
        90deg,
        var(--color-accent) 0 2px,
        transparent 0 4px
    );
    transform: scaleX(0);
    transition: transform var(--duration-medium) var(--ease-default);
}

.site-nav__link.is-active::after {
    transform: scaleX(1);
}

.site-nav:hover .site-nav__link:not(:hover) {
    opacity: 0.5;
}

/* --- Burger mobile --- */
.site-header__burger {
    display: none;
    width: 24px;
    height: 24px;
    position: relative;
    z-index: 110;
}

.site-header__burger span {
    display: block;
    width: 100%;
    height: 2px;
    background-color: var(--color-text);
    transition: transform var(--duration-medium) var(--ease-default);
    position: absolute;
    left: 0;
}

.site-header__burger span:first-child { top: 6px; }
.site-header__burger span:last-child { bottom: 6px; }

.site-header__burger.is-open span:first-child {
    transform: translateY(5px) rotate(45deg);
}

.site-header__burger.is-open span:last-child {
    transform: translateY(-5px) rotate(-45deg);
}

/* --- Mobile menu --- */
.mobile-menu {
    position: fixed;
    top: 0;
    right: 0;
    width: 100%;
    height: calc(var(--vh) * 100);
    background-color: var(--color-bg);
    z-index: 105;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 2em;
    transform: translateX(101%);
    transition: transform var(--duration-medium) var(--ease-default);
}

.mobile-menu.is-open {
    transform: translateX(0);
}

.mobile-menu__link {
    font-family: var(--font-display);
    font-size: var(--h3);
    text-transform: uppercase;
    font-weight: 500;
    color: var(--color-text);
}

.mobile-menu__link:hover {
    color: var(--color-accent);
}

.mobile-menu__blocker {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(32, 25, 20, 0.75);
    z-index: 104;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--duration-medium) var(--ease-default);
}

.mobile-menu__blocker.is-visible {
    opacity: 1;
    pointer-events: auto;
}

/* --- Mobile overrides --- */
@media (max-width: 767.98px) {
    .site-nav {
        display: none;
    }

    .site-header__burger {
        display: block;
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add site/assets/css/components.css site/assets/css/nav.css
git commit -m "feat: composants CSS — boutons, dashed lines, navigation"
```

---

## Task 4 : Structure PHP (header, footer, index, sections HTML)

**Files:**
- Create: `site/includes/header.php`
- Create: `site/includes/footer.php`
- Create: `site/index.php`
- Create: `site/sections/hero.php`
- Create: `site/sections/concept.php`
- Create: `site/sections/services.php`
- Create: `site/sections/realisations.php`
- Create: `site/sections/contact.php`

- [ ] **Step 1: Créer includes/header.php**

```php
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
```

- [ ] **Step 2: Créer includes/footer.php**

```php
</main>

<!-- Footer -->
<footer class="site-footer o-container">
    <div class="o-dashline"></div>
    <div class="site-footer__inner">
        <p class="site-footer__copy">&copy; <?= date('Y') ?> Agence Ping Pong</p>
        <p class="site-footer__tagline text-muted">Communication visuelle au troc</p>
    </div>
</footer>

<!-- JS Vendors -->
<script src="assets/js/vendors/gsap.min.js" defer></script>
<script src="assets/js/vendors/ScrollTrigger.min.js" defer></script>
<script src="assets/js/vendors/SplitText.min.js" defer></script>
<script src="assets/js/vendors/lenis.min.js" defer></script>
<script src="assets/js/vendors/three.min.js" defer></script>

<!-- JS App -->
<script src="assets/js/scene3d.js" defer></script>
<script src="assets/js/animations.js" defer></script>
<script src="assets/js/app.js" defer></script>

</body>
</html>
```

- [ ] **Step 3: Créer index.php**

```php
<?php require_once 'includes/header.php'; ?>

<?php require_once 'sections/hero.php'; ?>
<?php require_once 'sections/concept.php'; ?>
<?php require_once 'sections/services.php'; ?>
<?php require_once 'sections/realisations.php'; ?>
<?php require_once 'sections/contact.php'; ?>

<?php require_once 'includes/footer.php'; ?>
```

- [ ] **Step 4: Créer sections/hero.php**

```php
<section id="hero" class="section">
    <div class="section__inner hero">
        <div class="o-container hero__content">
            <h1 class="hero__title" data-animate="split-reveal">
                Ping<br>Pong
            </h1>
            <p class="hero__baseline body1" data-animate="fade-up">
                On échange. On crée.
            </p>
        </div>
    </div>
</section>
```

- [ ] **Step 5: Créer sections/concept.php**

```php
<section id="concept" class="section">
    <div class="section__inner concept">
        <div class="o-container concept__content">
            <h2 class="concept__title" data-animate="split-reveal">
                Chaque projet est un échange
            </h2>
            <div class="o-dashline"></div>
            <p class="concept__text body1" data-animate="split-reveal">
                Pas d'argent. Un échange de valeurs.
                Vous avez un produit, nous avons un savoir-faire.
                On se renvoie la balle jusqu'à ce que le résultat soit parfait.
            </p>
        </div>
    </div>
</section>
```

- [ ] **Step 6: Créer sections/services.php**

```php
<section id="services" class="section">
    <div class="section__inner services">
        <div class="o-container">
            <h2 class="services__title" data-animate="split-reveal">Services</h2>
            <div class="o-dashline"></div>
            <div class="services__grid">
                <div class="services__item" data-animate="fade-up">
                    <h3 class="services__item-title">Identité visuelle</h3>
                    <p class="services__item-desc body2">Logo, charte graphique, direction artistique. L'image qui vous ressemble.</p>
                </div>
                <div class="services__item" data-animate="fade-up">
                    <h3 class="services__item-title">Print</h3>
                    <p class="services__item-desc body2">Catalogue, magazine, PLV, packaging. Du papier qui a du caractère.</p>
                </div>
                <div class="services__item" data-animate="fade-up">
                    <h3 class="services__item-title">Site internet</h3>
                    <p class="services__item-desc body2">Vitrine, portfolio, e-commerce. Votre présence en ligne, sur mesure.</p>
                </div>
                <div class="services__item" data-animate="fade-up">
                    <h3 class="services__item-title">Photo &amp; colorimétrie</h3>
                    <p class="services__item-desc body2">Retouche, étalonnage, direction photo. Des images justes.</p>
                </div>
            </div>
        </div>
    </div>
</section>
```

- [ ] **Step 7: Créer sections/realisations.php**

```php
<section id="realisations" class="section">
    <div class="section__inner realisations">
        <div class="o-container">
            <h2 class="realisations__title" data-animate="split-reveal">Réalisations</h2>
            <div class="o-dashline"></div>
            <div class="realisations__grid">

                <article class="realisations__card" data-animate="fade-up">
                    <div class="realisations__card-img">
                        <img src="assets/img/realisations/editeur-magazine.webp" alt="Maquette magazine" loading="lazy" width="600" height="400">
                    </div>
                    <div class="realisations__card-info">
                        <span class="realisations__card-type text-muted">Print — Maquette &amp; colorimétrie</span>
                        <h4 class="realisations__card-title">Un éditeur de magazine</h4>
                    </div>
                </article>

                <article class="realisations__card" data-animate="fade-up">
                    <div class="realisations__card-img">
                        <img src="assets/img/realisations/reseau-fleuristes.webp" alt="Catalogue et PLV fleuriste" loading="lazy" width="600" height="400">
                    </div>
                    <div class="realisations__card-info">
                        <span class="realisations__card-type text-muted">Print — Catalogue &amp; PLV</span>
                        <h4 class="realisations__card-title">Un réseau de fleuristes</h4>
                    </div>
                </article>

                <article class="realisations__card" data-animate="fade-up">
                    <div class="realisations__card-img">
                        <img src="assets/img/realisations/traiteur-evenementiel.webp" alt="Site traiteur événementiel" loading="lazy" width="600" height="400">
                    </div>
                    <div class="realisations__card-info">
                        <span class="realisations__card-type text-muted">Web — Site vitrine</span>
                        <h4 class="realisations__card-title">Un traiteur événementiel</h4>
                    </div>
                </article>

                <article class="realisations__card" data-animate="fade-up">
                    <div class="realisations__card-img">
                        <img src="assets/img/realisations/maison-hotes.webp" alt="Site maison d'hôtes" loading="lazy" width="600" height="400">
                    </div>
                    <div class="realisations__card-info">
                        <span class="realisations__card-type text-muted">Web — Site vitrine</span>
                        <h4 class="realisations__card-title">Une maison d'hôtes</h4>
                    </div>
                </article>

                <article class="realisations__card" data-animate="fade-up">
                    <div class="realisations__card-img">
                        <img src="assets/img/realisations/choregraphe.webp" alt="Site chorégraphe" loading="lazy" width="600" height="400">
                    </div>
                    <div class="realisations__card-info">
                        <span class="realisations__card-type text-muted">Web — Portfolio artiste</span>
                        <h4 class="realisations__card-title">Une chorégraphe</h4>
                    </div>
                </article>

            </div>
        </div>
    </div>
</section>
```

- [ ] **Step 8: Créer sections/contact.php**

```php
<section id="contact" class="section">
    <div class="section__inner contact">
        <div class="o-container contact__content">
            <h2 class="contact__title" data-animate="split-reveal">
                À vous de jouer
            </h2>
            <p class="contact__intro body1" data-animate="fade-up">
                Vous avez un produit, on a le savoir-faire.<br>
                Renvoyez-nous la balle.
            </p>
            <div class="o-dashline"></div>
            <form class="contact__form" action="api/contact.php" method="POST" data-animate="fade-up">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="contact__form-group">
                    <label for="contact-name" class="contact__label text-muted">Nom</label>
                    <input type="text" id="contact-name" name="name" required class="contact__input" autocomplete="name">
                </div>
                <div class="contact__form-group">
                    <label for="contact-email" class="contact__label text-muted">Email</label>
                    <input type="email" id="contact-email" name="email" required class="contact__input" autocomplete="email">
                </div>
                <div class="contact__form-group">
                    <label for="contact-message" class="contact__label text-muted">Votre proposition</label>
                    <textarea id="contact-message" name="message" required rows="4" class="contact__textarea"></textarea>
                </div>
                <button type="submit" class="btn btn--accent btn--large contact__submit">Envoyer</button>
            </form>
            <div class="contact__success" hidden>
                <p class="body1">Bien reçu. On se renvoie la balle très vite.</p>
            </div>
        </div>
    </div>
</section>
```

- [ ] **Step 9: Commit**

```bash
git add site/includes/header.php site/includes/footer.php site/index.php site/sections/
git commit -m "feat: structure PHP — header, footer, index, 5 sections HTML"
```

---

## Task 5 : CSS des sections

**Files:**
- Create: `site/assets/css/sections.css`
- Create: `site/assets/css/responsive.css`

- [ ] **Step 1: Créer sections.css**

```css
/* ========================================
   HERO
   ======================================== */
#hero {
    height: 400vh;
}

.hero {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: calc(var(--vh) * 100);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.hero__content {
    text-align: center;
    z-index: 2;
    position: relative;
}

.hero__title {
    margin-bottom: 0.3em;
}

.hero__baseline {
    color: var(--color-muted);
}

/* ========================================
   CONCEPT
   ======================================== */
#concept {
    position: relative;
    z-index: 2;
    background-color: var(--color-bg);
}

.concept {
    padding: var(--section-gap) 0;
}

.concept__content {
    max-width: 900px;
    margin: 0 auto;
    text-align: center;
    display: flex;
    flex-direction: column;
    gap: 2em;
}

.concept__title {
    margin-bottom: 0.5em;
}

.concept__text {
    color: var(--color-text);
}

/* ========================================
   SERVICES
   ======================================== */
#services {
    position: relative;
    z-index: 2;
    background-color: var(--color-bg);
}

.services {
    padding: var(--section-gap) 0;
}

.services__title {
    margin-bottom: 0.5em;
}

.services__grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: calc(var(--grid-gap) * 2);
    margin-top: 2em;
}

.services__item {
    padding: 2em 0;
    border-bottom: 1px solid var(--color-dark);
}

.services__item-title {
    margin-bottom: 0.5em;
}

.services__item-desc {
    color: var(--color-muted);
}

/* ========================================
   REALISATIONS
   ======================================== */
#realisations {
    position: relative;
    z-index: 2;
    background-color: var(--color-bg);
}

.realisations {
    padding: var(--section-gap) 0;
}

.realisations__title {
    margin-bottom: 0.5em;
}

.realisations__grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: calc(var(--grid-gap) * 2);
    margin-top: 2em;
}

.realisations__card {
    overflow: hidden;
    cursor: pointer;
}

.realisations__card-img {
    overflow: hidden;
    aspect-ratio: 3 / 2;
}

.realisations__card-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--duration-medium) var(--ease-default);
}

.realisations__card:hover .realisations__card-img img {
    transform: scale(1.05);
}

.realisations__card-info {
    padding: 1em 0;
}

.realisations__card-type {
    font-size: var(--body3);
    display: block;
    margin-bottom: 0.3em;
}

/* ========================================
   CONTACT
   ======================================== */
#contact {
    position: relative;
    z-index: 2;
    background-color: var(--color-bg);
}

.contact {
    padding: var(--section-gap) 0;
}

.contact__content {
    max-width: 700px;
    margin: 0 auto;
    text-align: center;
}

.contact__title {
    margin-bottom: 0.3em;
}

.contact__intro {
    color: var(--color-muted);
    margin-bottom: 1.5em;
}

.contact__form {
    display: flex;
    flex-direction: column;
    gap: 1.5em;
    margin-top: 2em;
    text-align: left;
}

.contact__form-group {
    display: flex;
    flex-direction: column;
    gap: 0.4em;
}

.contact__label {
    font-size: var(--body3);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.contact__input,
.contact__textarea {
    background: transparent;
    border: none;
    border-bottom: 1px solid var(--color-muted);
    padding: 0.8em 0;
    font-size: var(--body2);
    color: var(--color-text);
    outline: none;
    transition: border-color var(--duration-fast) var(--ease-default);
}

.contact__input:focus,
.contact__textarea:focus {
    border-color: var(--color-accent);
}

.contact__textarea {
    resize: vertical;
    min-height: 120px;
}

.contact__submit {
    align-self: center;
    margin-top: 1em;
}

/* ========================================
   FOOTER
   ======================================== */
.site-footer {
    padding: var(--site-padding-y) 0;
}

.site-footer__inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1.5em;
}

.site-footer__copy {
    font-size: var(--body3);
}

.site-footer__tagline {
    font-size: var(--body3);
}
```

- [ ] **Step 2: Créer responsive.css**

```css
@media (max-width: 767.98px) {
    /* Services */
    .services__grid {
        grid-template-columns: 1fr;
    }

    /* Realisations */
    .realisations__grid {
        grid-template-columns: 1fr;
        gap: calc(var(--grid-gap) * 3);
    }

    /* Contact */
    .contact__content {
        padding: 0;
    }

    /* Footer */
    .site-footer__inner {
        flex-direction: column;
        gap: 0.5em;
        text-align: center;
    }

    /* Hero */
    #hero {
        height: 250vh;
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add site/assets/css/sections.css site/assets/css/responsive.css
git commit -m "feat: CSS sections — hero, concept, services, réalisations, contact, responsive"
```

---

## Task 6 : Télécharger les vendors JS + font

**Files:**
- Create: `site/assets/js/vendors/` (GSAP, Lenis, Three.js)
- Create: `site/assets/fonts/` (Space Grotesk)

- [ ] **Step 1: Télécharger GSAP (core + plugins)**

```bash
cd site/assets/js/vendors

# GSAP core
curl -L "https://cdn.jsdelivr.net/npm/gsap@3/dist/gsap.min.js" -o gsap.min.js

# ScrollTrigger
curl -L "https://cdn.jsdelivr.net/npm/gsap@3/dist/ScrollTrigger.min.js" -o ScrollTrigger.min.js

# SplitText (Club GSAP — nécessite licence ou utiliser version trial)
# Alternative : utiliser la version CDN pour dev
curl -L "https://cdn.jsdelivr.net/npm/gsap@3/dist/SplitText.min.js" -o SplitText.min.js
```

Note : SplitText est un plugin Club GSAP. Si la version CDN ne fonctionne pas, on utilisera une alternative open-source ou le CDN direct dans le HTML.

- [ ] **Step 2: Télécharger Lenis**

```bash
curl -L "https://unpkg.com/lenis@latest/dist/lenis.min.js" -o lenis.min.js
```

- [ ] **Step 3: Télécharger Three.js**

```bash
curl -L "https://cdn.jsdelivr.net/npm/three@0.170/build/three.min.js" -o three.min.js

# GLTFLoader (nécessaire pour charger le .glb)
curl -L "https://cdn.jsdelivr.net/npm/three@0.170/examples/js/loaders/GLTFLoader.js" -o GLTFLoader.js
```

- [ ] **Step 4: Télécharger Space Grotesk**

```bash
cd ../../fonts
curl -L "https://fonts.google.com/download?family=Space+Grotesk" -o SpaceGrotesk.zip
unzip SpaceGrotesk.zip -d temp
# Garder uniquement le woff2 variable
cp temp/SpaceGrotesk-VariableFont_wght.ttf SpaceGrotesk-Variable.woff2
rm -rf temp SpaceGrotesk.zip
```

Alternative : convertir le ttf en woff2 avec un outil en ligne, ou télécharger directement depuis Google Fonts API en woff2.

- [ ] **Step 5: Commit**

```bash
git add site/assets/js/vendors/ site/assets/fonts/
git commit -m "feat: vendors JS (GSAP, Lenis, Three.js) + font Space Grotesk"
```

---

## Task 7 : JavaScript — app.js (init Lenis, nav, scroll)

**Files:**
- Create: `site/assets/js/app.js`

- [ ] **Step 1: Créer app.js**

```javascript
/* ========================================
   APP.JS — Init Lenis, navigation, scroll
   ======================================== */

(function () {
    'use strict';

    // --- Lenis smooth scroll ---
    const lenis = new Lenis({
        duration: 1.2,
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
        smoothWheel: true,
    });

    function raf(time) {
        lenis.raf(time);
        requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);

    // Sync Lenis with GSAP ScrollTrigger
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((time) => {
        lenis.raf(time * 1000);
    });
    gsap.ticker.lagSmoothing(0);

    // --- VH unit fix (mobile) ---
    function setVh() {
        document.documentElement.style.setProperty('--vh', window.innerHeight * 0.01 + 'px');
        document.documentElement.style.setProperty('--inner-width', window.innerWidth + 'px');
    }
    setVh();
    window.addEventListener('resize', setVh);

    // --- Navigation desktop : active state ---
    const navLinks = document.querySelectorAll('.site-nav__link');
    const sections = document.querySelectorAll('.section');

    sections.forEach((section) => {
        ScrollTrigger.create({
            trigger: section,
            start: 'top center',
            end: 'bottom center',
            onEnter: () => setActiveNav(section.id),
            onEnterBack: () => setActiveNav(section.id),
        });
    });

    function setActiveNav(sectionId) {
        navLinks.forEach((link) => {
            link.classList.toggle('is-active', link.dataset.section === sectionId);
        });
    }

    // --- Navigation : smooth scroll to section ---
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const target = document.querySelector(targetId);
            if (target) {
                lenis.scrollTo(target, { offset: 0 });
                closeMobileMenu();
            }
        });
    });

    // --- Mobile menu ---
    const burger = document.querySelector('.site-header__burger');
    const mobileMenu = document.querySelector('.mobile-menu');
    const blocker = document.querySelector('.mobile-menu__blocker');

    function openMobileMenu() {
        burger.classList.add('is-open');
        mobileMenu.classList.add('is-open');
        blocker.classList.add('is-visible');
        lenis.stop();
    }

    function closeMobileMenu() {
        burger.classList.remove('is-open');
        mobileMenu.classList.remove('is-open');
        blocker.classList.remove('is-visible');
        lenis.start();
    }

    if (burger) {
        burger.addEventListener('click', () => {
            if (mobileMenu.classList.contains('is-open')) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });
    }

    if (blocker) {
        blocker.addEventListener('click', closeMobileMenu);
    }

    // --- Contact form (AJAX) ---
    const contactForm = document.querySelector('.contact__form');
    const contactSuccess = document.querySelector('.contact__success');

    if (contactForm) {
        contactForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(contactForm);

            try {
                const response = await fetch(contactForm.action, {
                    method: 'POST',
                    body: formData,
                });
                const result = await response.json();

                if (result.success) {
                    contactForm.hidden = true;
                    contactSuccess.hidden = false;
                }
            } catch (err) {
                console.error('Erreur envoi formulaire:', err);
            }
        });
    }
})();
```

- [ ] **Step 2: Commit**

```bash
git add site/assets/js/app.js
git commit -m "feat: app.js — Lenis, navigation, scroll actif, menu mobile, formulaire AJAX"
```

---

## Task 8 : JavaScript — animations.js (GSAP ScrollTrigger)

**Files:**
- Create: `site/assets/js/animations.js`

- [ ] **Step 1: Créer animations.js**

```javascript
/* ========================================
   ANIMATIONS.JS — GSAP ScrollTrigger animations
   ======================================== */

(function () {
    'use strict';

    gsap.registerPlugin(ScrollTrigger, SplitText);

    // --- Wait for DOM + fonts ---
    window.addEventListener('load', initAnimations);

    function initAnimations() {
        animateSplitReveal();
        animateFadeUp();
        animateHeroPin();
        animateServicesStagger();
        animateRealisationsStagger();
    }

    // --- SplitText reveal (titres + textes) ---
    function animateSplitReveal() {
        const elements = document.querySelectorAll('[data-animate="split-reveal"]');

        elements.forEach((el) => {
            const split = new SplitText(el, { type: 'lines,words' });

            gsap.set(split.words, {
                yPercent: 110,
                opacity: 0,
            });

            ScrollTrigger.create({
                trigger: el,
                start: 'top 85%',
                once: true,
                onEnter: () => {
                    gsap.to(split.words, {
                        yPercent: 0,
                        opacity: 1,
                        duration: 0.8,
                        ease: 'power3.out',
                        stagger: 0.04,
                    });
                },
            });
        });
    }

    // --- Fade up (éléments génériques) ---
    function animateFadeUp() {
        const elements = document.querySelectorAll('[data-animate="fade-up"]');

        elements.forEach((el) => {
            gsap.set(el, {
                y: 60,
                opacity: 0,
            });

            ScrollTrigger.create({
                trigger: el,
                start: 'top 90%',
                once: true,
                onEnter: () => {
                    gsap.to(el, {
                        y: 0,
                        opacity: 1,
                        duration: 0.8,
                        ease: 'power2.out',
                    });
                },
            });
        });
    }

    // --- Hero pin (section fixe pendant le scroll) ---
    function animateHeroPin() {
        const hero = document.querySelector('#hero');
        if (!hero) return;

        ScrollTrigger.create({
            trigger: hero,
            start: 'top top',
            end: 'bottom top',
            pin: '.hero',
            pinSpacing: false,
        });

        // Fade out hero content on scroll
        gsap.to('.hero__content', {
            opacity: 0,
            y: -100,
            ease: 'none',
            scrollTrigger: {
                trigger: hero,
                start: '30% top',
                end: '60% top',
                scrub: true,
            },
        });
    }

    // --- Services stagger ---
    function animateServicesStagger() {
        const items = document.querySelectorAll('.services__item');
        if (!items.length) return;

        gsap.set(items, { y: 40, opacity: 0 });

        ScrollTrigger.create({
            trigger: '.services__grid',
            start: 'top 80%',
            once: true,
            onEnter: () => {
                gsap.to(items, {
                    y: 0,
                    opacity: 1,
                    duration: 0.6,
                    ease: 'power2.out',
                    stagger: 0.15,
                });
            },
        });
    }

    // --- Réalisations stagger ---
    function animateRealisationsStagger() {
        const cards = document.querySelectorAll('.realisations__card');
        if (!cards.length) return;

        gsap.set(cards, { y: 60, opacity: 0 });

        ScrollTrigger.create({
            trigger: '.realisations__grid',
            start: 'top 80%',
            once: true,
            onEnter: () => {
                gsap.to(cards, {
                    y: 0,
                    opacity: 1,
                    duration: 0.7,
                    ease: 'power2.out',
                    stagger: 0.12,
                });
            },
        });
    }
})();
```

- [ ] **Step 2: Commit**

```bash
git add site/assets/js/animations.js
git commit -m "feat: animations.js — SplitText reveal, fade-up, hero pin, stagger sections"
```

---

## Task 9 : JavaScript — scene3d.js (Three.js raquette + balle)

**Files:**
- Create: `site/assets/js/scene3d.js`

- [ ] **Step 1: Créer scene3d.js**

```javascript
/* ========================================
   SCENE3D.JS — Three.js raquette ping pong
   ======================================== */

(function () {
    'use strict';

    // --- Config ---
    const CONFIG = {
        canvasId: 'canvas-3d',
        modelPath: 'assets/models/raquette.glb',
        cameraFov: 45,
        cameraNear: 0.1,
        cameraFar: 100,
        cameraZ: 5,
        ambientLightColor: 0xffedd7,
        ambientLightIntensity: 0.4,
        directionalLightColor: 0xffffff,
        directionalLightIntensity: 0.8,
        directionalLightPosition: { x: 5, y: 5, z: 5 },
        ballRadius: 0.08,
        ballColor: 0xffedd7,
    };

    let canvas, renderer, scene, camera;
    let raquette = null;
    let ball = null;
    let scrollProgress = 0;
    let isReady = false;

    window.addEventListener('load', init);

    function init() {
        canvas = document.getElementById(CONFIG.canvasId);
        if (!canvas) return;

        // --- Renderer ---
        renderer = new THREE.WebGLRenderer({
            canvas: canvas,
            antialias: true,
            alpha: true,
        });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.outputColorSpace = THREE.SRGBColorSpace;

        // --- Scene ---
        scene = new THREE.Scene();

        // --- Camera ---
        camera = new THREE.PerspectiveCamera(
            CONFIG.cameraFov,
            window.innerWidth / window.innerHeight,
            CONFIG.cameraNear,
            CONFIG.cameraFar
        );
        camera.position.z = CONFIG.cameraZ;

        // --- Lights ---
        const ambientLight = new THREE.AmbientLight(
            CONFIG.ambientLightColor,
            CONFIG.ambientLightIntensity
        );
        scene.add(ambientLight);

        const directionalLight = new THREE.DirectionalLight(
            CONFIG.directionalLightColor,
            CONFIG.directionalLightIntensity
        );
        directionalLight.position.set(
            CONFIG.directionalLightPosition.x,
            CONFIG.directionalLightPosition.y,
            CONFIG.directionalLightPosition.z
        );
        scene.add(directionalLight);

        // --- Ball (placeholder sphere) ---
        const ballGeometry = new THREE.SphereGeometry(CONFIG.ballRadius, 32, 32);
        const ballMaterial = new THREE.MeshStandardMaterial({ color: CONFIG.ballColor });
        ball = new THREE.Mesh(ballGeometry, ballMaterial);
        ball.position.set(2, 0, 0);
        ball.visible = false;
        scene.add(ball);

        // --- Load raquette model ---
        loadModel();

        // --- Scroll tracking ---
        setupScrollTracking();

        // --- Resize ---
        window.addEventListener('resize', onResize);

        // --- Render loop ---
        animate();
    }

    function loadModel() {
        const loader = new THREE.GLTFLoader();

        loader.load(
            CONFIG.modelPath,
            (gltf) => {
                raquette = gltf.scene;
                raquette.scale.set(1, 1, 1);
                raquette.position.set(0, 0, 0);
                scene.add(raquette);
                isReady = true;
            },
            undefined,
            (error) => {
                console.warn('Modèle 3D non trouvé, utilisation du placeholder');
                createPlaceholderRaquette();
            }
        );
    }

    function createPlaceholderRaquette() {
        // Raquette simplifiée en géométrie basique
        const group = new THREE.Group();

        // Tampon (cylindre aplati)
        const padGeometry = new THREE.CylinderGeometry(0.7, 0.7, 0.05, 32);
        const padMaterial = new THREE.MeshStandardMaterial({ color: 0xdc5000 });
        const pad = new THREE.Mesh(padGeometry, padMaterial);
        pad.rotation.x = Math.PI / 2;
        group.add(pad);

        // Manche (cylindre fin)
        const handleGeometry = new THREE.CylinderGeometry(0.08, 0.08, 0.8, 16);
        const handleMaterial = new THREE.MeshStandardMaterial({ color: 0x382416 });
        const handle = new THREE.Mesh(handleGeometry, handleMaterial);
        handle.position.y = -0.9;
        group.add(handle);

        raquette = group;
        scene.add(raquette);
        isReady = true;
    }

    function setupScrollTracking() {
        // Track overall scroll progress (0 to 1)
        ScrollTrigger.create({
            trigger: document.body,
            start: 'top top',
            end: 'bottom bottom',
            onUpdate: (self) => {
                scrollProgress = self.progress;
            },
        });

        // --- Hero section: raquette face caméra, balle frappe ---
        ScrollTrigger.create({
            trigger: '#hero',
            start: 'top top',
            end: 'bottom top',
            onUpdate: (self) => {
                if (!isReady || !raquette) return;
                const p = self.progress;

                // Raquette: rotation légère
                raquette.rotation.y = p * Math.PI * 0.5;
                raquette.rotation.x = Math.sin(p * Math.PI) * 0.3;
                raquette.position.x = p * -1.5;

                // Balle: apparaît et traverse
                if (p > 0.3 && p < 0.8) {
                    ball.visible = true;
                    const ballProgress = (p - 0.3) / 0.5;
                    ball.position.x = -2 + ballProgress * 6;
                    ball.position.y = Math.sin(ballProgress * Math.PI) * 1.5;
                } else {
                    ball.visible = false;
                }
            },
        });

        // --- Concept section: rotation lente flottante ---
        ScrollTrigger.create({
            trigger: '#concept',
            start: 'top bottom',
            end: 'bottom top',
            onUpdate: (self) => {
                if (!isReady || !raquette) return;
                const p = self.progress;
                raquette.rotation.y = Math.PI * 0.5 + p * Math.PI * 0.3;
                raquette.rotation.z = Math.sin(p * Math.PI * 2) * 0.1;
                raquette.position.x = -1.5 + Math.sin(p * Math.PI) * 0.5;
                raquette.position.y = Math.sin(p * Math.PI * 2) * 0.2;
                raquette.scale.setScalar(1 - p * 0.1);
            },
        });

        // --- Services section: inclinée, balle rebondit ---
        ScrollTrigger.create({
            trigger: '#services',
            start: 'top bottom',
            end: 'bottom top',
            onUpdate: (self) => {
                if (!isReady || !raquette) return;
                const p = self.progress;
                raquette.rotation.y = Math.PI * 0.8 + p * 0.2;
                raquette.rotation.x = 0.3;
                raquette.position.x = 2 - p * 0.5;
                raquette.position.y = -0.5 + p * 0.5;
                raquette.scale.setScalar(0.9);

                // Balle rebondissante
                ball.visible = true;
                ball.position.x = Math.sin(p * Math.PI * 3) * 1.5;
                ball.position.y = Math.abs(Math.sin(p * Math.PI * 4)) * 1;
                ball.position.z = 1;
            },
        });

        // --- Réalisations: en retrait ---
        ScrollTrigger.create({
            trigger: '#realisations',
            start: 'top bottom',
            end: 'bottom top',
            onUpdate: (self) => {
                if (!isReady || !raquette) return;
                const p = self.progress;
                raquette.position.z = -2;
                raquette.position.x = 3;
                raquette.scale.setScalar(0.5);
                raquette.rotation.y += 0.001;
                ball.visible = false;
            },
        });

        // --- Contact: revient au centre ---
        ScrollTrigger.create({
            trigger: '#contact',
            start: 'top bottom',
            end: 'bottom top',
            onUpdate: (self) => {
                if (!isReady || !raquette) return;
                const p = self.progress;
                raquette.position.x = 3 - p * 3;
                raquette.position.y = 0;
                raquette.position.z = -2 + p * 2;
                raquette.scale.setScalar(0.5 + p * 0.5);
                raquette.rotation.y = Math.PI + p * Math.PI;

                // Balle revient
                if (p > 0.5) {
                    ball.visible = true;
                    const bp = (p - 0.5) / 0.5;
                    ball.position.x = 3 - bp * 3;
                    ball.position.y = Math.sin(bp * Math.PI) * 0.8;
                    ball.position.z = 0;
                }
            },
        });
    }

    function onResize() {
        if (!camera || !renderer) return;
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    }

    function animate() {
        requestAnimationFrame(animate);
        if (renderer && scene && camera) {
            renderer.render(scene, camera);
        }
    }
})();
```

- [ ] **Step 2: Ajouter le style du canvas 3D dans base.css**

Ajouter à la fin de `site/assets/css/base.css` :

```css
/* --- Canvas 3D --- */
#canvas-3d {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
    pointer-events: none;
}
```

- [ ] **Step 3: Commit**

```bash
git add site/assets/js/scene3d.js site/assets/css/base.css
git commit -m "feat: scene3d.js — Three.js raquette ping pong + balle animées au scroll"
```

---

## Task 10 : API contact (PHP)

**Files:**
- Create: `site/api/contact.php`

- [ ] **Step 1: Créer api/contact.php**

```php
<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// CSRF check
$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token invalide']);
    exit;
}

// Sanitize inputs
$name = sanitize($_POST['name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$message = sanitize($_POST['message'] ?? '');

// Validate
if (empty($name) || !$email || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tous les champs sont requis']);
    exit;
}

// Store in DB
$pdo = connectDb();
if ($pdo) {
    $stmt = $pdo->prepare('INSERT INTO contacts (name, email, message, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$name, $email, $message]);
}

// Send email
$to = CONTACT_EMAIL;
$subject = 'Agence Ping Pong — Nouveau message de ' . $name;
$body = "Nom : $name\nEmail : $email\n\nMessage :\n$message";
$headers = "From: noreply@agencepingpong.fr\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8";

mail($to, $subject, $body, $headers);

// Reset CSRF token
unset($_SESSION['csrf_token']);

echo json_encode(['success' => true]);
```

- [ ] **Step 2: Créer la table contacts en SQL**

À exécuter dans phpMyAdmin sur o2switch :

```sql
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- [ ] **Step 3: Commit**

```bash
git add site/api/contact.php
git commit -m "feat: API contact — validation, CSRF, stockage DB, envoi email"
```

---

## Task 11 : .htaccess + staging setup

**Files:**
- Create: `site/.htaccess`
- Create: `staging/.htaccess`

- [ ] **Step 1: Créer site/.htaccess**

```apache
# --- Sécurité ---
Options -Indexes
ServerSignature Off

# Bloquer accès aux fichiers sensibles
<FilesMatch "^(config\.php|functions\.php|\.htpasswd)$">
    Require all denied
</FilesMatch>

# --- HTTPS redirect ---
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# --- Cache headers ---
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType font/woff2 "access plus 1 year"
    ExpiresByType model/gltf-binary "access plus 1 year"
</IfModule>

# --- Compression ---
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css application/javascript application/json image/svg+xml
</IfModule>

# --- CORS pour les fonts ---
<IfModule mod_headers.c>
    <FilesMatch "\.(woff2?|ttf|otf|eot)$">
        Header set Access-Control-Allow-Origin "*"
    </FilesMatch>
</IfModule>
```

- [ ] **Step 2: Créer staging/.htaccess**

```apache
# --- Auth basique ---
AuthType Basic
AuthName "Zone de staging - Acces restreint"
AuthUserFile /home2/cajo3558/staging.agencepingpong.fr/.htpasswd
Require valid-user

# --- Bloquer indexation ---
Header set X-Robots-Tag "noindex, nofollow, noarchive"

# --- Sécurité ---
Options -Indexes
```

- [ ] **Step 3: Commit**

```bash
git add site/.htaccess staging/.htaccess
git commit -m "feat: .htaccess — HTTPS, cache, compression, staging protégé"
```

---

## Task 12 : SEO (robots.txt, sitemap.xml)

**Files:**
- Create: `site/robots.txt`
- Create: `site/sitemap.xml`

- [ ] **Step 1: Créer robots.txt**

```
User-agent: *
Allow: /
Sitemap: https://agencepingpong.fr/sitemap.xml

# Bloquer staging
User-agent: *
Disallow: /staging/
```

- [ ] **Step 2: Créer sitemap.xml**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://agencepingpong.fr/</loc>
        <lastmod>2026-04-07</lastmod>
        <changefreq>monthly</changefreq>
        <priority>1.0</priority>
    </url>
</urlset>
```

- [ ] **Step 3: Commit**

```bash
git add site/robots.txt site/sitemap.xml
git commit -m "feat: SEO — robots.txt, sitemap.xml"
```

---

## Résumé des tasks

| Task | Description | Fichiers principaux |
|------|-------------|---------------------|
| 1 | Scaffolding + config | .gitignore, config, functions.php |
| 2 | Design system CSS (tokens, reset, base) | tokens.css, reset.css, base.css |
| 3 | Composants CSS (boutons, nav) | components.css, nav.css |
| 4 | Structure PHP (header, footer, sections) | index.php, header.php, 5 sections |
| 5 | CSS des sections + responsive | sections.css, responsive.css |
| 6 | Vendors JS + font | GSAP, Lenis, Three.js, Space Grotesk |
| 7 | app.js (Lenis, nav, scroll) | app.js |
| 8 | animations.js (GSAP ScrollTrigger) | animations.js |
| 9 | scene3d.js (Three.js raquette) | scene3d.js |
| 10 | API contact PHP | api/contact.php |
| 11 | .htaccess + staging | .htaccess (site + staging) |
| 12 | SEO | robots.txt, sitemap.xml |
