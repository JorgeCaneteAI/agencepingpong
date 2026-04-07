# MealCoach — Spec de design

**Date** : 7 avril 2026
**Projet** : Application web de coaching nutritionnel personnel
**URL** : `staging.agencepingpong.fr/menus/`
**Utilisateur** : Jorge (unique utilisateur)

---

## 1. Vision

Application web mobile first de gestion nutritionnelle complète. L'app sert de coach au quotidien : des courses a la realisation des repas, en passant par le suivi et la composition de menus personnalises.

Le contenu des menus est genere par Claude sous forme de fichiers Markdown (`.md`) pousses manuellement dans un dossier. L'app parse, affiche et exploite ces fichiers. Tout le reste (stock, suivi, catalogue, compositeur) est gere par un backend PHP + SQLite.

---

## 2. Contraintes

- **Mobile first** : consultation principale sur telephone (cuisine, supermarche)
- **Back office egalement mobile**
- **1 personne** : portions individuelles, login unique
- **Budget** : < 50EUR/semaine
- **Saison** : printemps/Provence au lancement, evolutif
- **Hebergement** : o2switch (PHP + SQLite natif)
- **Pas de framework JS** : vanilla JS uniquement
- **Style culinaire** : cuisine francaise du quotidien, mediterraneenne, influences world legeres

---

## 3. Stack technique

| Element | Choix |
|---------|-------|
| Backend | PHP vanilla |
| BDD | SQLite (fichier unique `mealcoach.sqlite`) |
| Parser MD | Parsedown (lib PHP) + regex custom |
| Front | HTML / CSS / JS vanilla |
| Auth | Session PHP, login simple |
| Hebergement | o2switch — `staging.agencepingpong.fr/menus/` |
| Versioning | GitHub (nouveau repo) |
| Contenu menus | Fichiers `.md` dans `/content/menus/` |

---

## 4. Architecture fichiers

```
/menus/
|-- index.php                  # Router principal
|-- config.php                 # Config BDD, chemins, constantes
|-- auth.php                   # Login simple (session PHP)
|
|-- content/
|   +-- menus/                 # Fichiers .md pousses ici
|       |-- semaine_01_du_07_au_13_avril_2026.md
|       +-- semaine_02_du_14_au_20_avril_2026.md
|
|-- data/
|   +-- mealcoach.sqlite       # BDD unique
|
|-- src/
|   |-- parser.php             # Parse les fichiers .md
|   |-- db.php                 # Connexion SQLite + helpers
|   |-- models/
|   |   |-- Produit.php        # Catalogue + exclusions
|   |   |-- Stock.php          # Garde-manger
|   |   |-- Suivi.php          # Poids, humeur, craquages
|   |   |-- Courses.php        # Liste de courses
|   |   +-- Compositeur.php    # Validation regles nutritionnelles
|   +-- api/                   # Endpoints AJAX (JSON)
|       |-- suivi.php
|       |-- stock.php
|       |-- courses.php
|       |-- produits.php
|       +-- compositeur.php
|
|-- front/                     # FRONT OFFICE
|   |-- layout.php             # Template HTML mobile first
|   |-- dashboard.php          # Vue du jour
|   |-- semaine.php            # Menu semaine navigation jour/jour
|   |-- jour.php               # Detail d'un jour
|   |-- batch.php              # Timeline batch cooking
|   |-- courses.php            # Liste courses cochable
|   |-- stock.php              # Consulter le garde-manger
|   |-- compositeur.php        # Composer un repas
|   |-- tableau-reference.php  # Equivalences nutritionnelles
|   +-- suivi.php              # Saisie rapide du jour
|
|-- admin/                     # BACK OFFICE
|   |-- layout.php             # Template admin (aussi mobile)
|   |-- dashboard.php          # Stats, vue globale
|   |-- catalogue.php          # Gerer produits
|   |-- stock.php              # Gerer le garde-manger
|   |-- import.php             # Uploader les fichiers .md
|   |-- semaines.php           # Historique des semaines importees
|   |-- historique.php         # Suivi poids/humeur/tendances
|   +-- settings.php           # Reglages
|
|-- assets/
|   |-- css/
|   |   +-- app.css            # Styles mobile first (front + back)
|   +-- js/
|       +-- app.js             # Interactivite
|
+-- .htaccess                  # Rewrites, protection data/
```

---

