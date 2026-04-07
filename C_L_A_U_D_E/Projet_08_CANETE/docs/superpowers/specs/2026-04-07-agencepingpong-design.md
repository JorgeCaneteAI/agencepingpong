# Spec — agencepingpong.fr

**Date** : 2026-04-07
**Projet** : Projet_08_CANETE
**Domaine** : agencepingpong.fr
**Staging** : staging.agencepingpong.fr

---

## 1. Vision

Agence Ping Pong est une agence de **communication visuelle** qui fonctionne au **troc** : échange de services créatifs (identité visuelle, print, web, photo) contre un produit. Pas d'argent. Le nom "Ping Pong" incarne le dialogue et l'échange — chaque projet est un aller-retour, jamais un travail à sens unique.

**Cible** : petites entreprises, commerçants, artisans — tout le monde, du moment qu'il y a un produit intéressant à échanger.

---

## 2. Architecture serveur (o2switch)

### Infos serveur
- **Utilisateur** : cajo3558
- **Serveur** : cajo3558.odns.fr
- **IP** : 109.234.166.203
- **Répertoire de base** : `/home2/cajo3558`
- **DB user** : cajo3558_pingpong
- **DB name** : cajo3558_pingpong

### Structure fichiers

```
/home2/cajo3558/
├── public_html/                        (ou dossier dédié au domaine)
│   └── agencepingpong.fr/
│       ├── index.php                   ← point d'entrée
│       ├── .htaccess                   ← rewrites, cache, sécurité
│       ├── assets/
│       │   ├── css/
│       │   │   └── style.css
│       │   ├── js/
│       │   │   ├── app.js              ← logique principale
│       │   │   └── vendors/
│       │   │       ├── gsap.min.js
│       │   │       ├── ScrollTrigger.min.js
│       │   │       ├── SplitText.min.js
│       │   │       ├── lenis.min.js
│       │   │       └── three.min.js
│       │   ├── models/
│       │   │   └── raquette.glb        ← modèle 3D raquette ping pong
│       │   ├── img/
│       │   │   ├── realisations/       ← visuels des projets
│       │   │   ├── og-image.png
│       │   │   └── favicon.svg
│       │   └── fonts/
│       │       └── (typos self-hosted)
│       ├── includes/
│       │   ├── header.php
│       │   ├── footer.php
│       │   ├── config.php              ← connexion DB, constantes
│       │   └── functions.php
│       └── pages/
│           └── (si besoin de pages séparées plus tard)
│
└── staging.agencepingpong.fr/
    ├── .htaccess                       ← auth basique + noindex
    ├── .htpasswd
    ├── bambi/
    ├── nutrition/
    └── .../
```

### Staging — protection

```apache
# .htaccess staging
AuthType Basic
AuthName "Zone de staging - Acces restreint"
AuthUserFile /home2/cajo3558/staging.agencepingpong.fr/.htpasswd
Require valid-user

Header set X-Robots-Tag "noindex, nofollow, noarchive"
```

```html
<!-- Dans chaque page staging -->
<meta name="robots" content="noindex, nofollow">
```

---

## 3. Stack technique

| Couche | Techno | Rôle |
|--------|--------|------|
| Back | PHP vanilla | Rendu des pages, formulaire contact, config |
| Front CSS | CSS custom (grid 16 col, custom properties) | Layout, design system, responsive |
| Front JS | GSAP 3.x (ScrollTrigger, SplitText) | Animations au scroll, reveal texte |
| Smooth scroll | Lenis | Scroll fluide natif |
| 3D | Three.js | Raquette ping pong + balle en 3D |
| Modèle 3D | Fichier .glb | Raquette de ping pong |
| DB | MySQL (cajo3558_pingpong) | Stockage messages contact (optionnel) |
| Hébergement | o2switch | Shared hosting, SSL Let's Encrypt |

---

## 4. Design system

### Palette (inspirée oryzo, adaptée Ping Pong)

| Token | Valeur | Usage |
|-------|--------|-------|
| `--color-bg` | `#100904` | Fond principal, brun-noir chaud |
| `--color-text` | `#ffedd7` | Texte principal, crème chaud |
| `--color-accent` | `#dc5000` | Accent orange, CTAs, liens, highlights |
| `--color-muted` | `#6c5f51` | Texte secondaire, captions |
| `--color-dark` | `#382416` | Boutons sombres, éléments secondaires |
| `--color-pure-white` | `#fff` | Accents ponctuels |
| `--color-pure-black` | `#000` | Ombres, overlays |

### Typographie

- **Display** : typo sans-serif forte, uppercase, weight 500-600, line-height 0.9 (type Syne, Space Grotesk, ou libre équivalente à Halyard)
- **Body** : sans-serif clean, weight 400, line-height 1.5
- **Mono** (optionnel) : pour éléments décoratifs/techniques

### Échelle typographique (système `--screen-unit` responsive)

| Token | Desktop (approx) | Mobile (approx) | Usage |
|-------|-------------------|------------------|-------|
| `--h1` | ~120-160px | ~60-80px | Titres hero |
| `--h2` | ~60-70px | ~35-40px | Titres de section |
| `--h3` | ~40-45px | ~24-28px | Sous-titres |
| `--h4` | ~28-32px | ~18-20px | Titres de cartes |
| `--body1` | ~36-38px | ~18px | Body large |
| `--body2` | ~22-24px | ~16px | Body standard |
| `--btn` | ~16px | ~14px | Boutons |

### Grille

```css
--grid-columns: 16;        /* Desktop */
--grid-columns: 4;          /* Mobile (<768px) */
--site-padding-x: 3.125vw;  /* Desktop (~60px @ 1920) */
--site-padding-x: 4.27vw;   /* Mobile (~16px @ 375) */
```

