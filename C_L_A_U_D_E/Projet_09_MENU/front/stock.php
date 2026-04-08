<?php
/**
 * MealCoach V2 — Garde-manger (produits non-perissables)
 * Toggle on/off par produit, integre avec la liste de courses
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

// ── Categories non-perissables ──────────────────────────────────────────────
$catNonPerissables = ['condiments', 'epicerie', 'matieres_grasses', 'cereales', 'boissons', 'feculents'];

$produits = fetchAll(
    'SELECT p.id, p.nom, p.categorie, p.unite_mesure,
            CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END AS en_stock
     FROM produits p
     LEFT JOIN stock s ON s.produit_id = p.id
     WHERE p.exclu = 0
       AND p.categorie IN (' . implode(',', array_fill(0, count($catNonPerissables), '?')) . ')
     ORDER BY p.categorie, p.nom',
    $catNonPerissables
);

// Grouper par categorie
$parCategorie = [];
foreach ($produits as $p) {
    $parCategorie[$p['categorie']][] = $p;
}

// Labels et emojis
$catLabels = [
    'condiments'       => 'Condiments & Epices',
    'epicerie'         => 'Epicerie',
    'matieres_grasses' => 'Matieres grasses',
    'cereales'         => 'Cereales & Pain',
    'boissons'         => 'Boissons',
    'feculents'        => 'Feculents & Legumineuses',
];
$catEmojis = [
    'condiments'       => '🌿',
    'epicerie'         => '🏪',
    'matieres_grasses' => '🫒',
    'cereales'         => '🥣',
    'boissons'         => '☕',
    'feculents'        => '🍞',
];

$ordreCategories = ['condiments', 'epicerie', 'feculents', 'cereales', 'matieres_grasses', 'boissons'];
$categoriesOrdonnees = [];
foreach ($ordreCategories as $cat) {
    if (isset($parCategorie[$cat])) {
        $categoriesOrdonnees[$cat] = $parCategorie[$cat];
    }
}
foreach ($parCategorie as $cat => $items) {
    if (!isset($categoriesOrdonnees[$cat])) {
        $categoriesOrdonnees[$cat] = $items;
    }
}

$totalProduits = count($produits);
$totalAAcheter = count(array_filter($produits, fn($p) => $p['en_stock']));

// ── Rendu ─────────────────────────────────────────────────────────────────────
$pageTitle = 'Garde-manger';
$activeNav = 'plus';
ob_start();
?>

<div class="page-header">
    <h2>Garde-manger</h2>
    <div class="page-header-meta">
        <span class="page-header-badge" id="stockCounter"><?= $totalAAcheter ?> a acheter</span>
    </div>
</div>

<div class="gm-hint">
    Coche ce qu'il te manque → ca s'ajoute a ta liste de courses.
</div>

<div class="page-inner">
<?php foreach ($categoriesOrdonnees as $catKey => $items):
    $label = $catLabels[$catKey] ?? ucfirst(str_replace('_', ' ', $catKey));
    $emoji = $catEmojis[$catKey] ?? '📦';
    $aAcheterCat = count(array_filter($items, fn($p) => $p['en_stock']));
?>
<div class="gm-section">
    <div class="gm-section-header">
        <span class="gm-section-emoji"><?= $emoji ?></span>
        <span class="gm-section-title"><?= htmlspecialchars($label) ?></span>
        <span class="gm-section-count" data-cat="<?= $catKey ?>"><?= $aAcheterCat ? $aAcheterCat . ' a acheter' : '✓' ?></span>
    </div>
    <div class="gm-items">
        <?php foreach ($items as $p): ?>
        <div class="gm-item<?= $p['en_stock'] ? ' gm-item--needed' : '' ?>"
             data-id="<?= $p['id'] ?>" data-cat="<?= $catKey ?>"
             onclick="toggleStock(this, <?= $p['id'] ?>)">
            <div class="gm-item-check">
                <span class="gm-check-icon"><?= $p['en_stock'] ? '🛒' : '' ?></span>
            </div>
            <div class="gm-item-name"><?= htmlspecialchars($p['nom']) ?></div>
            <?php if ($p['en_stock']): ?>
            <span class="gm-item-tag">a acheter</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<style>
.gm-hint {
    font-size: 0.85rem; color: var(--text-muted);
    text-align: center; padding: 8px 16px 16px; line-height: 1.4;
}
.gm-section { margin-bottom: 12px; }
.gm-section-header {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 16px; font-weight: 700; font-size: 0.9rem;
    color: var(--text-muted);
}
.gm-section-emoji { font-size: 1rem; }
.gm-section-title { flex: 1; }
.gm-section-count {
    font-size: 0.75rem; font-weight: 600;
    background: var(--bg-card); padding: 2px 8px; border-radius: 10px;
    color: var(--text-muted);
}
.gm-items {
    background: var(--bg-card); border-radius: var(--radius);
    border: 1px solid var(--border); overflow: hidden;
}
.gm-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px; cursor: pointer; user-select: none;
    border-bottom: 1px solid color-mix(in srgb, var(--border) 50%, transparent);
    transition: background 0.15s;
}
.gm-item:last-child { border-bottom: none; }
.gm-item:active { background: color-mix(in srgb, var(--primary) 5%, transparent); }

.gm-item-check {
    width: 26px; height: 26px; border-radius: 8px;
    border: 2px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; transition: all 0.2s; flex-shrink: 0;
}
.gm-item--needed .gm-item-check {
    background: color-mix(in srgb, var(--primary) 15%, transparent);
    border-color: var(--primary);
}
.gm-item-name {
    font-size: 0.9rem; font-weight: 500; flex: 1;
}
.gm-item--needed .gm-item-name {
    color: var(--text); font-weight: 600;
}
.gm-item:not(.gm-item--needed) .gm-item-name {
    color: var(--text-muted);
}
.gm-item-tag {
    font-size: 0.7rem; font-weight: 600;
    background: color-mix(in srgb, var(--primary) 15%, transparent);
    color: var(--primary); padding: 2px 8px; border-radius: 6px;
    white-space: nowrap;
}
</style>

<script>
var totalNeeded = <?= $totalAAcheter ?>;

function updateCounters() {
    document.getElementById('stockCounter').textContent = totalNeeded ? totalNeeded + ' a acheter' : 'Tout en stock ✓';
    document.querySelectorAll('.gm-section').forEach(function(section) {
        var needed = section.querySelectorAll('.gm-item--needed').length;
        var badge = section.querySelector('.gm-section-count');
        if (badge) badge.textContent = needed ? needed + ' a acheter' : '✓';
    });
}

async function toggleStock(el, produitId) {
    var wasNeeded = el.classList.contains('gm-item--needed');

    // Optimistic UI
    el.classList.toggle('gm-item--needed');
    var icon = el.querySelector('.gm-check-icon');
    icon.textContent = wasNeeded ? '' : '🛒';

    var tag = el.querySelector('.gm-item-tag');
    if (wasNeeded && tag) { tag.remove(); }
    if (!wasNeeded && !tag) {
        var t = document.createElement('span');
        t.className = 'gm-item-tag';
        t.textContent = 'a acheter';
        el.appendChild(t);
    }

    totalNeeded += wasNeeded ? -1 : 1;
    updateCounters();

    try {
        var res = await api('stock', 'POST', { action: 'toggle', produit_id: produitId });
        if (!res.ok) { revert(); }
    } catch(e) { revert(); }

    function revert() {
        el.classList.toggle('gm-item--needed');
        icon.textContent = wasNeeded ? '🛒' : '';
        var tag2 = el.querySelector('.gm-item-tag');
        if (wasNeeded && !tag2) {
            var t2 = document.createElement('span');
            t2.className = 'gm-item-tag';
            t2.textContent = 'a acheter';
            el.appendChild(t2);
        } else if (!wasNeeded && tag2) { tag2.remove(); }
        totalNeeded += wasNeeded ? 1 : -1;
        updateCounters();
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
