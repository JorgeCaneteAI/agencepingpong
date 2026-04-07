<?php
/**
 * MealCoach — Batch Cooking
 * Liste des tâches du batch cooking de la semaine active
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

// ── Semaine active ────────────────────────────────────────────────────────────
$semaine = fetchOne(
    "SELECT * FROM semaines WHERE statut = 'active' ORDER BY date_debut DESC LIMIT 1"
);

// ── Tâches batch ──────────────────────────────────────────────────────────────
$taches = [];
if ($semaine) {
    $taches = fetchAll(
        'SELECT * FROM batch_taches WHERE semaine_id = :sid ORDER BY ordre',
        [':sid' => $semaine['id']]
    );
}

// ── Rendu ─────────────────────────────────────────────────────────────────────
$pageTitle = 'Batch Cooking';
$activeNav = 'plus';
ob_start();
?>

<h1>Batch Cooking</h1>
<p class="text-muted text-sm mb-16">Dimanche matin</p>

<?php if (!$semaine): ?>
    <div class="alert alert-warning">
        Aucune semaine active.
        <a href="<?= BASE_URL ?>/admin/import" style="color:var(--warning);font-weight:700;">
            Importer un menu
        </a>
    </div>
<?php elseif (empty($taches)): ?>
    <p class="text-muted text-sm">Aucune tâche batch pour cette semaine.</p>
<?php else: ?>

<div class="card" style="padding:0 16px;">
    <?php foreach ($taches as $tache): ?>
    <div class="timeline-item">
        <div class="checklist-checkbox"
             style="cursor:pointer; flex-shrink:0;"
             onclick="this.closest('.timeline-item').classList.toggle('checked');
                      this.style.background = this.closest('.timeline-item').classList.contains('checked') ? 'var(--success)' : '';
                      this.style.borderColor = this.closest('.timeline-item').classList.contains('checked') ? 'var(--success)' : 'var(--border)';"
             title="Marquer comme fait"></div>
        <?php if (!empty($tache['heure'])): ?>
        <div class="timeline-time"><?= htmlspecialchars($tache['heure']) ?></div>
        <?php else: ?>
        <div class="timeline-time" style="min-width:50px;">&nbsp;</div>
        <?php endif; ?>
        <div class="timeline-info" style="flex:1;">
            <div class="timeline-action"><?= htmlspecialchars($tache['action'] ?? '') ?></div>
            <?php if (!empty($tache['equipement'])): ?>
                <div class="timeline-equip"><?= htmlspecialchars($tache['equipement']) ?></div>
            <?php endif; ?>
            <?php if (!empty($tache['resultat'])): ?>
                <div style="color:var(--primary);font-size:0.8rem;margin-top:4px;font-weight:600;">
                    → <?= htmlspecialchars($tache['resultat']) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($tache['duree'])): ?>
                <div class="timeline-equip">⏱ <?= (int) $tache['duree'] ?> min</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<style>
.timeline-item.checked {
    opacity: 0.5;
}
.timeline-item.checked .timeline-action {
    text-decoration: line-through;
}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
