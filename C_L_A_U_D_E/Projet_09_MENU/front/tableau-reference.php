<?php
/**
 * MealCoach — Tableau de référence nutritionnel
 * Toutes les équivalences groupées par catégorie, avec filtre de recherche
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

// ── Données ───────────────────────────────────────────────────────────────────
$equivalences = fetchAll(
    'SELECT * FROM equivalences WHERE actif = 1 ORDER BY categorie, nom'
);

// Grouper par catégorie
$parCategorie = [];
foreach ($equivalences as $equiv) {
    $cat = $equiv['categorie'] ?: 'Divers';
    $parCategorie[$cat][] = $equiv;
}

// Ordre souhaité des catégories (les autres seront à la fin)
$ordreCategories = [
    'Laitages', 'Céréales', 'Protéines PDJ', 'Viandes/Poissons',
    'Fromages', 'Fruits', 'Sucres lents', 'MG',
];
$categoriesOrdonnees = [];
foreach ($ordreCategories as $cat) {
    if (isset($parCategorie[$cat])) {
        $categoriesOrdonnees[$cat] = $parCategorie[$cat];
        unset($parCategorie[$cat]);
    }
}
// Ajouter les catégories restantes
foreach ($parCategorie as $cat => $items) {
    $categoriesOrdonnees[$cat] = $items;
}

// Toutes les données JSON pour le filtre client
$allItems = [];
foreach ($categoriesOrdonnees as $cat => $items) {
    foreach ($items as $item) {
        $allItems[] = [
            'id'       => $item['id'],
            'categorie'=> $cat,
            'nom'      => $item['nom'],
            'portion'  => $item['portion'] ?? '',
            'details'  => $item['details'] ?? $item['description'] ?? '',
        ];
    }
}

// ── Rendu ─────────────────────────────────────────────────────────────────────
$pageTitle = 'Tableau de référence';
$activeNav = 'plus';
ob_start();
?>

<h1 class="mb-8">Tableau de référence</h1>
<p class="text-muted text-sm mb-16">
    <?= count($equivalences) ?> équivalence<?= count($equivalences) > 1 ? 's' : '' ?>
    répartie<?= count($equivalences) > 1 ? 's' : '' ?> en
    <?= count($categoriesOrdonnees) ?> catégorie<?= count($categoriesOrdonnees) > 1 ? 's' : '' ?>
</p>

<!-- Filtre de recherche -->
<div style="position:sticky; top:0; z-index:50; background:var(--bg, #f5f5f5); padding:8px 0 12px;">
    <div style="position:relative;">
        <input
            type="search"
            id="search-equiv"
            placeholder="Rechercher un aliment…"
            autocomplete="off"
            style="
                width:100%;
                padding:10px 14px 10px 36px;
                border:1px solid var(--border, #ddd);
                border-radius:10px;
                font-size:0.95rem;
                background:var(--bg-card, #fff);
                color:var(--text, #222);
                box-sizing:border-box;
            "
        >
        <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted,#888); pointer-events:none;">
            &#9906;
        </span>
    </div>
    <div id="search-count" class="text-sm text-muted" style="margin-top:4px; min-height:1.2em;"></div>
</div>

<!-- Accordéons par catégorie -->
<div id="accordions-container">
<?php foreach ($categoriesOrdonnees as $categorie => $items):
    $catId = 'cat-' . preg_replace('/[^a-zA-Z0-9]/', '-', $categorie);
?>
<div class="accordion-item tableau-categorie"
     id="accord-<?= htmlspecialchars($catId) ?>"
     data-categorie="<?= htmlspecialchars($categorie) ?>">

    <div class="accordion-header" role="button" tabindex="0">
        <span class="accordion-title"><?= htmlspecialchars($categorie) ?></span>
        <span class="badge badge-neutral accord-count"><?= count($items) ?></span>
        <span class="accordion-arrow">▸</span>
    </div>

    <div class="accordion-body">
        <div class="tableau-items">
        <?php foreach ($items as $item):
            $details = $item['details'] ?? $item['description'] ?? '';
        ?>
            <div class="tableau-item"
                 data-nom="<?= htmlspecialchars(mb_strtolower($item['nom'])) ?>"
                 data-categorie="<?= htmlspecialchars(mb_strtolower($categorie)) ?>">
                <div class="tableau-item-main">
                    <span class="tableau-nom"><?= htmlspecialchars($item['nom']) ?></span>
                    <?php if (!empty($item['portion'])): ?>
                        <span class="tableau-portion"><?= htmlspecialchars($item['portion']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($details)): ?>
                    <div class="tableau-details"><?= htmlspecialchars($details) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </div>

</div>
<?php endforeach; ?>
</div>

<!-- Message aucun résultat -->
<p id="no-results" class="text-muted text-sm" style="display:none; padding:16px 0;">
    Aucun aliment trouvé pour cette recherche.
</p>

<style>
/* Accordion override pour le tableau */
.tableau-categorie .accordion-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px 16px;
    background: var(--bg-card, #fff);
    border: 1px solid var(--border, #eee);
    border-radius: 12px;
    cursor: pointer;
    margin-bottom: 4px;
    user-select: none;
}
.tableau-categorie.open .accordion-header {
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
    margin-bottom: 0;
}
.accordion-title {
    flex: 1;
    font-weight: 700;
    font-size: 0.95rem;
}
.accordion-arrow {
    font-size: 0.85rem;
    color: var(--text-muted, #888);
    transition: transform 0.2s;
}
.tableau-categorie.open .accordion-arrow { transform: rotate(90deg); }

.accordion-body {
    display: none;
    background: var(--bg-card, #fff);
    border: 1px solid var(--border, #eee);
    border-top: none;
    border-radius: 0 0 12px 12px;
    margin-bottom: 8px;
}
.tableau-categorie.open .accordion-body { display: block; }

.tableau-items { padding: 4px 0; }
.tableau-item {
    padding: 10px 16px;
    border-bottom: 1px solid var(--border, #f0f0f0);
}
.tableau-item:last-child { border-bottom: none; }
.tableau-item.hidden { display: none; }

.tableau-item-main {
    display: flex;
    align-items: baseline;
    gap: 8px;
    flex-wrap: wrap;
}
.tableau-nom {
    font-weight: 600;
    font-size: 0.9rem;
}
.tableau-portion {
    font-size: 0.8rem;
    color: var(--primary, #4CAF50);
    font-weight: 600;
    white-space: nowrap;
}
.tableau-details {
    font-size: 0.78rem;
    color: var(--text-muted, #888);
    margin-top: 2px;
}

/* Highlight de recherche */
mark {
    background: color-mix(in srgb, var(--primary, #4CAF50) 25%, transparent);
    border-radius: 2px;
    padding: 0 1px;
    color: inherit;
}
</style>

<script>
(function () {
    var allItems = <?= json_encode($allItems, JSON_UNESCAPED_UNICODE) ?>;
    var searchInput  = document.getElementById('search-equiv');
    var searchCount  = document.getElementById('search-count');
    var noResults    = document.getElementById('no-results');

    /* ── Filtre client ──────────────────────────────── */
    searchInput.addEventListener('input', function () {
        var q = this.value.trim().toLowerCase();
        filtrer(q);
    });

    function filtrer(q) {
        var accordions = document.querySelectorAll('.tableau-categorie');
        var totalVisible = 0;

        accordions.forEach(function (accord) {
            var items        = accord.querySelectorAll('.tableau-item');
            var catVisible   = 0;

            items.forEach(function (item) {
                var nom = item.dataset.nom || '';
                var visible = !q || nom.indexOf(q) !== -1;
                item.classList.toggle('hidden', !visible);
                if (visible) catVisible++;
            });

            // Masquer la catégorie entière si aucun item visible
            accord.style.display = catVisible === 0 ? 'none' : '';

            // Mettre à jour le compteur
            var badge = accord.querySelector('.accord-count');
            if (badge) badge.textContent = String(catVisible);

            // Ouvrir auto si recherche active et items trouvés
            if (q && catVisible > 0) {
                accord.classList.add('open');
            } else if (!q) {
                // Fermer au reset si la catégorie était ouverte par la recherche
                // (ne pas fermer si déjà ouverte manuellement — on laisse tel quel)
            }

            totalVisible += catVisible;
        });

        noResults.style.display = (q && totalVisible === 0) ? 'block' : 'none';

        if (q) {
            searchCount.textContent = totalVisible + ' résultat' + (totalVisible > 1 ? 's' : '') + ' pour "' + q + '"';
        } else {
            searchCount.textContent = '';
            // Restaurer tous les compteurs réels
            accordions.forEach(function (accord) {
                accord.style.display = '';
                var items  = accord.querySelectorAll('.tableau-item');
                var badge  = accord.querySelector('.accord-count');
                if (badge) badge.textContent = String(items.length);
            });
        }
    }
}());
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
