<?php
/**
 * MealCoach — Admin : Dashboard
 * Vue d'ensemble de la semaine active, stats repas et suivi poids.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

// ── Semaine active ────────────────────────────────────────────────────────────
$semaine = fetchOne(
    "SELECT * FROM semaines WHERE statut = 'active' ORDER BY date_debut DESC LIMIT 1"
);

// ── Stats repas ───────────────────────────────────────────────────────────────
$totalRepas   = 0;
$mangesRepas  = 0;
$craquages    = 0;
$dernierPoids = null;

if ($semaine) {
    $sid = (int) $semaine['id'];

    // Total repas prévus dans la semaine active
    $rowTotal = fetchOne(
        'SELECT COUNT(*) AS total
         FROM menu_repas mr
         JOIN menu_jours mj ON mr.menu_jour_id = mj.id
         WHERE mj.semaine_id = :sid',
        [':sid' => $sid]
    );
    $totalRepas = (int) ($rowTotal['total'] ?? 0);

    // Repas mangés (suivi)
    $rowManges = fetchOne(
        "SELECT COUNT(*) AS total
         FROM suivi_repas sr
         JOIN suivi_jours sj ON sr.suivi_jour_id = sj.id
         WHERE sj.semaine_id = :sid AND sr.statut = 'mange'",
        [':sid' => $sid]
    );
    $mangesRepas = (int) ($rowManges['total'] ?? 0);

    // Craquages
    $rowCraquages = fetchOne(
        "SELECT COUNT(*) AS total
         FROM suivi_repas sr
         JOIN suivi_jours sj ON sr.suivi_jour_id = sj.id
         WHERE sj.semaine_id = :sid AND sr.statut = 'craquage'",
        [':sid' => $sid]
    );
    $craquages = (int) ($rowCraquages['total'] ?? 0);

    // Dernier poids enregistré pour la semaine
    $rowPoids = fetchOne(
        "SELECT poids FROM suivi_jours
         WHERE semaine_id = :sid AND poids IS NOT NULL
         ORDER BY date DESC LIMIT 1",
        [':sid' => $sid]
    );
    if (!$rowPoids) {
        // suivi_jours n'a pas forcément semaine_id — on cherche par dates
        if (!empty($semaine['date_debut']) && !empty($semaine['date_fin'])) {
            $rowPoids = fetchOne(
                "SELECT poids FROM suivi_jours
                 WHERE date BETWEEN :debut AND :fin AND poids IS NOT NULL
                 ORDER BY date DESC LIMIT 1",
                [':debut' => $semaine['date_debut'], ':fin' => $semaine['date_fin']]
            );
        }
    }
    $dernierPoids = $rowPoids ? (float) $rowPoids['poids'] : null;
}

// Progression %
$progressPct = $totalRepas > 0 ? round($mangesRepas / $totalRepas * 100) : 0;

// ── Rendu ─────────────────────────────────────────────────────────────────────
$pageTitle = 'Dashboard admin';
$activeNav = 'admin-dashboard';
ob_start();
?>

<h1 class="page-title">Dashboard</h1>

<!-- Card semaine active -->
<?php if ($semaine): ?>
<div class="card mb-16">
    <div class="card-title">
        Semaine active
        <span class="badge badge-success" style="float:right;">Active</span>
    </div>

    <div class="stat-row">
        <span class="stat-label">Numéro</span>
        <span class="stat-value">Semaine <?= (int) $semaine['numero'] ?></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Saison</span>
        <span class="stat-value"><?= htmlspecialchars(ucfirst((string) ($semaine['saison'] ?? '—'))) ?></span>
    </div>
    <?php if (!empty($semaine['date_debut']) && !empty($semaine['date_fin'])): ?>
    <div class="stat-row">
        <span class="stat-label">Dates</span>
        <span class="stat-value">
            <?= date('d/m', strtotime($semaine['date_debut'])) ?>
            →
            <?= date('d/m/Y', strtotime($semaine['date_fin'])) ?>
        </span>
    </div>
    <?php endif; ?>
    <?php if (!empty($semaine['budget_estime'])): ?>
    <div class="stat-row">
        <span class="stat-label">Budget estimé</span>
        <span class="stat-value"><?= number_format((float) $semaine['budget_estime'], 0, ',', '') ?> €</span>
    </div>
    <?php endif; ?>
</div>

<!-- Card stats repas -->
<div class="card mb-16">
    <div class="card-title">Suivi repas</div>

    <div class="stat-row">
        <span class="stat-label">Repas mangés</span>
        <span class="stat-value"><?= $mangesRepas ?> / <?= $totalRepas ?></span>
    </div>

    <?php if ($totalRepas > 0): ?>
    <div class="progress-bar-wrap" style="margin:8px 0 12px;">
        <div class="progress-bar-track">
            <div class="progress-bar-fill" style="width:<?= $progressPct ?>%;"></div>
        </div>
        <span class="progress-bar-label"><?= $progressPct ?> %</span>
    </div>
    <?php endif; ?>

    <div class="stat-row">
        <span class="stat-label">Craquages</span>
        <span class="stat-value <?= $craquages > 0 ? 'text-danger' : '' ?>">
            <?= $craquages ?>
        </span>
    </div>

    <?php if ($dernierPoids !== null): ?>
    <div class="stat-row">
        <span class="stat-label">Dernier poids</span>
        <span class="stat-value"><?= number_format($dernierPoids, 1, ',', '') ?> kg</span>
    </div>
    <?php endif; ?>
</div>

<?php else: ?>
<div class="card mb-16">
    <div class="card-title">Aucune semaine active</div>
    <p class="text-muted text-sm">
        Importez un menu .md pour démarrer une nouvelle semaine.
    </p>
</div>
<?php endif; ?>

<!-- Actions rapides -->
<div class="card">
    <div class="card-title">Actions</div>
    <a href="<?= BASE_URL ?>/admin/import" class="btn btn-primary btn-full mb-8">
        &#8679; Importer un menu
    </a>
    <a href="<?= BASE_URL ?>/admin/catalogue" class="btn btn-outline btn-full mb-8">
        &#9776; Catalogue produits
    </a>
    <a href="<?= BASE_URL ?>/admin/stock" class="btn btn-outline btn-full mb-8">
        &#9878; Gestion du stock
    </a>
    <a href="<?= BASE_URL ?>/admin/semaines" class="btn btn-outline btn-full">
        &#9737; Historique semaines
    </a>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
