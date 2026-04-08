<?php
/**
 * MealCoach V2 — Courses
 * Liste de courses par semaine, générée depuis les menus
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

// ── 2 semaines glissantes ────────────────────────────────────────────────────
$today = new DateTime();
$dow = (int)$today->format('N');
$mondayS1 = (clone $today)->modify('-' . ($dow - 1) . ' days');
$sundayS1 = (clone $mondayS1)->modify('+6 days');
$mondayS2 = (clone $mondayS1)->modify('+7 days');
$sundayS2 = (clone $mondayS2)->modify('+6 days');

$weekTabs = [
    ['label' => 'Cette semaine',     'monday' => $mondayS1, 'sunday' => $sundayS1],
    ['label' => 'Semaine prochaine', 'monday' => $mondayS2, 'sunday' => $sundayS2],
];

// Semaine sélectionnée via ?w=0 ou ?w=1 (défaut: 0)
$selectedWeek = isset($_GET['w']) ? (int)$_GET['w'] : 0;
if ($selectedWeek < 0 || $selectedWeek > 1) $selectedWeek = 0;
$activeWeek = $weekTabs[$selectedWeek];

// Trouver la semaine en BDD qui correspond à cette période
$semaineActive = fetchOne(
    'SELECT * FROM semaines WHERE date_debut = :d',
    [':d' => $activeWeek['monday']->format('Y-m-d')]
);

// ── Générer la liste depuis menu_repas.contenu ──────────────────────────────
$parCategorie = [];
$nomsJours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

// Map catégorie contenu → rayon courses (granulaire)
$rayonMap = [
    'légumes' => 'Legumes', 'legumes' => 'Legumes',
    'fruit' => 'Fruits',
    'protéine' => 'Viandes & Volailles', 'proteine' => 'Viandes & Volailles',
    'sucre lent' => 'Epicerie', 'céréale' => 'Epicerie', 'cereale' => 'Epicerie',
    'féculent' => 'Epicerie', 'feculent' => 'Epicerie',
    'laitage' => 'Laitages',
    'mg' => 'Epicerie', 'matiere grasse' => 'Epicerie',
    'fromage' => 'Fromages',
    'boisson' => 'Boissons',
];

// Détection poisson vs viande dans les protéines
$poissons = ['poisson', 'saumon', 'cabillaud', 'thon', 'truite', 'crevette', 'sardine',
             'maquereau', 'colin', 'merlu', 'bar', 'dorade', 'sole', 'lieu', 'moules',
             'huîtres', 'calamar', 'gambas', 'lotte', 'rouget'];

$rayonEmoji = [
    'Legumes' => '🥬',
    'Fruits' => '🍎',
    'Viandes & Volailles' => '🍗',
    'Poissonnerie' => '🐟',
    'Fromages' => '🧀',
    'Laitages' => '🥛',
    'Epicerie' => '🛒',
    'Boissons' => '☕',
    'Divers' => '📦',
];

// ── Parser intelligent d'ingrédients ────────────────────────────────────────
function parseIngredient(string $raw): array {
    $quantite = 1;
    $unite = '';
    $produit = $raw;

    // Enlever les parenthèses (détails de cuisson, poids indicatif)
    $clean = preg_replace('/\s*\([^)]*\)/', '', $raw);
    $clean = trim($clean);

    // Pattern: "2 oeufs brouillés", "1 tranche pain complet", "150g lentilles"
    // 1) Quantité numérique au début
    if (preg_match('/^(\d+(?:[.,]\d+)?)\s*/', $clean, $m)) {
        $quantite = (float) str_replace(',', '.', $m[1]);
        $reste = trim(mb_substr($clean, mb_strlen($m[0])));

        // 2) Unité optionnelle : g, ml, cl, kg, l, tranche(s), cuillère(s), pot(s), bol(s), boîte(s), portion(s), tasse(s), carré(s)
        $unitePattern = '/^(g|ml|cl|kg|l|tranches?|cuill[eè]res?\s*[àa]\s*(soupe|caf[eé])|pots?|bols?|bo[iî]tes?|portions?|tasses?|carr[eé]s?)\s+/iu';
        if (preg_match($unitePattern, $reste, $mu)) {
            $unite = mb_strtolower(trim($mu[0]));
            $produit = trim(mb_substr($reste, mb_strlen($mu[0])));
        } else {
            $produit = $reste;
        }
    }

    // Normaliser le produit : enlever adjectifs de cuisson/préparation courants
    $produitBase = $produit;
    // Protéger les noms composés où la couleur fait partie du produit
    $composés = ['chou rouge', 'chou blanc', 'chou vert', 'chou-fleur', 'chocolat noir', 'riz noir', 'riz complet', 'pain complet', 'haricots verts', 'haricot vert', 'poivron rouge', 'poivron vert', 'radis noir', 'lentilles corail', 'fromage blanc'];
    $placeholders = [];
    foreach ($composés as $i => $c) {
        $ph = '##COMPOSE' . $i . '##';
        $placeholders[$ph] = $c;
        $produitBase = str_ireplace($c, $ph, $produitBase);
    }
    $produitBase = preg_replace('/\b(brouill[eé]s?|poch[eé]s?|dur|durs|cuits?e?s?|cuites?|grill[eé]s?|r[oô]tis?e?s?|frais|fra[iî]che?s?|nature|entier|complet|compl[eè]te|cru|crus?e?s?|r[aâ]p[eé]e?s?|moy?enn?e?s?|gross?e?s?|petit?e?s?|vert?e?s?|blanc|blanche|corail|noir|rouge)\b/iu', '', $produitBase);
    foreach ($placeholders as $ph => $c) { $produitBase = str_replace($ph, $c, $produitBase); }
    $produitBase = preg_replace('/\b(en soupe|en mouillettes|de provence|de paris|sans sucre|ajout[eé]?|à la coque|au four)\b/iu', '', $produitBase);
    $produitBase = preg_replace('/\s{2,}/', ' ', $produitBase);
    $produitBase = trim($produitBase);

    // Mapping de synonymes → produit d'achat
    $synonymes = [
        '/\b(oeufs?|œufs?|omelette)\b/iu' => 'oeufs',
        '/\b(pain)\b/iu' => 'pain',
        '/\b(huile d\s*olive)\b/iu' => 'huile d olive',
        '/\b(yaourt|yaourts)\b/iu' => 'yaourt',
        '/\b(fromage blanc)\b/iu' => 'fromage blanc',
        '/\b(lentilles)\b/iu' => 'lentilles',
        '/\b(riz)\b/iu' => 'riz',
        '/\b(tisane)\b/iu' => 'tisane',
        '/\b(caf[eé]|th[eé])\b/iu' => 'café ou thé',
        '/\b(salade)\b/iu' => 'salade verte',
        '/\b(tomates?)\b/iu' => 'tomates',
        '/\b(carottes?)\b/iu' => 'carottes',
        '/\b([eé]pinards?)\b/iu' => 'épinards',
    ];
    $matchedSynonyme = null;
    foreach ($synonymes as $pattern => $baseName) {
        if (preg_match($pattern, $produitBase) || preg_match($pattern, $produit)) {
            $matchedSynonyme = $baseName;
            break;
        }
    }

    // Pour les oeufs : extraire la quantité totale (ex: "omelette 3 oeufs" = 3)
    if ($matchedSynonyme === 'oeufs') {
        if (preg_match('/(\d+)\s*(oeufs?|œufs?)/iu', $produit, $mOeuf)) {
            $quantite = (float) $mOeuf[1];
        }
        $produitBase = 'oeufs';
    }

    if ($matchedSynonyme) {
        $produitBase = $matchedSynonyme;
    }

    // Clé de regroupement
    $key = mb_strtolower($produitBase);
    $key = preg_replace('/[^a-zàâéèêëïîôùûüç\s]/', '', $key);
    $key = preg_replace('/\s+/', ' ', trim($key));

    // Nom d'affichage
    if (empty($produitBase)) $produitBase = $produit;

    return [
        'quantite' => $quantite,
        'unite'    => $unite,
        'produit'  => $produitBase ?: $raw,
        'key'      => $key ?: mb_strtolower($raw),
    ];
}

