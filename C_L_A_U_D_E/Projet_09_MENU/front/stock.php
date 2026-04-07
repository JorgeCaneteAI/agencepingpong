<?php
/**
 * MealCoach — Garde-manger (stock)
 * Consultation du stock par catégorie
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/models/Stock.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

// ── Données stock ─────────────────────────────────────────────────────────────
$stockItems = Stock::getAll();
$alertes    = Stock::alertesPeremption(3);

// Grouper par catégorie
$parCategorie = [];
foreach ($stockItems as $item) {
    $cat = $item['categorie'] ?: 'Divers';
    $parCategorie[$cat][] = $item;
}
ksort($parCategorie);

// ── Rendu ─────────────────────────────────────────────────────────────────────
$pageTitle = 'Garde-manger';
$activeNav = 'plus';
ob_start();
?>

<h1 class="mb-16">Garde-manger</h1>

<?php if (!empty($alertes)): ?>
<div class="alert alert-warning mb-16">
    ⚠ <?= count($alertes) ?> produit<?= count($alertes) > 1 ? 's' : '' ?>
    arrive<?= count($alertes) > 1 ? 'nt' : '' ?> à péremption dans les 3 jours.
</div>
<?php endif; ?>

<?php if (empty($stockItems)): ?>
    <p class="text-muted text-sm">Le stock est vide.</p>
    <a href="<?= BASE_URL ?>/admin/stock" class="btn btn-outline mt-16">
        Gérer le stock
    </a>
<?php else: ?>

<?php foreach ($parCategorie as $categorie => $items): ?>
<div class="card">
    <div class="card-title"><?= htmlspecialchars(ucfirst($categorie)) ?></div>
    <?php foreach ($items as $item):
        // Vérifier si l'item est en alerte de péremption
        $enAlerte = false;
        if (!empty($item['date_peremption'])) {
            $diff = (strtotime($item['date_peremption']) - time()) / 86400;
            $enAlerte = $diff <= 3;
        }
    ?>
    <div class="meal-item">
        <div class="meal-info">
            <div class="meal-name"><?= htmlspecialchars($item['nom']) ?></div>
            <?php if (!empty($item['quantite'])): ?>
            <div class="meal-desc">
                <?= htmlspecialchars((string) $item['quantite']) ?>
                <?= htmlspecialchars($item['unite'] ?? $item['unite_mesure'] ?? '') ?>
            </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($item['date_peremption'])): ?>
            <span class="badge <?= $enAlerte ? 'badge-danger' : 'badge-neutral' ?>">
                <?= date('d/m', strtotime($item['date_peremption'])) ?>
            </span>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>

<div class="mt-16">
    <a href="<?= BASE_URL ?>/admin/stock" class="btn btn-outline btn-sm">
        Gérer le stock
    </a>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
