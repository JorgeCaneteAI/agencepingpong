<?php
/**
 * MealCoach V2 — Tableau de référence nutritionnel
 * Toutes les équivalences groupées par catégorie, avec filtre de recherche
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

// ── Données ───────────────────────────────────────────────────────────────────
$equivalences = fetchAll(
    'SELECT * FROM equivalences ORDER BY categorie, description'
);

// Labels lisibles pour les catégories
$catLabels = [
    'laitage_pdj'    => 'Laitages (petit-dej)',
    'cereale_pdj'    => 'Cereales (petit-dej)',
    'proteine_pdj'   => 'Proteines (petit-dej)',
    'proteine_repas' => 'Proteines (repas)',
    'fromage_repas'  => 'Fromages',
    'fruit'          => 'Fruits',
    'sucre_lent'     => 'Sucres lents / Feculents',
    'matiere_grasse' => 'Matieres grasses',
    'legumes'        => 'Legumes',
];

// Grouper par catégorie
$parCategorie = [];
foreach ($equivalences as $equiv) {
    $cat = $equiv['categorie'] ?: 'divers';
    $parCategorie[$cat][] = $equiv;
}

// Ordre souhaité
$ordreCategories = [
    'laitage_pdj', 'cereale_pdj', 'proteine_pdj', 'proteine_repas',
    'fromage_repas', 'fruit', 'sucre_lent', 'matiere_grasse', 'legumes',
];
$categoriesOrdonnees = [];
foreach ($ordreCategories as $cat) {
    if (isset($parCategorie[$cat])) {
        $categoriesOrdonnees[$cat] = $parCategorie[$cat];
        unset($parCategorie[$cat]);
    }
}
foreach ($parCategorie as $cat => $items) {
    $categoriesOrdonnees[$cat] = $items;
}

// Emoji par catégorie
$catEmojis = [
    'laitage_pdj'    => '🥛',
    'cereale_pdj'    => '🥣',
    'proteine_pdj'   => '🥚',
    'proteine_repas' => '🍗',
    'fromage_repas'  => '🧀',
    'fruit'          => '🍎',
    'sucre_lent'     => '🍞',
    'matiere_grasse' => '🫒',
    'legumes'        => '🥬',
];

// ── Rendu ─────────────────────────────────────────────────────────────────────
$pageTitle = 'Tableau de reference';
$activeNav = 'plus';
ob_start();
?>

<div class="page-header">
    <h2>Tableau de reference</h2>
    <div class="page-header-meta">
        <span class="page-header-badge"><?= count($equivalences) ?> equivalences</span>
        <span class="page-header-badge"><?= count($categoriesOrdonnees) ?> categories</span>
    </div>
</div>

<!-- Filtre de recherche -->
<div class="search-bar-sticky">
    <div class="search-bar-wrap">
        <span class="search-bar-icon">🔍</span>
        <input type="search" id="search-equiv" placeholder="Rechercher un aliment…"
               autocomplete="off" class="search-bar-input">
    </div>
    <div id="search-count" class="search-count-text"></div>
</div>

<!-- Accordéons par catégorie -->
<div id="accordions-container" class="page-inner">
<?php foreach ($categoriesOrdonnees as $catKey => $items):
    $label = $catLabels[$catKey] ?? ucfirst(str_replace('_', ' ', $catKey));
    $emoji = $catEmojis[$catKey] ?? '📦';
    $catId = 'cat-' . $catKey;
?>
<div class="ref-accordion" id="<?= $catId ?>" data-categorie="<?= htmlspecialchars($catKey) ?>">
    <div class="ref-accordion-header" onclick="toggleRefAccordion(this)">
        <div class="ref-accordion-left">
            <span class="ref-accordion-emoji"><?= $emoji ?></span>
            <span class="ref-accordion-title"><?= htmlspecialchars($label) ?></span>
        </div>
        <div class="ref-accordion-right">
            <span class="ref-accordion-count"><?= count($items) ?></span>
            <span class="ref-accordion-arrow">▾</span>
        </div>
    </div>
    <div class="ref-accordion-body">
        <?php foreach ($items as $item): ?>
        <div class="ref-item" data-search="<?= htmlspecialchars(mb_strtolower($item['description'])) ?>">
            <div class="ref-item-desc"><?= htmlspecialchars($item['description']) ?></div>
            <?php if (!empty($item['quantite']) && !empty($item['unite'])): ?>
            <div class="ref-item-qty"><?= rtrim(rtrim(number_format($item['quantite'], 1, ',', ''), '0'), ',') ?> <?= htmlspecialchars($item['unite']) ?></div>
            <?php endif; ?>
            <?php if ($item['est_non_raffine']): ?>
            <span class="ref-badge-bio">non raffine</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<div id="no-results" class="alert-card" style="display:none;">
    <span class="alert-icon">🔍</span>
    <div class="alert-text">Aucun aliment trouve pour cette recherche.</div>
</div>

<style>
.search-bar-sticky {
    position: sticky; top: 0; z-index: 50;
    background: var(--bg); padding: 8px 0 12px;
}
.search-bar-wrap {
    position: relative;
}
.search-bar-icon {
    position: absolute; left: 12px; top: 50%;
    transform: translateY(-50%); pointer-events: none; font-size: 0.9rem;
}
.search-bar-input {
    width: 100%; padding: 12px 14px 12px 38px;
    border: 1px solid var(--border); border-radius: var(--radius);
    font-size: 0.95rem; background: var(--bg-card); color: var(--text);
    box-sizing: border-box;
}
.search-bar-input:focus { outline: none; border-color: var(--primary); }
.search-count-text {
    font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; min-height: 1.2em;
}

/* Accordion */
.ref-accordion { margin-bottom: 6px; }
.ref-accordion-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 16px; background: var(--bg-card);
    border: 1px solid var(--border); border-radius: var(--radius);
    cursor: pointer; user-select: none; transition: border-radius 0.2s;
}
.ref-accordion.open .ref-accordion-header {
    border-bottom-left-radius: 0; border-bottom-right-radius: 0;
}
.ref-accordion-left { display: flex; align-items: center; gap: 8px; }
.ref-accordion-emoji { font-size: 1.1rem; }
.ref-accordion-title { font-weight: 700; font-size: 0.95rem; }
.ref-accordion-right { display: flex; align-items: center; gap: 8px; }
.ref-accordion-count {
    background: var(--bg); color: var(--text-muted);
    padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 600;
}
.ref-accordion-arrow {
    font-size: 0.85rem; color: var(--text-muted);
    transition: transform 0.2s; display: inline-block;
}
.ref-accordion.open .ref-accordion-arrow { transform: rotate(180deg); }

