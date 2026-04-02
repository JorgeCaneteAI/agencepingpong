# Résumé de session — 2 avril 2026

## Ce qui a été fait cette session

### 1. Import WordPress XML
- Script `config/import-from-wordpress-xml.php` qui parse l'export WXR
- **48 pièces** importées (1 template Elementor filtré)
- **1300 images** de galerie extraites
- **44 vidéos** YouTube/Vimeo
- **43 théâtres** avec logos et URLs
- Tous les crédits parsés (chorégraphie, musique, costumes, scénographie, lumière, direction musicale, distribution, crédit photo)
- Ajout de la pièce "AMOR & PSYCHE ? — Extended version for Tanzanienne Linz" (slug: `amor-psyche-linz`)

### 2. Hero de la page pièce — enrichi
- **Date complète** de la première affichée (plus seulement l'année)
- **Sous-titre** en italique si présent
- **Logo du théâtre flottant** qui suit le curseur avec inertie (`ease: 0.02`)
  - Position `fixed` + `window.addEventListener('mousemove')` car GSAP pin bloque les events sur les éléments `absolute`
  - Filtre `brightness(0) invert(1)` pour le rendre blanc
  - Opacité 0.2 au repos, 0.6 au hover
- **Nom du théâtre** + **compagnie** (si différente) affichés en dessous du titre
- L'année en watermark géant en fond reste

### 3. Galerie photos — refonte complète
- **Layout masonry** avec `column-count: 4` — chaque image garde ses proportions naturelles (paysage/portrait)
- **Lightbox** : clic sur une image → vue plein écran, navigation ‹ › + clavier + compteur
- **Titre "GALLERY"** écrit verticalement lettre par lettre avec effet glow ondulant
- **Bloc gauche/droite** : séparation verticale (titre à gauche, photos à droite)
- **12 yeux flottants** dans le bloc titre :
  - 3 gros (85-95px), 3 moyens (58-65px), 6 petits (38-50px)
  - 6 avec cils (lignes SVG au-dessus de la paupière)
  - 6 animations de trajectoire différentes × normal/reverse
  - **Clignement aléatoire** : paupière SVG qui se ferme/ouvre, toutes les 0.8-3.8s, 30% de chance de double-clignement
- Plus de limite de 20 images — toutes s'affichent
- Scrollbar jaune assortie au design

### 4. Base de données — schema v2
Nouvelles tables ajoutées :
- `theatres` (id, nom, slug, logo_url, site_url, ville, pays, timestamps)
- `collaborateurs` (id, nom, slug, role_principal, photo_url, bio, site_url, timestamps)
- `videos` (id, piece_id, url, titre, ordre) — plusieurs vidéos par pièce
- `critiques` (id, piece_id, type [text/pdf/image], contenu, auteur, source, source_url, fichier_url, ordre)
- `piece_collaborateurs` (id, piece_id, collaborateur_id, role) — table pivot

Migration `config/migrate-v2.php` :
- 43 théâtres migrés depuis les données inline des pièces
- 44 vidéos migrées depuis le champ `video_url`
- Colonne `theatre_id` ajoutée à `pieces` + liens établis

### 5. Admin — CRUD complet
URL : `http://localhost:8000/admin/`
- **Dashboard** (`admin/index.php`) — stats globales + liens rapides
- **Pièces** (`admin/pieces.php`) — liste triée par date, thumbnail, titre, théâtre, première, statut
- **Édition pièce** (`admin/piece-edit.php`) — 6 sections :
  1. Général (titre, slug auto, sous-titre, statut, image URL)
  2. Production (théâtre dropdown, compagnie, première, durée)
  3. Crédits (tous les champs techniques)
  4. Galerie (liste images + ajout/suppression)
  5. Vidéos (liste + ajout/suppression)
  6. Critiques (type, texte, auteur, source, fichier)
- **Théâtres** (`admin/theatres.php`) — CRUD inline
- **Collaborateurs** (`admin/collaborateurs.php`) — CRUD inline
- Pas de login (dev local), thème dark assorti au site

---

## Problèmes résolus / Astuces techniques

### GSAP ScrollTrigger pin bloque TOUT
C'est LE problème récurrent du projet. Quand GSAP pin le body pour le scroll horizontal, il crée un wrapper div qui intercepte tous les pointer events.

**Solutions appliquées :**
- `position: fixed` + `z-index: 99999` pour les éléments cliquables (logo home)
- `window.addEventListener('mousemove')` au lieu de `document.addEventListener` ou d'écouter sur l'élément directement
- Inline scripts dans les fichiers PHP avec `item.onclick = function()` au lieu d'addEventListener (pour la lightbox galerie, le preview d'images sur l'accueil, le flip e→3)
- CSS-only pour les effets hover (dock effect de la liste de pièces)

### Logo théâtre flottant — `position: absolute` ne marchait pas
Le logo en `position: absolute` dans le hero restait figé au centre car GSAP pin change le contexte de positionnement.
**Fix :** Passé en `position: fixed` + coordonnées calculées via `e.clientX`/`e.clientY` dans un `window.addEventListener('mousemove')`.

### Année 1970 dans le hero
`strtotime()` retournait epoch (1970) quand on lui donnait des strings comme "14/12/2024, Theater Orpheus, Apeldoorn".
**Fix :** Regex `preg_match('/\b(19|20)\d{2}\b/')` pour extraire l'année, fallback sur `date_creation`.

### Clignement des yeux SVG
On ne peut pas animer `scaleY(0)` sur un path SVG facilement.
**Fix :** Un deuxième path SVG `.galerie-eye__blink` (paupière fermée) superposé, avec `opacity: 0` par défaut. Le JS toggle l'opacité à 1 pour fermer l'oeil et cache pupille + iris en même temps.

### URLs de vidéos concaténées
Certaines pièces avaient 2 URLs YouTube collées sans séparateur dans `video_url` (ex: `https://youtu.be/ABChttps://youtu.be/XYZ`).
**Fix :** Le script de migration split sur `http` pour séparer les URLs.

---

## Fichiers clés modifiés/créés

| Fichier | Description |
|---------|-------------|
| `config/schema.sql` | +5 tables, +6 index, +2 colonnes (sous_titre, theatre_logo_url, theatre_id) |
| `config/import-from-wordpress-xml.php` | Import depuis export WXR WordPress |
| `config/migrate-v2.php` | Migration vers schema v2 (theatres, videos, critiques, collaborateurs) |
| `pages/piece.php` | Hero enrichi, logo flottant, galerie masonry, lightbox, yeux, clignement |
| `assets/css/piece.css` | +280 lignes (logo flottant, masonry, lightbox, yeux, glow) |
| `assets/js/piece.js` | initFloatingLogo placeholder (logique dans inline script) |
| `admin/` | 8 fichiers — admin complet CRUD |

## État actuel
- **49 pièces** en base (48 WordPress + 1 ajoutée manuellement)
- **1300 images** de galerie
- **44 vidéos**
- **43 théâtres**
- Serveur : `php -S localhost:8000` depuis le dossier `site/`
- Commit : `083160c`

## Prochaines étapes possibles
- Peupler les collaborateurs depuis les données des pièces
- Upload d'images dans l'admin (actuellement URLs manuelles)
- Ajouter les critiques/reviews (PDFs, textes)
- Pages du site : about, contact, theatres, collaborateurs
- Retirer les `border: 1px solid red` de debug sur la galerie
- Responsive mobile à tester/affiner
