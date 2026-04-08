# MealCoach V2 — Redesign Front-End Complet

**Date** : 8 avril 2026
**Projet** : Projet_09_MENU — MealCoach
**URL** : staging.agencepingpong.fr/menus/
**Stack** : PHP 8+ vanilla, SQLite3 (WAL), CSS vanilla, JS vanilla

---

## 1. Contexte

MealCoach V1 est fonctionnel (backend, API, auth, import .md) mais le front-end a été rejeté 3 fois. Cette V2 est une refonte complète de l'UX/UI basée sur les validations suivantes avec l'utilisateur :

- **Palette Pomegranate** (pastels chauds, fond crème)
- **Swipe horizontal par jour** (pas par repas)
- **5 moments quotidiens** : Petit-déj, Déjeuner, En-cas 16h, Dîner, Soirée
- **Cards compactes** avec noms de plats appétissants
- **SOS Craquage** avec technique 5-4-3-2-1 + alternatives anti-fringale
- **Rappels SMS J-2** via API Free Mobile (gratuit)
- **Pas d'accordéons**, pas de batch cooking, pas de tableaux nutritionnels visibles

---

## 2. Design System

### 2.1 Palette de couleurs (Pomegranate-inspired)

```css
--bg: #F5EDE3;           /* Fond page crème chaud */
--card: #FFFFFF;          /* Fond cards */
--text: #3D3232;          /* Texte principal */
--text-light: #8A7F7F;    /* Texte secondaire */
--text-muted: #B5ABAB;    /* Texte très discret */
--border: 1.5px solid rgba(80,50,50,0.08);
--shadow: 0 2px 16px rgba(80,50,50,0.07);
--radius: 22px;

/* Couleurs par type de repas */
--peach: #F5D5B8;         /* Petit-déjeuner */
--peach-deep: #D4954A;
--mint: #C6E2D4;          /* Déjeuner */
--mint-deep: #6BAB8A;
--lavender: #D4CCE6;      /* En-cas */
--lavender-deep: #9B87C4;
--sky: #C4DAF0;           /* Dîner */
--sky-deep: #6A9FD4;
--pink: #F2C4CE;          /* Soirée */
--pink-deep: #D4869A;

/* Actions */
--accent: #D4869A;        /* Boutons principaux */
--success: #6BAB8A;       /* Check / Mangé */
--danger: #D4696A;        /* SOS / Erreur */
```

### 2.2 Typographie
- **Font** : Inter (400, 500, 600, 700, 800)
- **Noms de plats** : 1.05rem, weight 700
- **Labels type repas** : 0.62rem, uppercase, letter-spacing 0.06em
- **Détails** : 0.78rem, color text-light

### 2.3 Composants réutilisables
- `.meal-row` : Card repas compacte avec color bar + emoji + nom + détail
- `.day-tab` : Pill jour cliquable (Lun 7, Mar 8...)
- `.day-swiper` : Container swipe horizontal par jour
- `.progress-card` : Anneau SVG + texte
- `.alert-card` : Notification J-2
- `.stat-block` : Bloc suivi (poids/humeur/énergie)
- `.btn-accent` / `.btn-ghost` : Boutons action
- `.done-check` : Cercle vert ✓
- `.sos-fab` : Bouton flottant SOS
- `.bottom-nav` : Navigation 5 items fixe en bas

---

## 3. Architecture des pages

### 3.1 Dashboard / Aujourd'hui (`front/dashboard.php`)
**URL** : `/`
**Nav active** : Accueil 🏠

**Contenu (de haut en bas)** :
1. Header : "Bonjour Jorge 👋" + date + badges (Semaine X, Saison)
2. Alerte J-2 (si applicable) : "Vendredi tu manges des sardines — tu les as ?"
3. Anneau de progression : X repas faits sur 5
4. Tabs jours (Lun-Dim) avec swipe par jour
5. 5 meal-rows par jour (petit-déj → soirée)
   - Repas passés faits : opacity 0.55 + done-check ✓
   - Repas à venir : bouton "C'est fait !" 
   - Tous les jours futurs : pas de boutons d'action
6. Section suivi rapide (poids/humeur/énergie) — seulement sur le jour actuel

**Swipe par jour** :
- `scroll-snap-type: x mandatory` sur le container
- Chaque `.day-panel` = `flex: 0 0 100%`
- Tabs se synchronisent avec le scroll
- Auto-scroll vers aujourd'hui au chargement

### 3.2 Semaine (`front/semaine.php`)
**URL** : `/semaine`
**Nav active** : Semaine 📅

Même layout que le dashboard mais sans le header personnel, le progress ring et le suivi. Vue pure planning :
1. Header : "Semaine X" + dates + saison
2. Tabs jours + swipe par jour
3. 5 meal-rows par jour (lecture seule, pas de boutons "C'est fait")

### 3.3 Composer (`front/compositeur.php`)
**URL** : `/compositeur`
**Nav active** : Composer ⭐

