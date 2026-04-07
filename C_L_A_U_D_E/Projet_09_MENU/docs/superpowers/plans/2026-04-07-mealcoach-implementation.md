# MealCoach Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a mobile-first personal nutrition coaching web app that reads weekly menus from Markdown files, manages grocery lists, stock, meal tracking, and meal composition with real-time nutritional validation.

**Architecture:** PHP vanilla router serves front office (consultation mobile) and back office (gestion) views. SQLite stores all persistent data (catalogue, stock, suivi, courses). Weekly menus are imported from standardized `.md` files, parsed and stored in DB. All interactions use vanilla JS with fetch() for AJAX calls to PHP API endpoints returning JSON.

**Tech Stack:** PHP 8+, SQLite3 (PDO), Parsedown (MD parser), vanilla HTML/CSS/JS, hosted on o2switch.

**Spec:** `docs/superpowers/specs/2026-04-07-mealcoach-design.md`

---

## Phase 1 — Foundation (Tasks 1-5)

### Task 1: Project scaffold, config, .htaccess

**Files:**
- Create: `config.php`
- Create: `.htaccess`
- Create: `data/.htaccess`
- Create: `content/menus/.gitkeep`

- [ ] Create `config.php` with constants: BASE_PATH, DB_PATH, CONTENT_PATH, BASE_URL, session config, timezone Europe/Paris
- [ ] Create root `.htaccess`: force HTTPS, protect data/src/vendor/content dirs, rewrite to index.php, security headers, compression
- [ ] Create `data/.htaccess` with `Deny from all`
- [ ] Create `content/menus/.gitkeep`
- [ ] Commit

### Task 2: SQLite schema + DB helper

**Files:**
- Create: `src/db.php`
- Create: `install.php`

- [ ] Create `src/db.php` with PDO singleton, helpers: `query()`, `fetchAll()`, `fetchOne()`, `insert()`, `update()`, `getSetting()`, `setSetting()`. WAL mode, foreign keys ON.
- [ ] Create `install.php` that creates all 13 tables from the spec (settings, produits, equivalences, regles, stock, semaines, menu_jours, menu_repas, batch_taches, listes_courses, liste_items, suivi_jours, suivi_repas, repas_composes, repas_compose_items) + all indexes
- [ ] Add seed data in install.php: ~80 produits from the PDF tableau, ~60 equivalences, ~15 regles nutritionnelles, default settings (budget=50, saison=printemps, password hash)
- [ ] Test: `php install.php` creates the DB successfully
- [ ] Commit

### Task 3: Auth system

**Files:**
- Create: `auth.php`

- [ ] Create `auth.php` with: `generateCsrfToken()`, `verifyCsrfToken()`, `csrfField()`, `isLoggedIn()`, `requireLogin()`, `login()`, `logout()`. Uses session PHP + `password_verify`.
- [ ] Commit

### Task 4: Router + layouts

**Files:**
- Create: `index.php`
- Create: `front/layout.php`
- Create: `front/login.php`
- Create: `admin/layout.php`

- [ ] Create `index.php` router: maps routes to files. Public routes (front/), admin routes (require login), API routes (return JSON). Handles login POST + logout.
- [ ] Create `front/layout.php`: mobile first HTML template with bottom nav (Dashboard, Semaine, Courses, Composer, Plus). Plus menu overlay with links to stock, tableau, suivi, batch, admin.
- [ ] Create `front/login.php`: simple password form with CSRF.
- [ ] Create `admin/layout.php`: mobile first admin template with bottom nav (Stats, Import, Produits, Stock, Histo, Config). Header with back-to-front link.
- [ ] Commit

### Task 5: CSS mobile first + JS base

**Files:**
- Create: `assets/css/app.css`
- Create: `assets/js/app.js`