## 5. Schema BDD SQLite

### 5.1 settings

```sql
CREATE TABLE settings (
    cle TEXT PRIMARY KEY,
    valeur TEXT NOT NULL
);
```

Cles : `budget_max`, `poids_objectif`, `saison`, `mot_de_passe_hash`.

### 5.2 produits

```sql
CREATE TABLE produits (
    id INTEGER PRIMARY KEY,
    nom TEXT NOT NULL UNIQUE,
    categorie TEXT NOT NULL,
    sous_categorie TEXT,
    unite_mesure TEXT DEFAULT 'g',
    prix_unitaire REAL,
    unite_achat TEXT DEFAULT 'kg',
    saisons TEXT,                      -- JSON array
    tryptophane INTEGER DEFAULT 0,
    exclu INTEGER DEFAULT 0,
    note TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);
```

Categories : `legume`, `fruit`, `proteine`, `feculent`, `laitage`, `fromage`, `epicerie`, `condiment`, `cereale`, `matiere_grasse`, `boisson`.

Sous-categories (pour proteines) : `viande`, `poisson`, `oeuf`, `charcuterie`.

**Logique d'exclusion** : si un produit est mis dans la liste "a retirer" et n'existe pas encore, il est cree automatiquement avec `exclu=1`.

### 5.3 equivalences

```sql
CREATE TABLE equivalences (
    id INTEGER PRIMARY KEY,
    categorie TEXT NOT NULL,
    description TEXT NOT NULL,
    quantite REAL,
    unite TEXT,
    produit_id INTEGER REFERENCES produits(id),
    moment TEXT,                       -- 'petit_dej','repas','tous'
    est_non_raffine INTEGER DEFAULT 0
);
```

Contient le tableau PDF nutritionnel transforme en donnees structurees. Chaque ligne = une option de portion valide.

### 5.4 regles

```sql
CREATE TABLE regles (
    id INTEGER PRIMARY KEY,
    type_repas TEXT NOT NULL,
    categorie TEXT NOT NULL,
    quantite_min INTEGER DEFAULT 0,
    quantite_max INTEGER NOT NULL,
    grammage TEXT,
    note TEXT
);
```

Exemples :
- dejeuner / sucre_lent / min=2 / max=2 / grammage="100g cuits"
- diner / sucre_lent / min=1 / max=1 / grammage="50g cuits"
- dejeuner / proteine / min=1 / max=1
- tous / matiere_grasse / min=1 / max=1 / grammage="1 CaS"

### 5.5 stock

```sql
CREATE TABLE stock (
    id INTEGER PRIMARY KEY,
    produit_id INTEGER NOT NULL REFERENCES produits(id),
    quantite REAL NOT NULL,
    unite TEXT NOT NULL,
    date_peremption TEXT,
    updated_at TEXT DEFAULT (datetime('now'))
);
```

### 5.6 semaines

```sql
CREATE TABLE semaines (
    id INTEGER PRIMARY KEY,
    fichier TEXT NOT NULL UNIQUE,
    numero INTEGER NOT NULL,
    date_debut TEXT NOT NULL,
    date_fin TEXT NOT NULL,
    saison TEXT NOT NULL,
    budget_estime REAL,
    statut TEXT DEFAULT 'active',
    contenu_brut TEXT,
    imported_at TEXT DEFAULT (datetime('now'))
);
```

### 5.7 menu_jours + menu_repas

```sql
CREATE TABLE menu_jours (
    id INTEGER PRIMARY KEY,
    semaine_id INTEGER NOT NULL REFERENCES semaines(id),
    jour INTEGER NOT NULL,             -- 0=lundi ... 6=dimanche
    date TEXT NOT NULL,
    UNIQUE(semaine_id, jour)
);

CREATE TABLE menu_repas (
    id INTEGER PRIMARY KEY,
    menu_jour_id INTEGER NOT NULL REFERENCES menu_jours(id),
    type_repas TEXT NOT NULL,
    nom_plat TEXT,
    contenu TEXT NOT NULL,             -- bloc MD brut
    source TEXT DEFAULT 'import',      -- 'import' ou 'compose'
    UNIQUE(menu_jour_id, type_repas)
);
```

### 5.8 batch_taches

