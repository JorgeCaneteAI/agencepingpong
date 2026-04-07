<?php
/**
 * MealCoach — Vue semaine
 * Navigation par jours + accordéon des repas
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

// ── Semaine active ────────────────────────────────────────────────────────────
$semaine = fetchOne(
    "SELECT * FROM semaines WHERE statut = 'active' ORDER BY date_debut DESC LIMIT 1"
);

// ── Jour sélectionné ──────────────────────────────────────────────────────────
$todayJour    = (int) date('N') - 1; // 0=lundi … 6=dimanche
$selectedJour = isset($_GET['jour']) ? max(0, min(6, (int) $_GET['jour'])) : $todayJour;

$nomsJoursCourts  = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
$nomsJoursComplets = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

// ── Repas du jour sélectionné ─────────────────────────────────────────────────
$menuRepas = [];
if ($semaine) {
    $menuJour = fetchOne(
        'SELECT * FROM menu_jours WHERE semaine_id = :sid AND jour = :jour',
        [':sid' => $semaine['id'], ':jour' => $selectedJour]
    );
    if ($menuJour) {
        $menuRepas = fetchAll(
            'SELECT * FROM menu_repas WHERE menu_jour_id = :mjid
             ORDER BY CASE type_repas
                WHEN \'petit_dej\' THEN 1
                WHEN \'dejeuner\'  THEN 2
                WHEN \'encas\'     THEN 3
                WHEN \'diner\'     THEN 4
                WHEN \'dessert\'   THEN 5
                ELSE 6 END',
            [':mjid' => $menuJour['id']]
        );
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
$repasLabels = [
    'petit_dej' => ['emoji' => '🌅', 'label' => 'Petit déjeuner'],
    'dejeuner'  => ['emoji' => '☀️', 'label' => 'Déjeuner'],
    'encas'     => ['emoji' => '🍎', 'label' => 'En-cas 16h'],
    'diner'     => ['emoji' => '🌙', 'label' => 'Dîner'],
    'dessert'   => ['emoji' => '🍮', 'label' => 'Dessert'],
];

// ── Rendu ─────────────────────────────────────────────────────────────────────
$pageTitle = 'Semaine';
$activeNav = 'semaine';
ob_start();
?>

<?php if (!$semaine): ?>
    <h1 class="mb-16">Menu de la semaine</h1>
    <div class="alert alert-warning">
        Aucune semaine active.
        <a href="<?= BASE_URL ?>/admin/import" style="color:var(--warning);font-weight:700;">
            Importer un menu
        </a>
    </div>
<?php else: ?>

<h2 class="mb-16">
    Semaine <?= (int) $semaine['numero'] ?>
    <?php if (!empty($semaine['saison'])): ?>
        <span class="badge badge-neutral"><?= htmlspecialchars(ucfirst($semaine['saison'])) ?></span>
    <?php endif; ?>
    <?php if (!empty($semaine['date_debut']) && !empty($semaine['date_fin'])): ?>
        <span class="text-muted text-sm">
            <?= date('d/m', strtotime($semaine['date_debut'])) ?>
            – <?= date('d/m', strtotime($semaine['date_fin'])) ?>
        </span>
    <?php endif; ?>
</h2>

<!-- Tabs jours -->
<div class="tabs" role="tablist">
    <?php for ($j = 0; $j <= 6; $j++): ?>
        <a href="<?= BASE_URL ?>/semaine?jour=<?= $j ?>"
           class="tab<?= $j === $selectedJour ? ' active' : '' ?>"
           role="tab"
           aria-selected="<?= $j === $selectedJour ? 'true' : 'false' ?>">
            <?= $nomsJoursCourts[$j] ?>
        </a>
    <?php endfor; ?>
</div>

<h3 class="mb-16"><?= $nomsJoursComplets[$selectedJour] ?></h3>

<?php if (empty($menuRepas)): ?>
    <p class="text-muted text-sm">Aucun repas planifié pour ce jour.</p>
<?php else: ?>
    <div class="card" style="padding:0 16px;">
        <?php foreach ($menuRepas as $repas):
            $cfg = $repasLabels[$repas['type_repas']] ?? ['emoji' => '🍽️', 'label' => ucfirst($repas['type_repas'])];
        ?>
        <div class="accordion-item" data-repas-id="<?= (int) $repas['id'] ?>"
             data-type="<?= htmlspecialchars($repas['type_repas']) ?>">
            <div class="accordion-header">
                <span><?= $cfg['emoji'] ?> <?= htmlspecialchars($cfg['label']) ?></span>
            </div>
            <div class="accordion-body">
                <p style="color:var(--text);font-weight:600;margin-bottom:6px;">
                    <?= htmlspecialchars($repas['nom_plat']) ?>
                </p>
                <?php if (!empty($repas['contenu'])): ?>
                    <p style="margin-bottom:12px;"><?= nl2br(htmlspecialchars($repas['contenu'])) ?></p>
                <?php endif; ?>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button type="button" class="btn btn-primary btn-sm"
                            onclick="marquerRepas(<?= (int) $repas['id'] ?>, '<?= htmlspecialchars($repas['type_repas']) ?>', 'mange', this)">
                        Mangé ✓
                    </button>
                    <button type="button" class="btn btn-outline btn-sm"
                            onclick="marquerRepas(<?= (int) $repas['id'] ?>, '<?= htmlspecialchars($repas['type_repas']) ?>', 'saute', this)">
                        Sauté
                    </button>
                </div>
                <p class="repas-feedback text-sm mt-8" style="display:none;"></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php endif; ?>

<script>
async function marquerRepas(repasId, typeRepas, statut, btn) {
    const item    = btn.closest('.accordion-item');
    const feedback = item ? item.querySelector('.repas-feedback') : null;

    try {
        const res = await api('suivi', 'POST', {
            action: 'maj_repas',
            type_repas: typeRepas,
            statut: statut,
            date: new Date().toISOString().slice(0, 10)
        });
        if (feedback) {
            feedback.style.display = 'block';
            feedback.textContent = res.ok
                ? (statut === 'mange' ? 'Repas marqué comme mangé ✓' : 'Repas sauté')
                : (res.error || 'Erreur');
            feedback.style.color = res.ok ? 'var(--success)' : 'var(--danger)';
        }
    } catch (err) {
        if (feedback) {
            feedback.style.display = 'block';
            feedback.textContent = 'Erreur réseau';
            feedback.style.color = 'var(--danger)';
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