if ($semaineActive) {
    $menuJours = fetchAll(
        'SELECT id, jour FROM menu_jours WHERE semaine_id = :sid ORDER BY jour',
        [':sid' => (int) $semaineActive['id']]
    );

    foreach ($menuJours as $mj) {
        $repas = fetchAll(
            'SELECT type_repas, nom_plat, contenu FROM menu_repas WHERE menu_jour_id = :mjid',
            [':mjid' => (int) $mj['id']]
        );

        $jourNom = $nomsJours[(int) $mj['jour']] ?? 'Jour ' . $mj['jour'];

        foreach ($repas as $r) {
            $contenu = $r['contenu'] ?? '';
            if (empty($contenu)) continue;

            $segments = preg_split('/ - /', $contenu);
            foreach ($segments as $seg) {
                $seg = trim($seg);
                if (empty($seg)) continue;

                $colonPos = mb_strpos($seg, ' : ');
                $cat = '';
                $val = $seg;
                if ($colonPos !== false) {
                    $cat = trim(mb_substr($seg, 0, $colonPos));
                    $val = trim(mb_substr($seg, $colonPos + 3));
                }

                // Nettoyer
                $val = preg_replace('/\s*—\s*à volonté.*$/i', '', $val);
                $val = trim($val);
                if (empty($val)) continue;

                // Splitter sur "+"
                $subParts = preg_split('/\s*\+\s*/', $val);
                foreach ($subParts as $sp) {
                    $sp = trim($sp);
                    if (empty($sp)) continue;

                    $catLower = mb_strtolower($cat);
                    $rayon = $rayonMap[$catLower] ?? 'Divers';

                    // Auto-classement par mot-clé quand pas de catégorie (goûter, petit-déj)
                    if ($rayon === 'Divers') {
                        $spLower = mb_strtolower($sp);
                        $autoMap = [
                            'Fruits'              => ['banane', 'pomme', 'poire', 'orange', 'kiwi', 'mangue', 'abricot sec'],
                            'Laitages'            => ['yaourt', 'fromage blanc', 'carré frais', 'gervais'],
                            'Viandes & Volailles' => ['jambon'],
                            'Epicerie'            => ['chocolat', 'amande', 'noix', 'cajou', 'noisette', 'graine', 'datte', 'miel', 'pain', 'biscuit', 'céréale', 'flocon', 'café', 'thé'],
                        ];
                        foreach ($autoMap as $autoRayon => $mots) {
                            foreach ($mots as $mot) {
                                if (str_contains($spLower, $mot)) { $rayon = $autoRayon; break 2; }
                            }
                        }
                    }

                    // Distinguer poisson vs viande
                    if ($rayon === 'Viandes & Volailles') {
                        $spLower = mb_strtolower($sp);
                        foreach ($poissons as $fish) {
                            if (str_contains($spLower, $fish)) {
                                $rayon = 'Poissonnerie';
                                break;
                            }
                        }
                    }

                    // Extraire quantité + unité + produit de base
                    $parsed = parseIngredient($sp);

                    if (!isset($parCategorie[$rayon])) $parCategorie[$rayon] = [];
                    $key = $parsed['key'];

                    if (!isset($parCategorie[$rayon][$key])) {
                        $parCategorie[$rayon][$key] = [
                            'produit' => $parsed['produit'],
                            'quantite' => 0,
                            'unite' => $parsed['unite'],
                            'jours' => [],
                            'details' => [],
                        ];
                    }
                    $parCategorie[$rayon][$key]['quantite'] += $parsed['quantite'];
                    $parCategorie[$rayon][$key]['jours'][] = $jourNom;
                    if (!in_array($sp, $parCategorie[$rayon][$key]['details'])) {
                        $parCategorie[$rayon][$key]['details'][] = $sp;
                    }
                }
            }
        }
    }

    // Exclure les produits déjà en garde-manger (stock non-périssable)
    $stockProduits = fetchAll(
        'SELECT LOWER(p.nom) as nom_lower FROM stock s JOIN produits p ON p.id = s.produit_id'
    );
    $stockNoms = array_column($stockProduits, 'nom_lower');

    foreach ($parCategorie as $rayon => &$items) {
        foreach ($items as $key => $item) {
            $prodLower = mb_strtolower($item['produit']);
            foreach ($stockNoms as $sn) {
                // Match direct ou mot principal du stock présent dans le produit
                $snWords = explode(' ', $sn);
                $mainWord = count($snWords) > 1 ? $snWords[0] : $sn;
                if (mb_strlen($mainWord) < 4) $mainWord = $sn; // mot trop court, utiliser le nom complet
                if (str_contains($prodLower, $sn) || str_contains($sn, $prodLower) || str_contains($prodLower, $mainWord)) {
                    unset($items[$key]);
                    break;
                }
            }
        }
        if (empty($items)) unset($parCategorie[$rayon]);
    }
    unset($items);

    // Trier les rayons et les items
    ksort($parCategorie);
    foreach ($parCategorie as &$items) {
        ksort($items);
        foreach ($items as &$item) {
            $item['jours'] = array_unique($item['jours']);
        }
    }
    unset($items, $item);
}

