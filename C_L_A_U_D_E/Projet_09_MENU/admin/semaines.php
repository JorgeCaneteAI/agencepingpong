<?php
/**
 * MealCoach — Admin : Historique des semaines
 * Liste toutes les semaines importées, triées de la plus récente à la plus ancienne.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

// ── Récupérer toutes les semaines ─────────────────────────────────────────────
$semaines = fetchAll(
    "SELECT * FROM semaines ORDER BY date_debut DESC, imported_at DESC"
);

// ── Rendu ─────────────────────────────────────────────────────────────────────
$pageTitle = 'Historique semaines';
$activeNav = 'admin-historique';
ob_start();
?>

<h1 class="page-title">Historique semaines</h1>

<?php if (empty($semaines)): ?>
<div class="card">
    <p class="text-muted text-sm">Aucune semaine importée pour l'instant.</p>
    <a href="<?= BASE_URL ?>/admin/import" class="btn btn-primary btn-sm mt-8">
        Importer un menu
    </a>
</div>

<?php else: ?>

<?php foreach ($semaines as $sem): ?>
<div class="card mb-12">

    <div class="card-title" style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
        <span>
            Semaine <?= (int) ($sem['numero'] ?? 0) ?>
            <?php if (!empty($sem['saison'])): ?>
                &mdash; <?= htmlspecialchars(ucfirst($sem['saison'])) ?>
            <?php endif; ?>
        </span>
        <?php
        $statut = $sem['statut'] ?? 'archive';
        if ($statut === 'active'):
        ?>
            <span class="badge badge-success">Active</span>
        <?php else: ?>
            <span class="badge badge-neutral">Archive</span>
        <?php endif; ?>
    </div>

    <?php if (!empty($sem['date_debut']) && !empty($sem['date_fin'])): ?>
    <div class="stat-row">
        <span class="stat-label">Dates</span>
        <span class="stat-value">
            <?= date('d/m/Y', strtotime($sem['date_debut'])) ?>
            → <?= date('d/m/Y', strtotime($sem['date_fin'])) ?>
        </span>
    </div>
    <?php elseif (!empty($sem['date_debut'])): ?>
    <div class="stat-row">
        <span class="stat-label">Début</span>
        <span class="stat-value"><?= date('d/m/Y', strtotime($sem['date_debut'])) ?></span>
    </div>
    <?php endif; ?>

    <?php if (!empty($sem['budget_estime'])): ?>
    <div class="stat-row">
        <span class="stat-label">Budget estimé</span>
        <span class="stat-value"><?= number_format((float) $sem['budget_estime'], 0, ',', '') ?> €</span>
    </div>
    <?php endif; ?>

    <?php if (!empty($sem['imported_at'])): ?>
    <div class="stat-row">
        <span class="stat-label">Importé le</span>
        <span class="stat-value text-sm text-muted">
            <?= date('d/m/Y à H\hi', strtotime($sem['imported_at'])) ?>
        </span>
    </div>
    <?php endif; ?>

</div>
<?php endforeach; ?>

<p class="text-muted text-sm" style="text-align:center; padding:8px 0;">
    <?= count($semaines) ?> semaine<?= count($semaines) > 1 ? 's' : '' ?> au total
</p>

<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
