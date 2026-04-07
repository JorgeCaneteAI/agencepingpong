<?php
/**
 * MealCoach — Script d'installation
 * Crée la base SQLite, toutes les tables et les données de seed.
 * USAGE : php install.php  (une seule fois)
 */

define('INSTALL_MODE', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/db.php';

// ─── 1. Créer le dossier data/ si nécessaire ──────────────────────────────────
if (!is_dir(DATA_PATH)) {
    mkdir(DATA_PATH, 0750, true);
    echo "[OK] Dossier data/ créé.\n";
}

// ─── 2. Vérifier si la DB existe déjà ─────────────────────────────────────────
if (file_exists(DB_PATH)) {
    die("[STOP] La base de données existe déjà : " . DB_PATH . "\n" .
        "Supprimez-la manuellement si vous souhaitez réinstaller.\n");
}

echo "[OK] Création de la base : " . DB_PATH . "\n";

$db = getDb();

// ─── 3. Création des tables ───────────────────────────────────────────────────
$db->exec("
-- Paramètres globaux
CREATE TABLE IF NOT EXISTS settings (
    cle    TEXT PRIMARY KEY,
    valeur TEXT NOT NULL
);

-- Produits (ingrédients)
CREATE TABLE IF NOT EXISTS produits (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    nom           TEXT UNIQUE NOT NULL,
    categorie     TEXT,
    sous_categorie TEXT,
    unite_mesure  TEXT DEFAULT 'g',
    prix_unitaire REAL,
    unite_achat   TEXT DEFAULT 'kg',
    saisons       TEXT,
    tryptophane   INTEGER DEFAULT 0,
    exclu         INTEGER DEFAULT 0,
    note          TEXT,
    created_at    TEXT DEFAULT (datetime('now')),
    updated_at    TEXT DEFAULT (datetime('now'))
);

-- Équivalences nutritionnelles
CREATE TABLE IF NOT EXISTS equivalences (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    categorie       TEXT,
    description     TEXT,
    quantite        REAL,
    unite           TEXT,
    produit_id      INTEGER REFERENCES produits(id) ON DELETE SET NULL,
    moment          TEXT,
    est_non_raffine INTEGER DEFAULT 0
);

-- Règles nutritionnelles par type de repas
CREATE TABLE IF NOT EXISTS regles (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    type_repas   TEXT,
    categorie    TEXT,
    quantite_min INTEGER DEFAULT 0,
    quantite_max INTEGER,
    grammage     TEXT,
    note         TEXT
);

-- Stock de la cuisine
CREATE TABLE IF NOT EXISTS stock (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    produit_id      INTEGER NOT NULL REFERENCES produits(id) ON DELETE CASCADE,
    quantite        REAL,
    unite           TEXT,
    date_peremption TEXT,
    updated_at      TEXT DEFAULT (datetime('now'))
);

-- Semaines de menus
CREATE TABLE IF NOT EXISTS semaines (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    fichier       TEXT UNIQUE,
    numero        INTEGER,
    date_debut    TEXT,
    date_fin      TEXT,
    saison        TEXT,
    budget_estime REAL,
    statut        TEXT DEFAULT 'active',
    contenu_brut  TEXT,
    imported_at   TEXT DEFAULT (datetime('now'))
);

-- Jours d'une semaine
CREATE TABLE IF NOT EXISTS menu_jours (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    semaine_id INTEGER NOT NULL REFERENCES semaines(id) ON DELETE CASCADE,
    jour       INTEGER,
    date       TEXT,
    UNIQUE(semaine_id, jour)
);

-- Repas d'un jour
CREATE TABLE IF NOT EXISTS menu_repas (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    menu_jour_id INTEGER NOT NULL REFERENCES menu_jours(id) ON DELETE CASCADE,
    type_repas   TEXT,
    nom_plat     TEXT,
    contenu      TEXT,
    source       TEXT DEFAULT 'import',
    UNIQUE(menu_jour_id, type_repas)
);

-- Tâches batch (cuisine préparée)
CREATE TABLE IF NOT EXISTS batch_taches (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    semaine_id INTEGER NOT NULL REFERENCES semaines(id) ON DELETE CASCADE,
    ordre      INTEGER,
    heure      TEXT,
    action     TEXT,
    equipement TEXT,
    duree      INTEGER,
    resultat   TEXT
);

-- Listes de courses
CREATE TABLE IF NOT EXISTS listes_courses (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    semaine_id   INTEGER REFERENCES semaines(id) ON DELETE SET NULL,
    cout_estime  REAL,
    created_at   TEXT DEFAULT (datetime('now'))
);

-- Items d'une liste de courses
CREATE TABLE IF NOT EXISTS liste_items (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    liste_id         INTEGER NOT NULL REFERENCES listes_courses(id) ON DELETE CASCADE,
    produit_id       INTEGER REFERENCES produits(id) ON DELETE SET NULL,
    nom_brut         TEXT,
    quantite         REAL,
    unite            TEXT,
    categorie_rayon  TEXT,
    prix_estime      REAL,
    en_stock         INTEGER DEFAULT 0,
    achete           INTEGER DEFAULT 0,
    ajout_manuel     INTEGER DEFAULT 0
);

-- Suivi journalier (bien-être)
CREATE TABLE IF NOT EXISTS suivi_jours (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    date      TEXT UNIQUE NOT NULL,
    poids     REAL,
    humeur    INTEGER CHECK(humeur BETWEEN 1 AND 5),
    energie   INTEGER CHECK(energie BETWEEN 1 AND 5),
    sommeil   INTEGER CHECK(sommeil BETWEEN 1 AND 5),
    note      TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

-- Suivi des repas
CREATE TABLE IF NOT EXISTS suivi_repas (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    suivi_jour_id   INTEGER NOT NULL REFERENCES suivi_jours(id) ON DELETE CASCADE,
    menu_repas_id   INTEGER REFERENCES menu_repas(id) ON DELETE SET NULL,
    type_repas      TEXT,
    statut          TEXT DEFAULT 'prevu',
    modification    TEXT,
    craquage_detail TEXT,
    heure           TEXT,
    UNIQUE(suivi_jour_id, type_repas)
);

-- Repas composés (recettes)
CREATE TABLE IF NOT EXISTS repas_composes (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    nom        TEXT,
    type_repas TEXT,
    date       TEXT,
    favori     INTEGER DEFAULT 0,
    valide     INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now'))
);

-- Items d'un repas composé
CREATE TABLE IF NOT EXISTS repas_compose_items (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    repas_id       INTEGER NOT NULL REFERENCES repas_composes(id) ON DELETE CASCADE,
    equivalence_id INTEGER REFERENCES equivalences(id) ON DELETE SET NULL,
    produit_id     INTEGER REFERENCES produits(id) ON DELETE SET NULL,
    quantite       REAL,
    unite          TEXT,
    categorie      TEXT
);
");

echo "[OK] Tables créées (15).\n";

// ─── Indexes ──────────────────────────────────────────────────────────────────
$db->exec("
CREATE INDEX IF NOT EXISTS idx_produits_categorie     ON produits(categorie);
CREATE INDEX IF NOT EXISTS idx_produits_exclu         ON produits(exclu);
CREATE INDEX IF NOT EXISTS idx_stock_produit          ON stock(produit_id);
CREATE INDEX IF NOT EXISTS idx_semaines_date_debut    ON semaines(date_debut);
CREATE INDEX IF NOT EXISTS idx_menu_jours_date        ON menu_jours(date);
CREATE INDEX IF NOT EXISTS idx_suivi_jours_date       ON suivi_jours(date);
CREATE INDEX IF NOT EXISTS idx_liste_items_liste      ON liste_items(liste_id);
CREATE INDEX IF NOT EXISTS idx_equivalences_categorie ON equivalences(categorie);
CREATE INDEX IF NOT EXISTS idx_regles_type_repas      ON regles(type_repas);
");

echo "[OK] Indexes créés.\n";

// ─── 4. Seed produits (~80) ───────────────────────────────────────────────────
$produits = [
    // LÉGUMES
    ['nom' => 'Salade verte',          'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 1.20,  'unite_achat' => 'piece', 'saisons' => '["printemps","ete","automne","hiver"]'],
    ['nom' => 'Tomates',               'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 2.50,  'unite_achat' => 'kg',    'saisons' => '["ete","automne"]'],
    ['nom' => 'Endives',               'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 2.80,  'unite_achat' => 'kg',    'saisons' => '["automne","hiver"]'],
    ['nom' => 'Asperges',              'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 5.00,  'unite_achat' => 'kg',    'saisons' => '["printemps"]'],
    ['nom' => 'Céleri branche',        'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 1.80,  'unite_achat' => 'piece', 'saisons' => null],
    ['nom' => 'Céleri rave',           'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 1.50,  'unite_achat' => 'piece', 'saisons' => '["automne","hiver"]'],
    ['nom' => 'Champignons de Paris',  'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 3.20,  'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Chou-fleur',            'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 1.50,  'unite_achat' => 'piece', 'saisons' => '["automne","hiver","printemps"]'],
    ['nom' => 'Chou rouge',            'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 1.20,  'unite_achat' => 'piece', 'saisons' => '["automne","hiver"]'],
    ['nom' => 'Chou vert',             'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 1.00,  'unite_achat' => 'piece', 'saisons' => '["automne","hiver"]'],
    ['nom' => "Coeur d'artichaut",     'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 4.50,  'unite_achat' => 'boite', 'saisons' => null],
    ['nom' => 'Coeur de palmier',      'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 3.50,  'unite_achat' => 'boite', 'saisons' => null],
    ['nom' => 'Concombre',             'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 1.00,  'unite_achat' => 'piece', 'saisons' => '["ete"]'],
    ['nom' => 'Cresson',               'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 1.50,  'unite_achat' => 'botte', 'saisons' => null],
    ['nom' => 'Fenouil',               'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 2.00,  'unite_achat' => 'piece', 'saisons' => '["automne","hiver"]'],
    ['nom' => 'Poivron',               'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 2.50,  'unite_achat' => 'kg',    'saisons' => '["ete","automne"]'],
    ['nom' => 'Radis',                 'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 0.80,  'unite_achat' => 'botte', 'saisons' => '["printemps","ete"]'],
    ['nom' => 'Épinards',              'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 2.50,  'unite_achat' => 'kg',    'saisons' => '["printemps","automne"]'],
    ['nom' => 'Carottes',              'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 1.00,  'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Oignons',               'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 1.20,  'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Courgettes',            'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 2.00,  'unite_achat' => 'kg',    'saisons' => '["printemps","ete"]'],
    ['nom' => 'Aubergines',            'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 2.50,  'unite_achat' => 'kg',    'saisons' => '["ete","automne"]'],
    ['nom' => 'Haricots verts',        'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 3.50,  'unite_achat' => 'kg',    'saisons' => '["printemps","ete"]'],
    ['nom' => 'Brocoli',               'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 2.00,  'unite_achat' => 'piece', 'saisons' => '["automne","hiver"]'],
    ['nom' => 'Poireaux',              'categorie' => 'legumes', 'unite_mesure' => 'g',  'prix_unitaire' => 1.80,  'unite_achat' => 'botte', 'saisons' => '["automne","hiver","printemps"]'],

    // FRUITS
    ['nom' => 'Pommes',                'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 2.20,  'unite_achat' => 'kg',    'saisons' => '["automne","hiver"]'],
    ['nom' => 'Pêches',                'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 3.50,  'unite_achat' => 'kg',    'saisons' => '["ete"]'],
    ['nom' => 'Poires',                'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 2.80,  'unite_achat' => 'kg',    'saisons' => '["automne","hiver"]'],
    ['nom' => 'Pamplemousse',          'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 1.20,  'unite_achat' => 'piece', 'saisons' => '["hiver","printemps"]'],
    ['nom' => 'Kiwi',                  'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 3.00,  'unite_achat' => 'kg',    'saisons' => '["hiver","printemps"]'],
    ['nom' => 'Banane',                'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 1.80,  'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Melon',                 'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 2.50,  'unite_achat' => 'piece', 'saisons' => '["ete"]'],
    ['nom' => 'Pastèque',              'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 0.80,  'unite_achat' => 'kg',    'saisons' => '["ete"]'],
    ['nom' => 'Brugnon',               'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 3.50,  'unite_achat' => 'kg',    'saisons' => '["ete"]'],
    ['nom' => 'Orange',                'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 2.00,  'unite_achat' => 'kg',    'saisons' => '["hiver"]'],
    ['nom' => 'Abricots',              'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 4.00,  'unite_achat' => 'kg',    'saisons' => '["ete"]'],
    ['nom' => 'Mandarines',            'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 2.50,  'unite_achat' => 'kg',    'saisons' => '["hiver"]'],
    ['nom' => 'Prunes',                'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 3.00,  'unite_achat' => 'kg',    'saisons' => '["ete","automne"]'],
    ['nom' => 'Figues',                'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 6.00,  'unite_achat' => 'kg',    'saisons' => '["ete","automne"]'],
    ['nom' => 'Cerises',               'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 8.00,  'unite_achat' => 'kg',    'saisons' => '["printemps","ete"]'],
    ['nom' => 'Fraises',               'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 5.00,  'unite_achat' => 'kg',    'saisons' => '["printemps","ete"]'],
    ['nom' => 'Framboises',            'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 10.00, 'unite_achat' => 'kg',    'saisons' => '["ete"]'],
    ['nom' => 'Myrtilles',             'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 12.00, 'unite_achat' => 'kg',    'saisons' => '["ete"]'],
    ['nom' => 'Ananas',                'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 2.00,  'unite_achat' => 'piece', 'saisons' => null],
    ['nom' => 'Raisins',               'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 3.50,  'unite_achat' => 'kg',    'saisons' => '["ete","automne"]'],
    ['nom' => 'Citrons',               'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 1.50,  'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Dattes',                'categorie' => 'fruits',  'unite_mesure' => 'g',  'prix_unitaire' => 8.00,  'unite_achat' => 'kg',    'saisons' => null],

    // PROTÉINES
    ['nom' => 'Steak de boeuf',        'categorie' => 'proteines', 'sous_categorie' => 'viande',      'unite_mesure' => 'g',  'prix_unitaire' => 18.00, 'unite_achat' => 'kg', 'saisons' => null, 'tryptophane' => 0],
    ['nom' => 'Escalope de veau',      'categorie' => 'proteines', 'sous_categorie' => 'viande',      'unite_mesure' => 'g',  'prix_unitaire' => 20.00, 'unite_achat' => 'kg', 'saisons' => null, 'tryptophane' => 0],
    ['nom' => 'Porc maigre',           'categorie' => 'proteines', 'sous_categorie' => 'viande',      'unite_mesure' => 'g',  'prix_unitaire' => 12.00, 'unite_achat' => 'kg', 'saisons' => null, 'tryptophane' => 0],
    ['nom' => 'Poulet',                'categorie' => 'proteines', 'sous_categorie' => 'viande',      'unite_mesure' => 'g',  'prix_unitaire' => 10.00, 'unite_achat' => 'kg', 'saisons' => null, 'tryptophane' => 1],
    ['nom' => 'Oeufs',                 'categorie' => 'proteines', 'sous_categorie' => 'oeuf',        'unite_mesure' => 'piece', 'prix_unitaire' => 3.50, 'unite_achat' => 'boite', 'saisons' => null, 'tryptophane' => 1],
    ['nom' => 'Jambon maigre',         'categorie' => 'proteines', 'sous_categorie' => 'charcuterie', 'unite_mesure' => 'g',  'prix_unitaire' => 14.00, 'unite_achat' => 'kg', 'saisons' => null, 'tryptophane' => 0],
    ['nom' => 'Foie',                  'categorie' => 'proteines', 'sous_categorie' => 'viande',      'unite_mesure' => 'g',  'prix_unitaire' => 8.00,  'unite_achat' => 'kg', 'saisons' => null, 'tryptophane' => 0],
    ['nom' => 'Poisson blanc',         'categorie' => 'proteines', 'sous_categorie' => 'poisson',     'unite_mesure' => 'g',  'prix_unitaire' => 12.00, 'unite_achat' => 'kg', 'saisons' => null, 'tryptophane' => 0],
    ['nom' => 'Thon nature',           'categorie' => 'proteines', 'sous_categorie' => 'poisson',     'unite_mesure' => 'g',  'prix_unitaire' => 4.50,  'unite_achat' => 'boite', 'saisons' => null, 'tryptophane' => 1],
    ['nom' => 'Huîtres',               'categorie' => 'proteines', 'sous_categorie' => 'poisson',     'unite_mesure' => 'piece', 'prix_unitaire' => 12.00, 'unite_achat' => 'douzaine', 'saisons' => null, 'tryptophane' => 0],
    ['nom' => 'Moules',                'categorie' => 'proteines', 'sous_categorie' => 'poisson',     'unite_mesure' => 'g',  'prix_unitaire' => 3.50,  'unite_achat' => 'kg', 'saisons' => null, 'tryptophane' => 0],
    ['nom' => 'Crevettes roses',       'categorie' => 'proteines', 'sous_categorie' => 'poisson',     'unite_mesure' => 'g',  'prix_unitaire' => 15.00, 'unite_achat' => 'kg', 'saisons' => null, 'tryptophane' => 0],
    ['nom' => 'Gambas',                'categorie' => 'proteines', 'sous_categorie' => 'poisson',     'unite_mesure' => 'g',  'prix_unitaire' => 20.00, 'unite_achat' => 'kg', 'saisons' => null, 'tryptophane' => 0],
    ['nom' => 'Sardines',              'categorie' => 'proteines', 'sous_categorie' => 'poisson',     'unite_mesure' => 'g',  'prix_unitaire' => 2.50,  'unite_achat' => 'boite', 'saisons' => null, 'tryptophane' => 1],

    // LAITAGES
    ['nom' => 'Lait écrémé',           'categorie' => 'laitages', 'unite_mesure' => 'ml', 'prix_unitaire' => 0.90, 'unite_achat' => 'litre', 'saisons' => null],
    ['nom' => 'Lait 1/2 écrémé',       'categorie' => 'laitages', 'unite_mesure' => 'ml', 'prix_unitaire' => 1.00, 'unite_achat' => 'litre', 'saisons' => null],
    ['nom' => 'Yaourt nature',          'categorie' => 'laitages', 'unite_mesure' => 'g',  'prix_unitaire' => 0.25, 'unite_achat' => 'piece', 'saisons' => null],
    ['nom' => 'Yaourt grec',            'categorie' => 'laitages', 'unite_mesure' => 'g',  'prix_unitaire' => 0.60, 'unite_achat' => 'piece', 'saisons' => null, 'tryptophane' => 1],
    ['nom' => 'Petits suisses 30%',     'categorie' => 'laitages', 'unite_mesure' => 'g',  'prix_unitaire' => 0.30, 'unite_achat' => 'pack',  'saisons' => null],
    ['nom' => 'Fromage blanc 0%',       'categorie' => 'laitages', 'unite_mesure' => 'g',  'prix_unitaire' => 1.20, 'unite_achat' => 'kg',   'saisons' => null],
    ['nom' => 'Fromage blanc 20%',      'categorie' => 'laitages', 'unite_mesure' => 'g',  'prix_unitaire' => 1.50, 'unite_achat' => 'kg',   'saisons' => null],

    // FROMAGES
    ['nom' => 'Camembert 40%',          'categorie' => 'fromages', 'unite_mesure' => 'g',  'prix_unitaire' => 2.50,  'unite_achat' => 'piece', 'saisons' => null],
    ['nom' => 'Crème de gruyère',       'categorie' => 'fromages', 'unite_mesure' => 'g',  'prix_unitaire' => 2.80,  'unite_achat' => 'pack',  'saisons' => null],
    ['nom' => 'Fromage à tartiner allégé', 'categorie' => 'fromages', 'unite_mesure' => 'g', 'prix_unitaire' => 3.00, 'unite_achat' => 'piece', 'saisons' => null],
    ['nom' => 'Bonbel',                 'categorie' => 'fromages', 'unite_mesure' => 'g',  'prix_unitaire' => 4.50,  'unite_achat' => 'piece', 'saisons' => null],
    ['nom' => 'Edam',                   'categorie' => 'fromages', 'unite_mesure' => 'g',  'prix_unitaire' => 8.00,  'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Tome',                   'categorie' => 'fromages', 'unite_mesure' => 'g',  'prix_unitaire' => 10.00, 'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Emmental',               'categorie' => 'fromages', 'unite_mesure' => 'g',  'prix_unitaire' => 9.00,  'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Demi sel 40%',           'categorie' => 'fromages', 'unite_mesure' => 'g',  'prix_unitaire' => 3.00,  'unite_achat' => 'piece', 'saisons' => null],
    ['nom' => 'Saint-Moret allégé',     'categorie' => 'fromages', 'unite_mesure' => 'g',  'prix_unitaire' => 2.80,  'unite_achat' => 'piece', 'saisons' => null],

    // FÉCULENTS
    ['nom' => 'Pain complet',           'categorie' => 'feculents', 'unite_mesure' => 'g',  'prix_unitaire' => 3.50,  'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Pâtes complètes',        'categorie' => 'feculents', 'unite_mesure' => 'g',  'prix_unitaire' => 2.00,  'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Riz complet',            'categorie' => 'feculents', 'unite_mesure' => 'g',  'prix_unitaire' => 2.50,  'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Semoule 1/2 complète',   'categorie' => 'feculents', 'unite_mesure' => 'g',  'prix_unitaire' => 2.00,  'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Patate douce',           'categorie' => 'feculents', 'unite_mesure' => 'g',  'prix_unitaire' => 3.00,  'unite_achat' => 'kg',    'saisons' => '["automne","hiver"]'],
    ['nom' => 'Quinoa',                 'categorie' => 'feculents', 'unite_mesure' => 'g',  'prix_unitaire' => 5.00,  'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Boulgour',               'categorie' => 'feculents', 'unite_mesure' => 'g',  'prix_unitaire' => 3.00,  'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Lentilles vertes',       'categorie' => 'feculents', 'unite_mesure' => 'g',  'prix_unitaire' => 2.50,  'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Lentilles corail',       'categorie' => 'feculents', 'unite_mesure' => 'g',  'prix_unitaire' => 3.00,  'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Pois chiches',           'categorie' => 'feculents', 'unite_mesure' => 'g',  'prix_unitaire' => 1.50,  'unite_achat' => 'boite', 'saisons' => null],
    ['nom' => 'Haricots secs',          'categorie' => 'feculents', 'unite_mesure' => 'g',  'prix_unitaire' => 2.00,  'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Pommes de terre',        'categorie' => 'feculents', 'unite_mesure' => 'g',  'prix_unitaire' => 1.20,  'unite_achat' => 'kg',    'saisons' => null],

    // CÉRÉALES PDJ
    ['nom' => 'Biscottes',              'categorie' => 'cereales', 'unite_mesure' => 'piece', 'prix_unitaire' => 1.80, 'unite_achat' => 'paquet', 'saisons' => null],
    ['nom' => 'Wasa léger',             'categorie' => 'cereales', 'unite_mesure' => 'piece', 'prix_unitaire' => 3.00, 'unite_achat' => 'paquet', 'saisons' => null],
    ['nom' => 'Pains suédois',          'categorie' => 'cereales', 'unite_mesure' => 'piece', 'prix_unitaire' => 2.50, 'unite_achat' => 'paquet', 'saisons' => null],
    ['nom' => 'Galettes de riz',        'categorie' => 'cereales', 'unite_mesure' => 'piece', 'prix_unitaire' => 2.80, 'unite_achat' => 'paquet', 'saisons' => null],
    ['nom' => 'Flocons de céréales',    'categorie' => 'cereales', 'unite_mesure' => 'g',  'prix_unitaire' => 3.00,  'unite_achat' => 'kg',    'saisons' => null],

    // ÉPICERIE
    ['nom' => 'Amandes',                'categorie' => 'epicerie', 'unite_mesure' => 'g',  'prix_unitaire' => 12.00, 'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Noix',                   'categorie' => 'epicerie', 'unite_mesure' => 'g',  'prix_unitaire' => 10.00, 'unite_achat' => 'kg',    'saisons' => '["automne"]', 'tryptophane' => 1],
    ['nom' => 'Chocolat noir 85%',      'categorie' => 'epicerie', 'unite_mesure' => 'g',  'prix_unitaire' => 5.00,  'unite_achat' => 'kg',    'saisons' => null, 'tryptophane' => 1],
    ['nom' => 'Miel',                   'categorie' => 'epicerie', 'unite_mesure' => 'g',  'prix_unitaire' => 8.00,  'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Compote pomme non sucrée', 'categorie' => 'epicerie', 'unite_mesure' => 'g', 'prix_unitaire' => 1.50, 'unite_achat' => 'kg',   'saisons' => null],

    // MATIÈRES GRASSES
    ['nom' => 'Huile d\'olive',         'categorie' => 'matieres_grasses', 'unite_mesure' => 'ml', 'prix_unitaire' => 6.00, 'unite_achat' => 'litre', 'saisons' => null],
    ['nom' => 'Beurre',                 'categorie' => 'matieres_grasses', 'unite_mesure' => 'g',  'prix_unitaire' => 5.00, 'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Margarine 40%',          'categorie' => 'matieres_grasses', 'unite_mesure' => 'g',  'prix_unitaire' => 3.00, 'unite_achat' => 'kg',    'saisons' => null],
    ['nom' => 'Crème fraîche 5-20%',    'categorie' => 'matieres_grasses', 'unite_mesure' => 'g',  'prix_unitaire' => 1.50, 'unite_achat' => 'piece', 'saisons' => null],

    // CONDIMENTS
    ['nom' => 'Cumin',                  'categorie' => 'condiments', 'unite_mesure' => 'g', 'prix_unitaire' => 2.00, 'unite_achat' => 'paquet', 'saisons' => null],
    ['nom' => 'Curry',                  'categorie' => 'condiments', 'unite_mesure' => 'g', 'prix_unitaire' => 2.50, 'unite_achat' => 'paquet', 'saisons' => null],
    ['nom' => 'Herbes de Provence',     'categorie' => 'condiments', 'unite_mesure' => 'g', 'prix_unitaire' => 1.80, 'unite_achat' => 'paquet', 'saisons' => null],
    ['nom' => 'Bouillon de légumes',    'categorie' => 'condiments', 'unite_mesure' => 'piece', 'prix_unitaire' => 1.20, 'unite_achat' => 'paquet', 'saisons' => null],

    // BOISSONS
    ['nom' => 'Tisane verveine',        'categorie' => 'boissons', 'unite_mesure' => 'piece', 'prix_unitaire' => 2.50, 'unite_achat' => 'boite', 'saisons' => null],
    ['nom' => 'Tisane camomille',       'categorie' => 'boissons', 'unite_mesure' => 'piece', 'prix_unitaire' => 2.50, 'unite_achat' => 'boite', 'saisons' => null],
    ['nom' => 'Tisane tilleul',         'categorie' => 'boissons', 'unite_mesure' => 'piece', 'prix_unitaire' => 2.50, 'unite_achat' => 'boite', 'saisons' => null],
];

$countProduits = 0;
foreach ($produits as $p) {
    $row = [
        'nom'           => $p['nom'],
        'categorie'     => $p['categorie'],
        'sous_categorie' => $p['sous_categorie'] ?? null,
        'unite_mesure'  => $p['unite_mesure'] ?? 'g',
        'prix_unitaire' => $p['prix_unitaire'] ?? null,
        'unite_achat'   => $p['unite_achat'] ?? 'kg',
        'saisons'       => $p['saisons'] ?? null,
        'tryptophane'   => $p['tryptophane'] ?? 0,
    ];
    insert('produits', $row);
    $countProduits++;
}
echo "[OK] $countProduits produits insérés.\n";

// ─── 5. Seed équivalences (~60) ───────────────────────────────────────────────

// Helper: trouve l'ID d'un produit par son nom
function pid(string $nom): ?int
{
    $row = fetchOne('SELECT id FROM produits WHERE nom = :n', [':n' => $nom]);
    return $row ? (int)$row['id'] : null;
}

$equivalences = [
    // Laitages PDJ
    ['categorie' => 'laitage_pdj', 'description' => '200 ml lait écrémé',        'quantite' => 200, 'unite' => 'ml',    'produit' => 'Lait écrémé',            'moment' => 'petit_dej'],
    ['categorie' => 'laitage_pdj', 'description' => '100 ml lait 1/2 écrémé',    'quantite' => 100, 'unite' => 'ml',    'produit' => 'Lait 1/2 écrémé',        'moment' => 'petit_dej'],
    ['categorie' => 'laitage_pdj', 'description' => '1 yaourt nature',            'quantite' => 1,   'unite' => 'piece', 'produit' => 'Yaourt nature',           'moment' => 'petit_dej'],
    ['categorie' => 'laitage_pdj', 'description' => '2 petits suisses',           'quantite' => 2,   'unite' => 'piece', 'produit' => 'Petits suisses 30%',      'moment' => 'petit_dej'],
    ['categorie' => 'laitage_pdj', 'description' => '120g fromage blanc 0%',      'quantite' => 120, 'unite' => 'g',     'produit' => 'Fromage blanc 0%',        'moment' => 'petit_dej'],
    ['categorie' => 'laitage_pdj', 'description' => '2 crèmes de gruyère',        'quantite' => 2,   'unite' => 'piece', 'produit' => 'Crème de gruyère',        'moment' => 'petit_dej'],
    ['categorie' => 'laitage_pdj', 'description' => '1 demi sel',                 'quantite' => 1,   'unite' => 'piece', 'produit' => 'Demi sel 40%',            'moment' => 'petit_dej'],
    ['categorie' => 'laitage_pdj', 'description' => '1 Saint-Moret allégé',       'quantite' => 1,   'unite' => 'piece', 'produit' => 'Saint-Moret allégé',      'moment' => 'petit_dej'],

    // Céréales PDJ
    ['categorie' => 'cereale_pdj', 'description' => '3 biscottes',                'quantite' => 3,   'unite' => 'piece', 'produit' => 'Biscottes',               'moment' => 'petit_dej'],
    ['categorie' => 'cereale_pdj', 'description' => '50g pain complet',           'quantite' => 50,  'unite' => 'g',     'produit' => 'Pain complet',            'moment' => 'petit_dej', 'est_non_raffine' => 1],
    ['categorie' => 'cereale_pdj', 'description' => '3 wasa léger',               'quantite' => 3,   'unite' => 'piece', 'produit' => 'Wasa léger',              'moment' => 'petit_dej'],
    ['categorie' => 'cereale_pdj', 'description' => '3 pains suédois',            'quantite' => 3,   'unite' => 'piece', 'produit' => 'Pains suédois',           'moment' => 'petit_dej'],
    ['categorie' => 'cereale_pdj', 'description' => '4 càs flocons céréales',     'quantite' => 4,   'unite' => 'cas',   'produit' => 'Flocons de céréales',     'moment' => 'petit_dej'],
    ['categorie' => 'cereale_pdj', 'description' => '4 càs flocons maïs',         'quantite' => 4,   'unite' => 'cas',   'produit' => null,                      'moment' => 'petit_dej'],
    ['categorie' => 'cereale_pdj', 'description' => '4 galettes de riz',          'quantite' => 4,   'unite' => 'piece', 'produit' => 'Galettes de riz',         'moment' => 'petit_dej'],
    ['categorie' => 'cereale_pdj', 'description' => '4 càs compote pomme',        'quantite' => 4,   'unite' => 'cas',   'produit' => 'Compote pomme non sucrée','moment' => 'petit_dej'],

    // Protéines PDJ
    ['categorie' => 'proteine_pdj', 'description' => '1 oeuf',                    'quantite' => 1,   'unite' => 'piece', 'produit' => 'Oeufs',                   'moment' => 'petit_dej'],
    ['categorie' => 'proteine_pdj', 'description' => '1 tranche jambon cuit',     'quantite' => 1,   'unite' => 'tranche', 'produit' => 'Jambon maigre',         'moment' => 'petit_dej'],
    ['categorie' => 'proteine_pdj', 'description' => '1 tranche jambon cru',      'quantite' => 1,   'unite' => 'tranche', 'produit' => null,                    'moment' => 'petit_dej'],
    ['categorie' => 'proteine_pdj', 'description' => '1/8 camembert',             'quantite' => 0.125, 'unite' => 'piece', 'produit' => 'Camembert 40%',         'moment' => 'petit_dej'],
    ['categorie' => 'proteine_pdj', 'description' => '1 morceau fromage',         'quantite' => 1,   'unite' => 'morceau', 'produit' => null,                    'moment' => 'petit_dej'],

    // Viandes/Poissons repas
    ['categorie' => 'proteine_repas', 'description' => '150g boeuf',              'quantite' => 150, 'unite' => 'g',     'produit' => 'Steak de boeuf',          'moment' => 'repas'],
    ['categorie' => 'proteine_repas', 'description' => '150g veau',               'quantite' => 150, 'unite' => 'g',     'produit' => 'Escalope de veau',        'moment' => 'repas'],
    ['categorie' => 'proteine_repas', 'description' => '150g porc maigre',        'quantite' => 150, 'unite' => 'g',     'produit' => 'Porc maigre',             'moment' => 'repas'],
    ['categorie' => 'proteine_repas', 'description' => '150g volaille',           'quantite' => 150, 'unite' => 'g',     'produit' => 'Poulet',                  'moment' => 'repas'],
    ['categorie' => 'proteine_repas', 'description' => '2 oeufs',                 'quantite' => 2,   'unite' => 'piece', 'produit' => 'Oeufs',                   'moment' => 'repas'],
    ['categorie' => 'proteine_repas', 'description' => '80g jambon maigre',       'quantite' => 80,  'unite' => 'g',     'produit' => 'Jambon maigre',           'moment' => 'repas'],
    ['categorie' => 'proteine_repas', 'description' => '150g foie',               'quantite' => 150, 'unite' => 'g',     'produit' => 'Foie',                    'moment' => 'repas'],
    ['categorie' => 'proteine_repas', 'description' => '200g poisson blanc',      'quantite' => 200, 'unite' => 'g',     'produit' => 'Poisson blanc',           'moment' => 'repas'],
    ['categorie' => 'proteine_repas', 'description' => '150g thon nature',        'quantite' => 150, 'unite' => 'g',     'produit' => 'Thon nature',             'moment' => 'repas'],
    ['categorie' => 'proteine_repas', 'description' => '12 huîtres',              'quantite' => 12,  'unite' => 'piece', 'produit' => 'Huîtres',                 'moment' => 'repas'],
    ['categorie' => 'proteine_repas', 'description' => '12 moules',               'quantite' => 200, 'unite' => 'g',     'produit' => 'Moules',                  'moment' => 'repas'],
    ['categorie' => 'proteine_repas', 'description' => '10 crevettes roses',      'quantite' => 10,  'unite' => 'piece', 'produit' => 'Crevettes roses',         'moment' => 'repas'],

    // Fromages repas
    ['categorie' => 'fromage_repas', 'description' => '1/8 camembert',            'quantite' => 0.125, 'unite' => 'piece', 'produit' => 'Camembert 40%',         'moment' => 'repas'],
    ['categorie' => 'fromage_repas', 'description' => '2 crèmes de gruyère',      'quantite' => 2,   'unite' => 'piece', 'produit' => 'Crème de gruyère',        'moment' => 'repas'],
    ['categorie' => 'fromage_repas', 'description' => '1 portion à tartiner',     'quantite' => 1,   'unite' => 'portion', 'produit' => 'Fromage à tartiner allégé', 'moment' => 'repas'],
    ['categorie' => 'fromage_repas', 'description' => '1 portion fromage à couper', 'quantite' => 30, 'unite' => 'g',    'produit' => null,                      'moment' => 'repas'],
    ['categorie' => 'fromage_repas', 'description' => '2 demi sel',               'quantite' => 2,   'unite' => 'piece', 'produit' => 'Demi sel 40%',            'moment' => 'repas'],
    ['categorie' => 'fromage_repas', 'description' => '1 ramequin fromage blanc 20%', 'quantite' => 100, 'unite' => 'g',  'produit' => 'Fromage blanc 20%',      'moment' => 'repas'],
    ['categorie' => 'fromage_repas', 'description' => '1 yaourt nature',          'quantite' => 1,   'unite' => 'piece', 'produit' => 'Yaourt nature',           'moment' => 'repas'],

    // Fruits
    ['categorie' => 'fruit', 'description' => '1 pomme (100g)',                   'quantite' => 100, 'unite' => 'g',     'produit' => 'Pommes',                  'moment' => 'repas'],
    ['categorie' => 'fruit', 'description' => '1 pêche (100g)',                   'quantite' => 100, 'unite' => 'g',     'produit' => 'Pêches',                  'moment' => 'repas'],
    ['categorie' => 'fruit', 'description' => '1 poire (100g)',                   'quantite' => 100, 'unite' => 'g',     'produit' => 'Poires',                  'moment' => 'repas'],
    ['categorie' => 'fruit', 'description' => '1/2 pamplemousse',                 'quantite' => 0.5, 'unite' => 'piece', 'produit' => 'Pamplemousse',            'moment' => 'repas'],
    ['categorie' => 'fruit', 'description' => '1 kiwi',                           'quantite' => 1,   'unite' => 'piece', 'produit' => 'Kiwi',                    'moment' => 'repas'],
    ['categorie' => 'fruit', 'description' => '1 banane',                         'quantite' => 1,   'unite' => 'piece', 'produit' => 'Banane',                  'moment' => 'repas'],
    ['categorie' => 'fruit', 'description' => '1 tranche melon',                  'quantite' => 150, 'unite' => 'g',     'produit' => 'Melon',                   'moment' => 'repas'],
    ['categorie' => 'fruit', 'description' => '1 tranche pastèque',               'quantite' => 200, 'unite' => 'g',     'produit' => 'Pastèque',                'moment' => 'repas'],
    ['categorie' => 'fruit', 'description' => '1 brugnon',                        'quantite' => 1,   'unite' => 'piece', 'produit' => 'Brugnon',                 'moment' => 'repas'],
    ['categorie' => 'fruit', 'description' => '1 orange',                         'quantite' => 1,   'unite' => 'piece', 'produit' => 'Orange',                  'moment' => 'repas'],
    ['categorie' => 'fruit', 'description' => '3 abricots',                       'quantite' => 3,   'unite' => 'piece', 'produit' => 'Abricots',                'moment' => 'repas'],
    ['categorie' => 'fruit', 'description' => '2 mandarines',                     'quantite' => 2,   'unite' => 'piece', 'produit' => 'Mandarines',              'moment' => 'repas'],
    ['categorie' => 'fruit', 'description' => '5 prunes',                         'quantite' => 5,   'unite' => 'piece', 'produit' => 'Prunes',                  'moment' => 'repas'],
    ['categorie' => 'fruit', 'description' => '100g figues',                      'quantite' => 100, 'unite' => 'g',     'produit' => 'Figues',                  'moment' => 'repas'],
    ['categorie' => 'fruit', 'description' => '15 cerises',                       'quantite' => 15,  'unite' => 'piece', 'produit' => 'Cerises',                 'moment' => 'repas'],
    ['categorie' => 'fruit', 'description' => '100g baies (fraises/framboises)',   'quantite' => 100, 'unite' => 'g',     'produit' => 'Fraises',                 'moment' => 'repas'],
    ['categorie' => 'fruit', 'description' => '1 tranche ananas',                 'quantite' => 1,   'unite' => 'tranche', 'produit' => 'Ananas',                'moment' => 'repas'],
    ['categorie' => 'fruit', 'description' => '1 verre raisins',                  'quantite' => 100, 'unite' => 'g',     'produit' => 'Raisins',                 'moment' => 'repas'],

    // Sucres lents
    ['categorie' => 'sucre_lent', 'description' => '50g pain complet',            'quantite' => 50,  'unite' => 'g',     'produit' => 'Pain complet',            'moment' => 'repas', 'est_non_raffine' => 1],
    ['categorie' => 'sucre_lent', 'description' => '100g pâtes complètes cuites', 'quantite' => 100, 'unite' => 'g',     'produit' => 'Pâtes complètes',         'moment' => 'repas', 'est_non_raffine' => 1],
    ['categorie' => 'sucre_lent', 'description' => '100g riz complet cuit',       'quantite' => 100, 'unite' => 'g',     'produit' => 'Riz complet',             'moment' => 'repas', 'est_non_raffine' => 1],
    ['categorie' => 'sucre_lent', 'description' => '100g semoule 1/2 complète cuite', 'quantite' => 100, 'unite' => 'g', 'produit' => 'Semoule 1/2 complète',  'moment' => 'repas', 'est_non_raffine' => 1],
    ['categorie' => 'sucre_lent', 'description' => '100g patate douce cuite',     'quantite' => 100, 'unite' => 'g',     'produit' => 'Patate douce',            'moment' => 'repas', 'est_non_raffine' => 1],
    ['categorie' => 'sucre_lent', 'description' => '100g quinoa cuit',            'quantite' => 100, 'unite' => 'g',     'produit' => 'Quinoa',                  'moment' => 'repas', 'est_non_raffine' => 1],
    ['categorie' => 'sucre_lent', 'description' => '100g boulgour cuit',          'quantite' => 100, 'unite' => 'g',     'produit' => 'Boulgour',                'moment' => 'repas', 'est_non_raffine' => 1],
    ['categorie' => 'sucre_lent', 'description' => '100g lentilles cuites',       'quantite' => 100, 'unite' => 'g',     'produit' => 'Lentilles vertes',        'moment' => 'repas', 'est_non_raffine' => 1],
    ['categorie' => 'sucre_lent', 'description' => '100g pois chiches cuits',     'quantite' => 100, 'unite' => 'g',     'produit' => 'Pois chiches',            'moment' => 'repas', 'est_non_raffine' => 1],
    ['categorie' => 'sucre_lent', 'description' => '100g haricots secs cuits',    'quantite' => 100, 'unite' => 'g',     'produit' => 'Haricots secs',           'moment' => 'repas', 'est_non_raffine' => 1],
    ['categorie' => 'sucre_lent', 'description' => '100g pâtes blanches cuites',  'quantite' => 100, 'unite' => 'g',     'produit' => 'Pâtes complètes',         'moment' => 'repas', 'est_non_raffine' => 0],
    ['categorie' => 'sucre_lent', 'description' => '100g riz blanc cuit',         'quantite' => 100, 'unite' => 'g',     'produit' => 'Riz complet',             'moment' => 'repas', 'est_non_raffine' => 0],
    ['categorie' => 'sucre_lent', 'description' => '100g semoule blanche cuite',  'quantite' => 100, 'unite' => 'g',     'produit' => 'Semoule 1/2 complète',    'moment' => 'repas', 'est_non_raffine' => 0],
    ['categorie' => 'sucre_lent', 'description' => '100g pommes de terre cuites', 'quantite' => 100, 'unite' => 'g',     'produit' => 'Pommes de terre',         'moment' => 'repas', 'est_non_raffine' => 0],

    // Matières grasses
    ['categorie' => 'matiere_grasse', 'description' => '1 càs huile olive',       'quantite' => 1,   'unite' => 'cas',   'produit' => "Huile d'olive",           'moment' => 'repas'],
    ['categorie' => 'matiere_grasse', 'description' => '1 càs beurre',            'quantite' => 1,   'unite' => 'cas',   'produit' => 'Beurre',                  'moment' => 'repas'],
    ['categorie' => 'matiere_grasse', 'description' => '2 càs margarine 40%',     'quantite' => 2,   'unite' => 'cas',   'produit' => 'Margarine 40%',           'moment' => 'repas'],
    ['categorie' => 'matiere_grasse', 'description' => '3 càs sauce salade',      'quantite' => 3,   'unite' => 'cas',   'produit' => null,                      'moment' => 'repas'],
    ['categorie' => 'matiere_grasse', 'description' => '3 càs fromage blanc 10%', 'quantite' => 3,   'unite' => 'cas',   'produit' => 'Fromage blanc 0%',        'moment' => 'repas'],
    ['categorie' => 'matiere_grasse', 'description' => '3 càs crème fraîche',     'quantite' => 3,   'unite' => 'cas',   'produit' => 'Crème fraîche 5-20%',     'moment' => 'repas'],
];

$countEquiv = 0;
foreach ($equivalences as $e) {
    $pid = null;
    if (!empty($e['produit'])) {
        $pid = pid($e['produit']);
    }
    insert('equivalences', [
        'categorie'       => $e['categorie'],
        'description'     => $e['description'],
        'quantite'        => $e['quantite'],
        'unite'           => $e['unite'],
        'produit_id'      => $pid,
        'moment'          => $e['moment'],
        'est_non_raffine' => $e['est_non_raffine'] ?? 0,
    ]);
    $countEquiv++;
}
echo "[OK] $countEquiv équivalences insérées.\n";

// ─── 6. Seed règles nutritionnelles (~15) ─────────────────────────────────────
$regles = [
    // Petit déjeuner
    ['type_repas' => 'petit_dej', 'categorie' => 'laitage',             'quantite_min' => 1, 'quantite_max' => 1, 'grammage' => null,       'note' => 'Voir équivalences laitages PDJ'],
    ['type_repas' => 'petit_dej', 'categorie' => 'cereale',             'quantite_min' => 1, 'quantite_max' => 1, 'grammage' => null,       'note' => 'Voir équivalences céréales PDJ'],
    ['type_repas' => 'petit_dej', 'categorie' => 'proteine',            'quantite_min' => 0, 'quantite_max' => 1, 'grammage' => null,       'note' => 'Optionnel — voir équivalences protéines PDJ'],

    // Déjeuner
    ['type_repas' => 'dejeuner',  'categorie' => 'legume',              'quantite_min' => 1, 'quantite_max' => 99,'grammage' => 'à volonté','note' => 'Légumes crus ou cuits'],
    ['type_repas' => 'dejeuner',  'categorie' => 'proteine',            'quantite_min' => 1, 'quantite_max' => 1, 'grammage' => null,       'note' => 'Voir équivalences viandes/poissons'],
    ['type_repas' => 'dejeuner',  'categorie' => 'sucre_lent',          'quantite_min' => 2, 'quantite_max' => 2, 'grammage' => '100g cuits','note' => 'Préférer non-raffinés'],
    ['type_repas' => 'dejeuner',  'categorie' => 'laitage_ou_fromage',  'quantite_min' => 1, 'quantite_max' => 1, 'grammage' => null,       'note' => 'Voir équivalences fromages repas'],
    ['type_repas' => 'dejeuner',  'categorie' => 'fruit',               'quantite_min' => 1, 'quantite_max' => 1, 'grammage' => null,       'note' => 'Voir équivalences fruits'],
    ['type_repas' => 'dejeuner',  'categorie' => 'matiere_grasse',      'quantite_min' => 1, 'quantite_max' => 1, 'grammage' => '1 CàS',   'note' => 'Voir équivalences MG'],

    // Dîner
    ['type_repas' => 'diner',     'categorie' => 'legume',              'quantite_min' => 1, 'quantite_max' => 99,'grammage' => 'à volonté','note' => 'Légumes crus ou cuits'],
    ['type_repas' => 'diner',     'categorie' => 'proteine',            'quantite_min' => 1, 'quantite_max' => 1, 'grammage' => null,       'note' => 'Voir équivalences viandes/poissons'],
    ['type_repas' => 'diner',     'categorie' => 'sucre_lent',          'quantite_min' => 1, 'quantite_max' => 1, 'grammage' => '50g cuits','note' => 'Préférer non-raffinés, portion réduite le soir'],
    ['type_repas' => 'diner',     'categorie' => 'laitage_ou_fromage',  'quantite_min' => 1, 'quantite_max' => 1, 'grammage' => null,       'note' => 'Voir équivalences fromages repas'],
    ['type_repas' => 'diner',     'categorie' => 'matiere_grasse',      'quantite_min' => 1, 'quantite_max' => 1, 'grammage' => '1 CàS',   'note' => 'Voir équivalences MG'],

    // Encas et dessert
    ['type_repas' => 'encas',     'categorie' => 'libre',               'quantite_min' => 0, 'quantite_max' => 2, 'grammage' => null,       'note' => 'Optionnel — encas équilibré'],
    ['type_repas' => 'dessert',   'categorie' => 'libre',               'quantite_min' => 0, 'quantite_max' => 1, 'grammage' => null,       'note' => 'Optionnel'],
];

$countRegles = 0;
foreach ($regles as $r) {
    insert('regles', $r);
    $countRegles++;
}
echo "[OK] $countRegles règles insérées.\n";

// ─── 7. Seed settings ─────────────────────────────────────────────────────────
$hash = password_hash('mealcoach2026', PASSWORD_DEFAULT);

$settings = [
    'budget_max'          => '50',
    'poids_objectif'      => '',
    'saison'              => 'printemps',
    'mot_de_passe_hash'   => $hash,
];

foreach ($settings as $cle => $valeur) {
    setSetting($cle, $valeur);
}
echo "[OK] Settings insérés (mot de passe haché).\n";

// ─── Résumé final ─────────────────────────────────────────────────────────────
echo "\n==========================================\n";
echo " Installation terminée avec succès !\n";
echo " DB : " . DB_PATH . "\n";
echo "==========================================\n";