// Compter le total
$totalItems = 0;
foreach ($parCategorie as $items) $totalItems += count($items);

// Séparer frais vs sec
$rayonsFrais = ['Legumes', 'Fruits', 'Viandes & Volailles', 'Poissonnerie', 'Fromages', 'Laitages'];
$rayonsSecs  = ['Epicerie', 'Boissons', 'Divers'];

$parFrais = [];
$parSec   = [];
foreach ($parCategorie as $rayon => $items) {
    if (in_array($rayon, $rayonsFrais)) {
        $parFrais[$rayon] = $items;
    } else {
        $parSec[$rayon] = $items;
    }
}
$totalFrais = 0;
foreach ($parFrais as $items) $totalFrais += count($items);
$totalSec = 0;
foreach ($parSec as $items) $totalSec += count($items);

// ── Rendu ────────────────────────────────────────────────────────────────────
$pageTitle = 'Courses';
$activeNav = 'courses';
ob_start();
?>

<div class="page-header">
    <h1>Courses</h1>
    <?php if ($semaineActive): ?>
    <div class="page-header-meta">
        <span class="page-header-badge"><?= $totalItems ?> articles</span>
    </div>
    <?php endif; ?>
</div>

<div class="page-inner" style="margin-bottom:12px;">
    <div class="semaine-tabs">
        <?php foreach ($weekTabs as $wi => $wt): ?>
        <a href="<?= BASE_URL ?>/courses?w=<?= $wi ?>"
           class="semaine-tab<?= $wi === $selectedWeek ? ' semaine-tab--active' : '' ?>">
            <span class="semaine-tab-label"><?= $wt['label'] ?></span>
            <span class="semaine-tab-dates"><?= $wt['monday']->format('d/m') ?> → <?= $wt['sunday']->format('d/m') ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if (!$semaineActive): ?>