```sql
CREATE TABLE batch_taches (
    id INTEGER PRIMARY KEY,
    semaine_id INTEGER NOT NULL REFERENCES semaines(id),
    ordre INTEGER NOT NULL,
    heure TEXT,
    action TEXT NOT NULL,
    equipement TEXT,
    duree INTEGER,
    resultat TEXT
);
```

### 5.9 listes_courses + liste_items

```sql
CREATE TABLE listes_courses (
    id INTEGER PRIMARY KEY,
    semaine_id INTEGER NOT NULL REFERENCES semaines(id),
    cout_estime REAL,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE liste_items (
    id INTEGER PRIMARY KEY,
    liste_id INTEGER NOT NULL REFERENCES listes_courses(id),
    produit_id INTEGER REFERENCES produits(id),
    nom_brut TEXT,
    quantite REAL NOT NULL,
    unite TEXT NOT NULL,
    categorie_rayon TEXT,
    prix_estime REAL,
    en_stock INTEGER DEFAULT 0,
    achete INTEGER DEFAULT 0,
    ajout_manuel INTEGER DEFAULT 0
);
```

### 5.10 suivi_jours + suivi_repas

```sql
CREATE TABLE suivi_jours (
    id INTEGER PRIMARY KEY,
    date TEXT NOT NULL UNIQUE,
    poids REAL,
    humeur INTEGER CHECK(humeur BETWEEN 1 AND 5),
    energie INTEGER CHECK(energie BETWEEN 1 AND 5),
    sommeil INTEGER CHECK(sommeil BETWEEN 1 AND 5),
    note TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE suivi_repas (
    id INTEGER PRIMARY KEY,
    suivi_jour_id INTEGER NOT NULL REFERENCES suivi_jours(id),
    menu_repas_id INTEGER REFERENCES menu_repas(id),
    type_repas TEXT NOT NULL,
    statut TEXT DEFAULT 'prevu',
    modification TEXT,
    craquage_detail TEXT,
    heure TEXT,
    UNIQUE(suivi_jour_id, type_repas)
);
```

Statuts : `prevu`, `mange`, `saute`, `modifie`, `craquage`.

### 5.11 repas_composes + repas_compose_items

```sql
CREATE TABLE repas_composes (
    id INTEGER PRIMARY KEY,
    nom TEXT,
    type_repas TEXT NOT NULL,
    date TEXT NOT NULL,
    favori INTEGER DEFAULT 0,
    valide INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE repas_compose_items (
    id INTEGER PRIMARY KEY,
    repas_id INTEGER NOT NULL REFERENCES repas_composes(id),
    equivalence_id INTEGER REFERENCES equivalences(id),
    produit_id INTEGER REFERENCES produits(id),
    quantite REAL,
    unite TEXT,
    categorie TEXT NOT NULL
);
```

### 5.12 Index

```sql
CREATE INDEX idx_produits_categorie ON produits(categorie);
CREATE INDEX idx_produits_exclu ON produits(exclu);
CREATE INDEX idx_stock_produit ON stock(produit_id);
CREATE INDEX idx_semaines_date ON semaines(date_debut);
CREATE INDEX idx_menu_jours_date ON menu_jours(date);
CREATE INDEX idx_suivi_date ON suivi_jours(date);
CREATE INDEX idx_liste_items_liste ON liste_items(liste_id);
CREATE INDEX idx_equivalences_cat ON equivalences(categorie);
CREATE INDEX idx_regles_type ON regles(type_repas);
```

---

## 6. Format des fichiers Markdown

### Nommage

```
semaine_XX_du_JJ_au_JJ_mois_AAAA.md
```

Exemple : `semaine_01_du_07_au_13_avril_2026.md`

### Structure interne

```markdown
# SEMAINE X -- SAISON -- Dates
## Metadonnees : saison / dates / budget estime

## BATCH COOKING -- DIMANCHE MATIN
| Heure | Action | Equipement | Resultat |
(timeline tableau)
### Recap frigo
(liste des boites)

## LUNDI
### Petit dejeuner
### Dejeuner
### En-cas 16h
### Diner
### Dessert

(idem pour chaque jour jusqu'a DIMANCHE)

## LISTE DE COURSES
### Proteines
### Laitages
### Legumes
### Fruits
### Feculents & legumineuses
### Epicerie seche
### Epices & condiments

## Budget estime total
```

Les emojis dans les titres de repas servent de marqueurs de parsing.

### Parsing