**Flux en 3 étapes** :
1. **Choix du moment** : 5 boutons (Petit-déj / Déj / Dîner / En-cas / Soirée)
2. **Sélecteurs adaptés au moment** :
   - Petit-déj : Laitage | Céréale | Protéine (dropdowns catégorisés)
   - Déj/Dîner : Légumes | Protéine | Fromage | Fruit | Féculent
   - En-cas : Sélection anti-fringale (tryptophane/sérotonine)
   - Soirée : Choix rituel
   - Chaque dropdown : produits de saison en premier (🌱), quantités pré-remplies
3. **Résultat** : Card du plat composé + bouton "Ajouter au menu de [jour]"

### 3.4 Courses (`front/courses.php`)
**URL** : `/courses`
**Nav active** : Courses 🛒

**Contenu** :
1. Header "Liste de courses — Semaine X"
2. **Rappels J-2** : Cards par repas dans 2 jours
   - "Après-demain (jeudi) : Sardines, riz & tomates"
   - Liste ingrédients avec double checkbox : ☐ J'ai | ☐ J'ai pas
   - Bouton "Ajouter les manquants à ma liste"
3. **Liste courses** groupée par rayon :
   - Protéines | Laitages | Légumes | Fruits | Féculents | Épicerie
   - Chaque item : checkbox cochable

### 3.5 SOS Craquage (overlay/modal)
**Accessible depuis** : bouton FAB flottant sur toutes les pages

**Parcours guidé** :
1. **Écran 1** : "Stop. Respire." — message bienveillant
2. **Écran 2** : Deux chemins
   - 🧠 "Gérer l'émotion" → Technique 5-4-3-2-1 (5 écrans successifs)
   - 🍫 "Alternative anti-fringale" → Liste suggestions tryptophane/sérotonine
3. **Écran 3** : Mini-slider humeur + bilan
   - "💪 J'ai résisté" → message positif + tracking
   - "J'ai craqué" → enregistrement sans jugement

### 3.6 Plus (bottom sheet existant)
Via le menu "•••" :
- 📊 Suivi détaillé (`/suivi`)
- 📈 Tableau de référence (`/tableau`)
- ⚙️ Back office (`/admin`)
- 🚪 Déconnexion

---

## 4. Rappels SMS Free Mobile

### 4.1 Configuration
Nouvelle table `settings` (ou réutiliser existante) :
- `free_mobile_user` : identifiant Free Mobile
- `free_mobile_pass` : clé API SMS

### 4.2 Logique
Fichier `src/cron/rappel-sms.php` :
1. Calcule la date J+2
2. Cherche les repas de ce jour dans `menu_repas`
3. Construit le message : "Après-demain (jeudi) tu manges : Sardines, riz complet, tomates. Tu as tout ?"
4. Appelle `https://smsapi.free-mobile.fr/sendmsg?user=X&pass=Y&msg=Z`

### 4.3 Cron
Sur o2switch, cron quotidien à 19h :
```
0 19 * * * php /home/user/public_html/menus/src/cron/rappel-sms.php
```

---

## 5. Modifications backend

### 5.1 Type de repas "soiree"
Le type `dessert` dans la BDD et le parser est renommé en `soiree` pour refléter le concept de rituel anti-craquage du soir (pas juste un dessert).

### 5.2 API suivi — SOS tracking
Ajouter à l'API suivi (`src/api/suivi.php`) :
- Action `sos_event` : enregistre un événement SOS (résisté/craqué + quoi + timestamp)
- Nouvelle table ou colonne dans `suivi_jours`

### 5.3 Nom de plat
Le champ `nom_plat` dans `menu_repas` contient déjà le nom. Le parser doit s'assurer de générer des noms appétissants (pas juste la liste d'ingrédients). Le format .md reste identique.

---

## 6. Fichiers impactés

### À réécrire complètement :
- `assets/css/app.css` — Nouveau design system Pomegranate
- `assets/js/app.js` — Swipe, SOS overlay, interactions
- `front/layout.php` — Nouvelle nav + SOS FAB + structure
- `front/dashboard.php` — Swipe par jour + progress + suivi
- `front/semaine.php` — Swipe par jour (lecture seule)
- `front/compositeur.php` — Flux 3 étapes avec sélecteurs
- `front/courses.php` — J-2 + double checkbox + liste rayons

### À créer :
- `front/sos.php` — Contenu de l'overlay SOS (ou inline dans layout)
- `src/cron/rappel-sms.php` — Cron SMS Free Mobile

### Inchangés :
- `config.php`, `auth.php`, `index.php` (router), `src/db.php`
- `src/parser.php`, `src/models/*`, `src/api/*`
- `admin/*` — Le back-office ne change pas
- `install.php`, `data/`, `content/`

---

## 7. Contraintes techniques

- **PHP vanilla** — pas de framework, pas de npm, pas de build
- **SQLite3** — WAL mode, pas de migration, schéma existant
- **Mobile-first** — max-width 430px, safe-area, touch-friendly
- **Performance** — CSS inline minimal, pas de librairie externe (sauf Inter font)
- **Hébergement** — o2switch, cPanel, pas de Node, pas de composer en prod
- **Accents français** — Pas d'accents dans le code (htmlspecialchars pour l'affichage)