<div class="page-inner">
    <div class="alert-card alert-card--warning">
        <span class="alert-icon">⚠️</span>
        <div class="alert-text">
            Aucune semaine active.
            <a href="<?= BASE_URL ?>/admin/import" style="font-weight:700;text-decoration:underline;">Importer un menu</a>
        </div>
    </div>
</div>

<?php elseif (empty($parCategorie)): ?>
<div class="page-inner">
    <div class="alert-card">
        Aucun ingredient trouve pour cette semaine.
    </div>
</div>

<?php else: ?>

<!-- Liste de courses (items marqués "à acheter") -->
<div id="listeCourses" style="display:none;">
    <div class="section-title">🛒 Ma liste de courses <span id="listeCount" style="font-weight:400;opacity:.5;"></span></div>
    <div class="page-inner">
        <div class="card" id="listeCoursesCard" style="padding:4px 0;"></div>
    </div>
    <div class="spacer"></div>
</div>

<!-- ═══ PRODUITS FRAIS ═══ -->
<?php if (!empty($parFrais)): ?>
<div class="courses-zone-header courses-zone--frais">
    <span class="courses-zone-icon">🧊</span>
    <span class="courses-zone-title">Produits frais</span>
    <span class="courses-zone-count"><?= $totalFrais ?> article<?= $totalFrais > 1 ? 's' : '' ?></span>
</div>

<?php foreach ($parFrais as $rayon => $items):
    $emoji = $rayonEmoji[$rayon] ?? '📦';
?>
<div class="section-title"><?= $emoji ?> <?= htmlspecialchars($rayon) ?> <span style="font-weight:400;opacity:.5;">(<?= count($items) ?>)</span></div>
<div class="page-inner">
    <div class="card" style="padding:4px 0;">
        <?php foreach ($items as $key => $item):
            $itemId = md5($rayon . '|' . $key);
            $qty = $item['quantite'];
            $qtyStr = ($qty == (int)$qty) ? (int)$qty : number_format($qty, 1);
            $unite = $item['unite'] ? rtrim($item['unite']) . ' ' : '';
            $displayNom = $qtyStr . ' ' . $unite . $item['produit'];
            $nbJours = count($item['jours']);
        ?>
        <div class="courses-item" data-id="<?= $itemId ?>">
            <div class="courses-item-body">
                <div class="courses-item-nom"><?= htmlspecialchars($displayNom) ?></div>
                <div class="courses-item-jours"><?= htmlspecialchars(implode(', ', $item['jours'])) ?> <?php if ($nbJours > 1): ?><span class="courses-item-count">(×<?= $nbJours ?>)</span><?php endif; ?></div>
            </div>
            <div class="courses-item-actions">
                <button class="stock-btn stock-btn--ok" onclick="toggleStock(this, '<?= $itemId ?>', 'stock')" title="En stock">✓</button>
                <button class="stock-btn stock-btn--buy" onclick="toggleStock(this, '<?= $itemId ?>', 'acheter')" title="A acheter">🛒</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ═══ EPICERIE SECHE ═══ -->