- [ ] Create `app.css`: CSS variables (--primary:#2d6a4f, --accent:#d4a373, etc.), reset, typography, layout (.page-content max-width:600px), cards, bottom-nav (fixed, 64px), plus-menu overlay, buttons, forms, alerts, accordion, tabs (horizontal scroll), checklist items, progress bar, badges, meal items, sliders, timeline, sticky-bottom, composer options/status, admin header, search input, utility classes.
- [ ] Create `app.js`: `togglePlusMenu()`, click-outside close, accordion toggle, `api()` fetch helper, checklist click toggle with API persist, `updateProgress()`, slider value display, DOMContentLoaded init.
- [ ] Commit

---

## Phase 2 — MD Parser + Models (Tasks 6-8)

### Task 6: Parsedown + MD parser

**Files:**
- Create: `vendor/Parsedown.php` (download)
- Create: `src/parser.php`

- [ ] Download Parsedown.php from GitHub into vendor/
- [ ] Create `src/parser.php` with `MenuParser` class. Methods: `parse($content)` returns array with metadata/batch/jours/courses/budget. `parseMetadata()` extracts semaine number, saison, dates from `# SEMAINE X — SAISON — Dates`. `parseBatch()` extracts table rows (heure/action/equipement). `parseJours()` extracts each day (LUNDI-DIMANCHE) and each meal type by emoji markers. `parseCourses()` extracts grocery list by category. `parseBudget()` extracts total.
- [ ] Commit

### Task 7: Models — Produit, Stock, Courses

**Files:**
- Create: `src/models/Produit.php`
- Create: `src/models/Stock.php`
- Create: `src/models/Courses.php`

- [ ] Create `Produit.php`: `getAll()`, `getByCategorie()`, `getById()`, `search()`, `create()`, `update()`, `exclure()`, `inclure()`, `exclureParNom()` (auto-create if unknown with exclu=1), `getCategories()`.
- [ ] Create `Stock.php`: `getAll()` (JOIN produits), `getByProduit()`, `ajouter()` (increment if exists), `retirer()` (delete if qty<=0), `setQuantite()`, `alertesPeremption()`, `estEnStock()`.
- [ ] Create `Courses.php`: `getListeBySemaine()`, `getItemsBySemaine()`, `creerDepuisParsing()` (from parser output, match produits, check stock), `toggleAchete()`, `ajouterItem()`, `statsListe()`.
- [ ] Commit

### Task 8: Models — Suivi, Compositeur

**Files:**
- Create: `src/models/Suivi.php`
- Create: `src/models/Compositeur.php`

- [ ] Create `Suivi.php`: `getJour()`, `getOuCreerJour()`, `majJour()`, `getRepas()`, `majRepas()`, `getHistorique()`, `statsRepas()`.
- [ ] Create `Compositeur.php`: `getEquivalences($typeRepas)` grouped by category, `getRegles($typeRepas)`, `valider($typeRepas, $selections)` checks min/max per category, `sauvegarder()`, `getFavoris()`.
- [ ] Commit

---

## Phase 3 — API Endpoints (Task 9)

### Task 9: All API endpoints

**Files:**
- Create: `src/api/suivi.php`
- Create: `src/api/stock.php`
- Create: `src/api/courses.php`
- Create: `src/api/produits.php`
- Create: `src/api/compositeur.php`

- [ ] Create `src/api/suivi.php`: GET returns jour+repas for date. POST actions: `maj_jour` (poids/humeur/energie/sommeil/note), `maj_repas` (type_repas/statut/detail).
- [ ] Create `src/api/stock.php`: GET returns all stock. POST actions: `ajouter`, `retirer`, `set`.
- [ ] Create `src/api/courses.php`: GET returns items+stats by semaine_id. POST actions: `toggle` (achete), `ajouter` (manual item).
- [ ] Create `src/api/produits.php`: GET with search/filter. POST actions: `create`, `update`, `exclure`, `inclure`, `exclure_par_nom`.
- [ ] Create `src/api/compositeur.php`: GET returns equivalences+regles by type_repas. POST actions: `valider`, `sauvegarder`, `favoris`.
- [ ] Commit

---

## Phase 4 — Front Office Views (Tasks 10-12)

### Task 10: Front dashboard + semaine

**Files:**
- Create: `front/dashboard.php`
- Create: `front/semaine.php`
- Create: `front/jour.php`

- [ ] Create `front/dashboard.php`: date+jour, card menu du jour (5 repas with status badges), card suivi rapide (3 sliders poids/humeur/energie + save button via API), card alertes stock peremption. Uses front/layout.php.
- [ ] Create `front/semaine.php`: horizontal tab nav (Lun-Dim), selected day shows meals in accordion. Each meal expandable with content + Mange/Saute buttons that call suivi API.
- [ ] Create `front/jour.php`: redirects to semaine with correct day param.
- [ ] Commit

### Task 11: Front batch + courses + stock

**Files:**
- Create: `front/batch.php`
- Create: `front/courses.php`
- Create: `front/stock.php`

- [ ] Create `front/batch.php`: vertical timeline of batch tasks (heure/action/equipement), each with checkbox. Recap frigo at bottom.
- [ ] Create `front/courses.php`: items grouped by rayon, each with checkbox (persisted via API), in-stock items grayed with badge. Progress bar top, total sticky bottom. "Ajouter un article" button.
- [ ] Create `front/stock.php`: stock items grouped by category, quantities, peremption alerts. Link to admin/stock for management.
- [ ] Commit

### Task 12: Front compositeur + tableau + suivi

**Files:**
- Create: `front/compositeur.php`
- Create: `front/tableau-reference.php`
- Create: `front/suivi.php`

- [ ] Create `front/compositeur.php`: step 1 = choose meal type (buttons), step 2 = load equivalences from API grouped by category, click to select options, real-time validation via API (green/red status dots per category), sticky bar with validation status + save button (prompt for name, saves as favori).
- [ ] Create `front/tableau-reference.php`: all equivalences by category (Laitages, Cereales, Proteines PDJ, Viandes/Poissons, Fromages, Fruits, Sucres lents, MG) with search filter. Cuissons in accordion (Viandes, Poissons, Oeufs, Legumes).
- [ ] Create `front/suivi.php`: full day tracking. Repas section with 3 buttons per meal (Mange/Saute/Craquage). Bien-etre section with 4 sliders (poids/humeur/energie/sommeil) + note textarea + save button.
- [ ] Commit

---

## Phase 5 — Back Office Views (Tasks 13-14)

### Task 13: Admin import + dashboard + semaines

**Files:**
- Create: `admin/import.php`
- Create: `admin/dashboard.php`
- Create: `admin/semaines.php`

- [ ] Create `admin/import.php`: upload .md file OR paste content. Preview mode shows parsed data (nb jours, nb taches batch, nb courses, budget). Validate button: archives previous active week, inserts semaine+menu_jours+menu_repas+batch_taches, generates liste_courses via Courses model, saves .md file to content/menus/.
- [ ] Create `admin/dashboard.php`: active week info, stats (repas manges/total, craquages count, progress bar), dernier poids, action buttons (import, catalogue, stock).
- [ ] Create `admin/semaines.php`: list all weeks ordered by date desc, each card shows numero/saison/dates/statut/budget/import date.
- [ ] Commit

### Task 14: Admin catalogue + stock + historique + settings

**Files:**
- Create: `admin/catalogue.php`
- Create: `admin/stock.php`
- Create: `admin/historique.php`
- Create: `admin/settings.php`

- [ ] Create `admin/catalogue.php`: search input, category tabs (horizontal scroll), product list with exclu/inclure buttons. "Retirer un produit" form at bottom (auto-creates if unknown). "Ajouter" button with prompts.
- [ ] Create `admin/stock.php`: product list with +/- buttons to adjust quantity. "Ajouter au stock" flow (search product, set qty, optional peremption). Peremption alerts.
- [ ] Create `admin/historique.php`: poids evolution (CSS bar chart), bien-etre data (humeur/energie/sommeil per day), craquages list (date/type/detail).
- [ ] Create `admin/settings.php`: form with budget max, poids objectif, saison select, new password. CSRF protected, saves via setSetting().
- [ ] Commit

---

## Phase 6 — Test + Deploy (Tasks 15-17)

### Task 15: Sample .md test file

**Files:**
- Create: `content/menus/semaine_01_du_07_au_13_avril_2026.md`

- [ ] Create the sample week 1 .md file using data from RECAP_Projet_09_MENU.md. Must follow the exact format: emoji markers, batch cooking table, 7 days with 5 meals each, courses list by category, budget total.
- [ ] Commit

### Task 16: .gitignore + cleanup

**Files:**
- Create: `.gitignore`

- [ ] Create `.gitignore`: data/mealcoach.sqlite, .DS_Store, .superpowers/
- [ ] Verify all files present
- [ ] Commit

### Task 17: Deploy to o2switch

- [ ] Create GitHub repo: `gh repo create mealcoach --private --source=. --push`
- [ ] Upload to staging.agencepingpong.fr/menus/ (git pull or FTP)
- [ ] Run install.php in browser
- [ ] Delete install.php from server
- [ ] Test: login, import .md, dashboard, semaine, courses, compositeur, suivi, admin
- [ ] Final commit + push
