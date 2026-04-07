<?php
/**
 * MealCoach — Liste de courses
 * Items cochables groupés par rayon
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/models/Courses.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

// ── Semaine active ────────────────────────────────────────────────────────────
$semaine = fetchOne(
    "SELECT * FROM semaines WHERE statut = 'active' ORDER BY date_debut DESC LIMIT 1"
);

// ── Liste + items + stats ─────────────────────────────────────────────────────
$liste      = null;
$items      = [];
$stats      = ['total' => 0, 'achetes' => 0, 'cout_estime' => 0];
$parRayon   = [];

if ($semaine) {
    $liste = Courses::getListeBySemaine((int) $semaine['id']);
    if ($liste) {
        $items = Courses::getItemsBySemaine((int) $semaine['id']);
        $stats = Courses::statsListe((int) $liste['id']);

        // Grouper par rayon
        foreach ($items as $item) {
            $rayon = $item['categorie_rayon'] ?: 'Divers';
            $parRayon[$rayon][] = $item;
        }
        ksort($parRayon);
    }
}

$pctFait = $stats['total'] > 0
    ? round(($stats['achetes'] / $stats['total']) * 100)
    : 0;

// ── Rendu ─────────────────────────────────────────────────────────────────────
$pageTitle = 'Courses';
$activeNav = 'courses';
ob_start();
?>

<h1 class="mb-16">Liste de courses</h1>

<?php if (!$semaine || !$liste): ?>
    <div class="alert alert-warning">
        <?php if (!$semaine): ?>
            Aucune semaine active.
            <a href="<?= BASE_URL ?>/admin/import" style="color:var(--warning);font-weight:700;">
                Importer un menu
            </a>
        <?php else: ?>
            Aucune liste de courses générée pour cette semaine.
        <?php endif; ?>
    </div>
<?php else: ?>

<!-- Progression -->
<div class="card">
    <div class="flex-between mb-8">
        <span class="text-sm" style="font-weight:600;">Progression</span>
        <span class="progress-label"><?= $stats['achetes'] ?> / <?= $stats['total'] ?></span>
    </div>
    <div class="progress-bar">
        <div class="progress-fill" style="width:<?= $pctFait ?>%;"></div>
    </div>
</div>

<!-- Items par rayon -->
<?php foreach ($parRayon as $rayon => $rayonItems): ?>
<div class="card">
    <div class="card-title"><?= htmlspecialchars(ucfirst($rayon)) ?></div>
    <?php foreach ($rayonItems as $item):
        $nom = !empty($item['produit_nom']) ? $item['produit_nom'] : ($item['nom_brut'] ?? '');
        $isAchete = (bool) $item['achete'];
    ?>
    <div class="checklist-item<?= $isAchete ? ' checked' : '' ?>"
         id="course-item-<?= (int) $item['id'] ?>">
        <div class="checklist-checkbox"
             data-id="<?= (int) $item['id'] ?>"
             onclick="toggleCourse(<?= (int) $item['id'] ?>)"
             style="cursor:pointer;"></div>
        <div class="checklist-info">
            <div class="checklist-name"><?= htmlspecialchars($nom) ?></div>
            <?php if (!empty($item['quantite']) || !empty($item['unite'])): ?>
            <div class="checklist-detail">
                <?= htmlspecialchars((string) ($item['quantite'] ?? '')) ?>
                <?= htmlspecialchars($item['unite'] ?? '') ?>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($item['en_stock']): ?>
            <span class="badge badge-stock">En stock</span>
        <?php endif; ?>
        <?php if (!empty($item['prix_estime'])): ?>
            <span class="checklist-price">
                ~<?= number_format((float) $item['prix_estime'], 2, ',', '') ?>&nbsp;€
            </span>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>

<!-- Sticky total -->
<?php if ($stats['cout_estime'] > 0): ?>
<div class="sticky-bottom">
    <div class="flex-between">
        <span style="font-weight:600;">Total estimé</span>
        <span style="font-weight:700;color:var(--primary);">
            ~<?= number_format((float) $stats['cout_estime'], 2, ',', '') ?>&nbsp;€
        </span>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<script>
async function toggleCourse(id) {
    const itemEl = document.getElementById('course-item-' + id);
    if (!itemEl) return;

    // Optimistic UI
    itemEl.classList.toggle('checked');

    try {
        await api('courses', 'POST', { action: 'toggle', id: id });
    } catch (err) {
        // Revert on error
        itemEl.classList.toggle('checked');
        console.warn('[MealCoach] toggleCourse error:', err);
    }

    // Update progress bar
    const allItems     = document.querySelectorAll('.checklist-item');
    const checkedItems = document.querySelectorAll('.checklist-item.checked');
    const total   = allItems.length;
    const done    = checkedItems.length;
    const percent = total > 0 ? Math.round((done / total) * 100) : 0;

    const fill  = document.querySelector('.progress-fill');
    const label = document.querySelector('.progress-label');
    if (fill)  fill.style.width = percent + '%';
    if (label) label.textContent = done + ' / ' + total;
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