<?php if (!empty($parSec)): ?>
<div class="courses-zone-header courses-zone--sec">
    <span class="courses-zone-icon">🏪</span>
    <span class="courses-zone-title">Epicerie seche</span>
    <span class="courses-zone-count"><?= $totalSec ?> article<?= $totalSec > 1 ? 's' : '' ?></span>
</div>

<?php foreach ($parSec as $rayon => $items):
    $emoji = $rayonEmoji[$rayon] ?? '📦';
?>
<div class="section-title"><?= $emoji ?> <?= htmlspecialchars($rayon) ?> <span style="font-weight:400;opacity:.5;">(<?= count($items) ?>)</span></div>
<div class="page-inner">
    <div class="card" style="padding:4px 0;">
        <?php foreach ($items as $key => $item):
            $itemId = md5($rayon . '|' . $key);
            $qty = $item['quantite'];
            $qtyStr = ($qty == (int)$qty) ? (int)$qty : number_format($qty, 1);
            $unite = $item['unite'] ? rtrim($item['unite']) . ' ' : '';
            $displayNom = $qtyStr . ' ' . $unite . $item['produit'];
            $nbJours = count($item['jours']);
        ?>
        <div class="courses-item" data-id="<?= $itemId ?>">
            <div class="courses-item-body">
                <div class="courses-item-nom"><?= htmlspecialchars($displayNom) ?></div>
                <div class="courses-item-jours"><?= htmlspecialchars(implode(', ', $item['jours'])) ?> <?php if ($nbJours > 1): ?><span class="courses-item-count">(×<?= $nbJours ?>)</span><?php endif; ?></div>
            </div>
            <div class="courses-item-actions">
                <button class="stock-btn stock-btn--ok" onclick="toggleStock(this, '<?= $itemId ?>', 'stock')" title="En stock">✓</button>
                <button class="stock-btn stock-btn--buy" onclick="toggleStock(this, '<?= $itemId ?>', 'acheter')" title="A acheter">🛒</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php endif; ?>