### Composants

**Boutons** :
- Pill shape (`border-radius: 3em`)
- Uppercase, weight 500
- Variants : default (crème/noir), dark (brun/blanc), accent (orange/noir)
- Transition hover 0.18s

**Séparateurs** :
- Dashed lines (`repeating-linear-gradient`)
- Couleur `--color-muted`

**Glow** :
- `box-shadow: 0 0 10px 3px #ff8c0099, 0 0 60px 45px #e6500a40`
- Utilisé sur CTAs importants et éléments 3D

**Selection** :
- Background orange, texte crème

### Responsive

- **Breakpoint unique** : 768px
- Desktop (>=768px) : grille 16 colonnes
- Mobile (<768px) : grille 4 colonnes
- Classes utilitaires : `.desktop-only`, `.mobile-only`

---

## 5. Structure du site (one-page scroll)

### Section 1 — Hero

- **Contenu** : logo/nom "Ping Pong" + baseline
- **3D** : raquette de ping pong en arrière-plan, la balle frappe et traverse l'écran
- **Animation** : SplitText reveal sur le titre, fade-in baseline
- **Hauteur scroll** : ~300-400vh (pour piloter l'animation 3D d'entrée)

### Section 2 — Concept

- **Contenu** : explication du troc en 2-3 phrases percutantes. "Pas d'argent. Un échange de valeurs."
- **Animation** : texte reveal au scroll (SplitText), raquette 3D en arrière-plan continue de tourner
- **Layout** : texte centré, grande typo, beaucoup d'espace

### Section 3 — Services

- **Contenu** : 4 services en blocs
  - Identité visuelle
  - Print (catalogue, magazine, PLV)
  - Site internet
  - Photo / colorimétrie
- **Animation** : stagger reveal des blocs au scroll
- **Layout** : grille 2x2 desktop, empilé mobile

### Section 4 — Réalisations

- **Contenu** : grille de projets avec visuel + titre + type + lien
- **Projets** :
  - Nacre Magazine (print — maquette, mise en page, colorimétrie)
  - Florajet (print — catalogue client, PLV)
  - YelloEvent (web — site vitrine traiteur/événementiel)
  - Villa Plaisance (web — site maison d'hôtes)
  - Verbruggen / aslideofbambi.dance (web — site artiste chorégraphe)
- **Animation** : hover effect sur les cartes, reveal au scroll
- **Layout** : grille asymétrique (style éditorial), images de tailles variées

### Section 5 — Contact

- **Contenu** : invitation à l'échange + formulaire simple (nom, email, message) ou mail/téléphone
- **Animation** : reveal au scroll
- **Formulaire** : envoi via PHP, stockage optionnel en DB

### Navigation — Desktop

- Position fixe, haut à droite
- 4 liens : Concept, Services, Réalisations, Contact
- Hash navigation (#concept, #services, #realisations, #contact)
- Style : dashed underline sur actif, fade 50% opacity sur hover des autres items
- Scroll smooth via Lenis vers la section cible

### Navigation — Mobile

- Burger discret (pas de hamburger classique lourd)
- Panel slide-in depuis la droite
- Mêmes 4 liens + email en bas
- Overlay sombre derrière le panel
- Navigation ultra light : le moins de friction possible

---

## 6. Élément 3D — La raquette

### Concept
La raquette de ping pong est l'élément signature du site. Elle vit en **canvas fixe plein écran** derrière le contenu DOM (comme oryzo).

### Comportements au scroll

| Section | Comportement raquette | Balle |
|---------|----------------------|-------|
| Hero | Face caméra, frappe la balle | La balle traverse l'écran → intro |
| Concept | Rotation lente, flottante | Absente |
| Services | Légèrement inclinée, suit le scroll | Apparaît entre les blocs (rebonds) |
| Réalisations | En retrait, plus petite | Absente |
| Contact | Revient au centre, invitation | Balle revient = "à toi de jouer" |

### Technique
- **Three.js** : PerspectiveCamera, scene simple, 1-2 lights
- **Modèle** : fichier `.glb` (raquette de ping pong réaliste ou stylisée)
- **Pilotage** : GSAP ScrollTrigger met à jour les propriétés Three.js (rotation, position, scale)
- **Performance** : un seul objet, pas de shaders custom, rendu léger
- **Mobile** : animations 3D simplifiées ou désactivées si performances insuffisantes

---

## 7. Animations

### GSAP ScrollTrigger
- Chaque section est un trigger
- Animations : reveal texte (SplitText), fade-in éléments, parallaxe images
- Pin des sections pour les animations longues (hero notamment)

### Lenis
- Smooth scroll global
- Intégré avec GSAP ScrollTrigger

### Micro-interactions
- Hover sur les boutons (scale + color transition)
- Hover sur les cartes réalisations (image zoom léger + overlay info)
- Cursor custom optionnel sur desktop

---

## 8. SEO & Performance

### SEO
- Balises meta complètes (title, description, og:image)
- Schema.org LocalBusiness (agence communication visuelle, troc)
- Sitemap.xml
- Robots.txt (staging bloqué)
- URLs propres avec .htaccess

### Performance
- Images WebP optimisées
- Fonts self-hosted (preload)
- JS : GSAP + Lenis + Three.js en defer/async
- Modèle 3D compressé (Draco compression pour le .glb)
- Lazy loading images (sauf hero)
- Cache headers agressifs sur assets statiques

---

## 9. Ce qui est hors scope (pour l'instant)

- Multilingue (FR uniquement)
- Back-office / CMS
- Blog
- E-commerce
- Animations Rive
- Moteur de scroll custom (on utilise Lenis)
- WebGL complexe (on reste sur 1 objet 3D simple)