.ref-accordion-body {
    display: none; background: var(--bg-card);
    border: 1px solid var(--border); border-top: none;
    border-radius: 0 0 var(--radius) var(--radius);
    overflow: hidden;
}
.ref-accordion.open .ref-accordion-body { display: block; }

.ref-item {
    display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap;
    padding: 10px 16px; border-bottom: 1px solid color-mix(in srgb, var(--border) 50%, transparent);
}
.ref-item:last-child { border-bottom: none; }
.ref-item.hidden { display: none; }
.ref-item-desc { font-size: 0.9rem; font-weight: 500; flex: 1; }
.ref-item-qty {
    font-size: 0.8rem; color: var(--primary); font-weight: 600; white-space: nowrap;
}
.ref-badge-bio {
    font-size: 0.7rem; background: color-mix(in srgb, var(--success) 15%, transparent);
    color: var(--success); padding: 1px 6px; border-radius: 6px; font-weight: 600;
}
</style>

<script>
function toggleRefAccordion(header) {
    header.parentElement.classList.toggle('open');
}

(function () {
    var searchInput = document.getElementById('search-equiv');
    var searchCount = document.getElementById('search-count');
    var noResults   = document.getElementById('no-results');

    searchInput.addEventListener('input', function () {
        var q = this.value.trim().toLowerCase();
        var accordions = document.querySelectorAll('.ref-accordion');
        var totalVisible = 0;

        accordions.forEach(function (accord) {
            var items = accord.querySelectorAll('.ref-item');
            var catVisible = 0;

            items.forEach(function (item) {
                var s = item.dataset.search || '';
                var visible = !q || s.indexOf(q) !== -1;
                item.classList.toggle('hidden', !visible);
                if (visible) catVisible++;
            });

            accord.style.display = catVisible === 0 ? 'none' : '';
            var badge = accord.querySelector('.ref-accordion-count');
            if (badge) badge.textContent = String(catVisible);

            if (q && catVisible > 0) {
                accord.classList.add('open');
            } else if (!q) {
                accord.classList.remove('open');
            }

            totalVisible += catVisible;
        });

        noResults.style.display = (q && totalVisible === 0) ? 'block' : 'none';

        if (q) {
            searchCount.textContent = totalVisible + ' resultat' + (totalVisible > 1 ? 's' : '') + ' pour "' + q + '"';
        } else {
            searchCount.textContent = '';
            accordions.forEach(function (accord) {
                accord.style.display = '';
                var items = accord.querySelectorAll('.ref-item');
                var badge = accord.querySelector('.ref-accordion-count');
                if (badge) badge.textContent = String(items.length);
            });
        }
    });
}());
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