<style>
.semaine-tabs {
    display: flex; gap: 8px;
}
.semaine-tab {
    flex: 1; display: flex; flex-direction: column; align-items: center;
    padding: 10px 12px; border-radius: 16px;
    text-decoration: none; border: 2px solid rgba(80,50,50,0.1);
    background: var(--card, #fff); transition: all 0.2s;
}
.semaine-tab-label {
    font-size: 0.82rem; font-weight: 700; color: var(--text-muted);
}
.semaine-tab-dates {
    font-size: 0.7rem; font-weight: 600; color: var(--text-light);
}
.semaine-tab--active {
    border-color: var(--accent); background: var(--accent);
}
.semaine-tab--active .semaine-tab-label { color: white; }
.semaine-tab--active .semaine-tab-dates { color: rgba(255,255,255,0.8); }

.courses-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    border-bottom: 1px solid var(--border, #eee);
    transition: opacity 0.25s, background 0.2s;
}
.courses-item:last-child { border-bottom: none; }
.courses-item--stock {
    opacity: 0.35;
}
.courses-item--stock .courses-item-nom {
    text-decoration: line-through;
}
.courses-item--buy {
    background: color-mix(in srgb, var(--warning, #F5A623) 8%, transparent);
}
.courses-item-body { flex: 1; min-width: 0; }
.courses-item-nom {
    font-weight: 600;
    font-size: 0.88rem;
}
.courses-item-jours {
    font-size: 0.7rem;
    color: var(--text-muted);
    margin-top: 1px;
}
.courses-item-count {
    font-weight: 700;
    color: var(--accent);
}
.courses-item-actions {
    display: flex;
    gap: 6px;
    flex-shrink: 0;
}
.stock-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 2px solid var(--border, #eee);
    background: var(--bg-card, #fff);
    font-size: 0.9rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: border-color 0.15s, background 0.15s, transform 0.1s;
}
.stock-btn:active { transform: scale(0.9); }
.stock-btn--ok.active {
    border-color: var(--success, #4CAF50);
    background: color-mix(in srgb, var(--success, #4CAF50) 15%, transparent);
}
.stock-btn--buy.active {
    border-color: var(--warning, #F5A623);
    background: color-mix(in srgb, var(--warning, #F5A623) 15%, transparent);
}

/* Zone headers (frais / sec) */
.courses-zone-header {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 16px; margin: 16px 0 8px;
    border-radius: var(--radius, 12px);
    font-weight: 700; font-size: 0.95rem;
}
.courses-zone-icon { font-size: 1.2rem; }
.courses-zone-title { flex: 1; }
.courses-zone-count {
    font-size: 0.75rem; font-weight: 600; opacity: 0.7;
}
.courses-zone--frais {
    background: color-mix(in srgb, #4FC3F7 12%, transparent);
    color: #0277BD;
}
.courses-zone--sec {
    background: color-mix(in srgb, var(--accent) 12%, transparent);
    color: var(--primary-dark, #8B4513);
}

/* Liste de courses résumé */
.liste-course-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    border-bottom: 1px solid var(--border, #eee);
}
.liste-course-item:last-child { border-bottom: none; }
.liste-course-check {
    width: 20px;
    height: 20px;
    accent-color: var(--accent);
    flex-shrink: 0;
}
.liste-course-nom {
    font-weight: 600;
    font-size: 0.88rem;
    flex: 1;
}
.liste-course-nom--done {
    text-decoration: line-through;
    opacity: 0.4;
}
</style>

<script>
(function() {
    var storageKey = 'courses_<?= (int)($semaineActive['id'] ?? 0) ?>';
    var state = JSON.parse(localStorage.getItem(storageKey) || '{}');

    // Appliquer l'état sauvegardé
    Object.keys(state).forEach(function(id) {
        var item = document.querySelector('.courses-item[data-id="' + id + '"]');
        if (!item) return;
        if (state[id] === 'stock') {
            item.classList.add('courses-item--stock');
            item.querySelector('.stock-btn--ok').classList.add('active');
        } else if (state[id] === 'acheter') {
            item.classList.add('courses-item--buy');
            item.querySelector('.stock-btn--buy').classList.add('active');
        }
    });

    window.toggleStock = function(btn, id, type) {
        var item = btn.closest('.courses-item');
        var okBtn = item.querySelector('.stock-btn--ok');
        var buyBtn = item.querySelector('.stock-btn--buy');

        if (state[id] === type) {
            delete state[id];
            item.classList.remove('courses-item--stock', 'courses-item--buy');
            okBtn.classList.remove('active');
            buyBtn.classList.remove('active');
        } else {
            state[id] = type;
            item.classList.remove('courses-item--stock', 'courses-item--buy');
            okBtn.classList.remove('active');
            buyBtn.classList.remove('active');
            if (type === 'stock') {
                item.classList.add('courses-item--stock');
                okBtn.classList.add('active');
            } else {
                item.classList.add('courses-item--buy');
                buyBtn.classList.add('active');
            }
        }
        localStorage.setItem(storageKey, JSON.stringify(state));
        updateListeCourses();
    };

    function updateListeCourses() {
        var container = document.getElementById('listeCourses');
        var card = document.getElementById('listeCoursesCard');
        var countEl = document.getElementById('listeCount');

        var toBuy = [];
        Object.keys(state).forEach(function(id) {
            if (state[id] !== 'acheter') return;
            var item = document.querySelector('.courses-item[data-id="' + id + '"]');
            if (!item) return;
            toBuy.push({
                id: id,
                nom: item.querySelector('.courses-item-nom').textContent.trim()
            });
        });

        if (toBuy.length === 0) {
            container.style.display = 'none';
            return;
        }

        container.style.display = 'block';
        countEl.textContent = '(' + toBuy.length + ')';
        card.textContent = '';

        toBuy.forEach(function(item) {
            var row = document.createElement('label');
            row.className = 'liste-course-item';

            var checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'liste-course-check';
            checkbox.addEventListener('change', function() {
                span.classList.toggle('liste-course-nom--done', this.checked);
            });

            var span = document.createElement('span');
            span.className = 'liste-course-nom';
            span.textContent = item.nom;

            row.appendChild(checkbox);
            row.appendChild(span);
            card.appendChild(row);
        });
    }

    updateListeCourses();
}());
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