Le parser PHP extrait chaque bloc via regex sur les titres `##` et `###`. Les donnees parsees sont stockees en BDD (tables `menu_jours`, `menu_repas`, `batch_taches`, `liste_items`) pour exploitation rapide. Le MD brut est aussi conserve dans `semaines.contenu_brut`.

---

## 7. Design des ecrans

### 7.1 Front Office

#### Dashboard (page d'accueil)
- Date du jour + numero de semaine
- Card "Menu du jour" : 5 repas avec statut (prevu/mange/saute)
- Card "Batch cooking" : si dimanche, affiche la timeline
- Card "Stock" : alertes peremption + produits bientot vides
- Card "Suivi rapide" : poids/humeur/energie en 3 sliders + bouton sauver
- **Navigation bottom** : Dashboard / Semaine / Courses / Composer / Plus

#### Vue Semaine
- Navigation horizontale swipeable : Lun Mar Mer Jeu Ven Sam Dim
- Chaque jour affiche les 5 blocs repas en accordeon
- Tap sur un repas : detail + bouton "Mange" / "Saute" / "Modifie"

#### Batch Cooking
- Timeline verticale scrollable
- Chaque tache : heure, action, equipement, duree
- Checkbox pour cocher "fait"
- En bas : recap frigo (boites preparees)

#### Liste de Courses
- Groupee par rayon (Proteines, Legumes, Fruits, etc.)
- Chaque item : case a cocher + nom + quantite + prix estime
- Items deja en stock grises avec badge "En stock"
- Barre de progression en haut (X/Y achetes)
- Total estime en bas sticky
- Bouton "Ajouter un article"

#### Compositeur de Repas
- Etape 1 : choisir le type (petit dej / dejeuner / diner / encas / dessert)
- Etape 2 : selectionner dans chaque categorie du tableau
- Validation temps reel : jauge verte/rouge par categorie
- Resultat : recap du repas compose + bouton "Sauvegarder en favori"

#### Tableau de Reference
- Le PDF en version interactive
- Navigation par onglets : Laitages / Cereales / Proteines / Legumes / Viandes / Fromages / Fruits / Sucres lents / Cuissons / MG
- Recherche rapide en haut

#### Suivi du Jour
- Mode rapide : cocher les repas (mange/saute/craquage)
- Mode detaille : modifier ce qui a ete mange
- Sliders : poids, humeur, energie, sommeil
- Zone note libre

### 7.2 Back Office

#### Dashboard Admin
- Semaine en cours + statut
- Stats : % repas respectes, craquages, evolution poids (mini graphe)
- Derniere semaine importee + bouton "Importer"

#### Import .md
- Upload fichier ou coller le contenu
- Preview du parsing avant validation
- Bouton "Valider et activer"

#### Catalogue Produits
- Liste searchable + filtres par categorie
- Chaque produit : nom, categorie, prix, saison, statut (actif/exclu)
- Swipe gauche pour exclure, swipe droit pour modifier
- Bouton "+" pour ajouter
- Toggle "Voir les exclus"
- **Auto-creation** : si un produit est ajoute a la liste "a retirer" et n'existe pas, il est cree avec `exclu=1`

#### Gestion Stock
- Liste des produits en stock avec quantite
- Bouton "+/-" pour ajuster rapidement
- Alerte visuelle si peremption proche
- Bouton "Inventaire rapide" : checklist du catalogue, cocher ce qu'on a

#### Historique / Stats
- Graphe evolution poids (courbe)
- Graphe humeur / energie / sommeil (barres)
- Calendrier des craquages
- Taux de respect des menus par semaine

#### Reglages
- Budget hebdo max
- Objectif poids
- Saison courante
- Mot de passe

---

## 8. Navigation

### Front Office — Bottom nav (5 items)
1. Dashboard
2. Semaine
3. Courses
4. Composer
5. Plus (stock, tableau reference, suivi, lien vers back office)

### Back Office — Bottom nav (6 items)
1. Dashboard admin
2. Import
3. Catalogue
4. Stock
5. Historique
6. Reglages

Acces au back office via le menu "Plus" du front office, protege par login.

---

## 9. Regles nutritionnelles (source : tableau PDF)

### Petit dejeuner
- 1 laitage AU CHOIX
- 1 cereale AU CHOIX
- 1 proteine AU CHOIX (optionnel)
- Boisson chaude

### Dejeuner
- Legumes a volonte
- 1 proteine (150g viande / 200g poisson blanc / 2 oeufs / 80g jambon / 150g thon)
- 2 sucres lents = 100g feculent cuit (non raffine = 5 CaS, raffine = 4 CaS)
- 1 laitage OU 1 fromage
- 1 fruit
- 1 matiere grasse (1 CaS huile / 2 CaS margarine 40% / 3 CaS creme 5-20%)

### Diner
- Legumes a volonte
- 1 proteine (memes regles)
- 1 sucre lent = 50g feculent cuit
- 1 laitage OU 1 fromage
- 1 matiere grasse

### En-cas 16h
- Combinaison libre type : oleagineux + fruit, ou laitage + fruit

### Dessert / Apres souper
- Tisane + 2 carres chocolat noir 85% (anti-craquage)
- OU 1 datte + 1 amande

### Anti-fringales
Aliments riches en tryptophane (precurseur serotonine) : oeufs, volaille, thon, sardines, yaourt grec, noix, chocolat noir. Au moins 2 par jour dans le menu.

### Cuissons autorisees
- Viandes : grill, poele Teflon, broche, four, cocotte, brochette
- Poissons : court-bouillon, four, papillote, grille, poele, citron marine
- Oeufs : dur, coque, mollet, poche, omelette Teflon, brouilles bain-marie
- Legumes : vapeur, autocuiseur, four, farcis, etouffe

---

## 10. Flux principaux

### Flux 1 : Nouvelle semaine
1. Claude genere le fichier `.md` de la semaine
2. Jorge uploade le `.md` via le back office (Import)
3. Le parser extrait les donnees et peuple `menu_jours`, `menu_repas`, `batch_taches`
4. La liste de courses est generee automatiquement dans `liste_items`
5. Les items deja en stock sont marques `en_stock=1`
6. La semaine passe en statut `active`

### Flux 2 : Courses au supermarche
1. Jorge ouvre la liste de courses sur son telephone
2. Les articles en stock sont grises
3. Il coche chaque article achete
4. Les coches sont persistees en BDD
5. A son retour, il peut mettre a jour le stock depuis les achats

### Flux 3 : Dimanche batch cooking
1. Jorge ouvre la vue Batch Cooking
2. Il suit la timeline etape par etape
3. Il coche chaque tache terminee
4. Le recap frigo s'affiche en bas

### Flux 4 : Quotidien
1. Le matin : consulte le dashboard, voit les repas du jour
2. A chaque repas : marque "mange", "saute" ou "craquage"
3. Le soir : saisie rapide poids/humeur/energie
4. Les donnees alimentent les stats dans l'historique

### Flux 5 : Composer un repas
1. Jorge choisit le type de repas
2. Il pioche dans les equivalences du tableau
3. L'app valide en temps reel (regles respectees ou non)
4. Il peut sauvegarder en favori pour reutilisation
5. Le repas compose peut remplacer un repas du menu

### Flux 6 : Gestion catalogue
1. Jorge ajoute un produit : saisie nom + categorie + prix
2. Jorge exclut un produit : le produit passe en `exclu=1`
3. Jorge "retire" un produit inconnu : auto-creation avec `exclu=1`
4. Les produits exclus n'apparaissent plus dans le compositeur ni les suggestions

---

## 11. Donnees initiales

Au premier deploiement, la BDD sera pre-remplie avec :

1. **Table `produits`** : tous les aliments mentionnes dans le tableau PDF (~80 produits)
2. **Table `equivalences`** : toutes les lignes du tableau PDF (~60 equivalences)
3. **Table `regles`** : les regles nutritionnelles par type de repas (~15 regles)
4. **Table `settings`** : budget_max=50, saison=printemps

---

## 12. Securite

- `.htaccess` : protection du dossier `data/` (deny all), rewrite rules
- Login par session PHP, mot de passe hashe (`password_hash` / `password_verify`)
- CSRF token sur tous les formulaires
- Requetes preparees PDO pour toutes les interactions SQLite
- Validation et sanitisation de toutes les entrees

---

## 13. Hors perimetre (pour plus tard)

- Multi-utilisateur
- Notifications push
- Mode hors ligne (PWA)
- Generation automatique des `.md` cote serveur
- Export PDF
- Adaptation automatique saison (pour l'instant reglage manuel)
