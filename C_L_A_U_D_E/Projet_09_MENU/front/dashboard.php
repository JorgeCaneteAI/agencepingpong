<?php
/**
 * MealCoach — Dashboard (accueil)
 * Menu du jour + suivi rapide + alertes péremption
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/models/Suivi.php';
require_once __DIR__ . '/../src/models/Stock.php';

// ── Auth ──────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../auth.php';
requireLogin();

// ── Date & jour de la semaine ─────────────────────────────────────────────────
$today      = date('Y-m-d');
$jourSemaine = (int) date('N') - 1; // 0=lundi … 6=dimanche
$nomsJours  = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
$nomJour    = $nomsJours[$jourSemaine];

// ── Semaine active ────────────────────────────────────────────────────────────
$semaine = fetchOne(
    "SELECT * FROM semaines WHERE statut = 'active' ORDER BY date_debut DESC LIMIT 1"
);

// ── Menu du jour ──────────────────────────────────────────────────────────────
$menuRepas = [];
if ($semaine) {
    $menuJour = fetchOne(
        'SELECT * FROM menu_jours WHERE semaine_id = :sid AND jour = :jour',
        [':sid' => $semaine['id'], ':jour' => $jourSemaine]
    );
    if ($menuJour) {
        $rows = fetchAll(
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
        foreach ($rows as $r) {
            $menuRepas[$r['type_repas']] = $r;
        }
    }
}

// ── Suivi du jour ─────────────────────────────────────────────────────────────
$suiviJour  = Suivi::getJour($today);
$suiviRepas = [];
if ($suiviJour) {
    foreach (Suivi::getRepas((int) $suiviJour['id']) as $sr) {
        $suiviRepas[$sr['type_repas']] = $sr;
    }
}

// ── Alertes péremption ────────────────────────────────────────────────────────
$alertes = Stock::alertesPeremption(3);

// ── Données pour les sliders ───────────────────────────────────────────────────
$poids  = $suiviJour ? (float)  ($suiviJour['poids']  ?? 70)  : 70;
$humeur = $suiviJour ? (int)    ($suiviJour['humeur'] ?? 3)   : 3;
$energie = $suiviJour ? (int)   ($suiviJour['energie'] ?? 3)  : 3;

// ── Helpers ───────────────────────────────────────────────────────────────────
$repasConfig = [
    'petit_dej' => ['emoji' => '🌅', 'label' => 'Petit déjeuner'],
    'dejeuner'  => ['emoji' => '☀️', 'label' => 'Déjeuner'],
    'encas'     => ['emoji' => '🍎', 'label' => 'En-cas 16h'],
    'diner'     => ['emoji' => '🌙', 'label' => 'Dîner'],
    'dessert'   => ['emoji' => '🍮', 'label' => 'Dessert'],
];

function statutBadge(string $statut): string {
    return match ($statut) {
        'mange'   => '<span class="badge badge-success">Mangé</span>',
        'saute'   => '<span class="badge badge-neutral">Sauté</span>',
        'craquage'=> '<span class="badge badge-danger">Craquage</span>',
        default   => '<span class="badge badge-warning">Prévu</span>',
    };
}

// ── Rendu ─────────────────────────────────────────────────────────────────────
$pageTitle = 'Accueil';
$activeNav = 'dashboard';
ob_start();
?>

<div style="display:flex; align-items:baseline; gap:8px; margin-bottom:4px;">
    <h1><?= htmlspecialchars($nomJour) ?></h1>
    <span class="text-muted"><?= date('d/m', strtotime($today)) ?></span>
</div>

<?php if ($semaine): ?>
<p class="text-sm text-muted mb-16">
    Semaine <?= (int) $semaine['numero'] ?>
    <?php if (!empty($semaine['saison'])): ?>
        &middot; <?= htmlspecialchars(ucfirst($semaine['saison'])) ?>
    <?php endif; ?>
</p>
<?php else: ?>
<p class="text-sm text-muted mb-16">
    Aucune semaine active —
    <a href="<?= BASE_URL ?>/admin/import" style="color:var(--primary);">Importer un menu</a>
</p>
<?php endif; ?>

<!-- Card Menu du jour -->
<div class="card">
    <div class="card-title">Menu du jour</div>
    <?php foreach ($repasConfig as $type => $cfg): ?>
        <?php
        $repas  = $menuRepas[$type] ?? null;
        $suivi  = $suiviRepas[$type] ?? null;
        $statut = $suivi ? $suivi['statut'] : 'prevu';
        ?>
        <div class="meal-item">
            <span class="meal-emoji"><?= $cfg['emoji'] ?></span>
            <div class="meal-info">
                <div class="meal-name">
                    <?= $repas ? htmlspecialchars($repas['nom_plat']) : '<span class="text-muted">—</span>' ?>
                </div>
                <div class="meal-desc"><?= htmlspecialchars($cfg['label']) ?></div>
            </div>
            <?php if ($repas): ?>
                <?= statutBadge($statut) ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <div class="mt-8">
        <a href="<?= BASE_URL ?>/semaine" class="btn btn-outline btn-sm">Voir la semaine</a>
    </div>
</div>

<!-- Card Suivi rapide -->
<div class="card">
    <div class="card-title">Suivi rapide</div>

    <div class="slider-group">
        <label for="sl-poids">
            Poids (kg)
            <span id="sl-poids-val"><?= number_format($poids, 1) ?></span>
        </label>
        <input type="range" id="sl-poids" min="50" max="120" step="0.1"
               value="<?= htmlspecialchars((string) $poids) ?>">
    </div>

    <div class="slider-group">
        <label for="sl-humeur">
            Humeur
            <span id="sl-humeur-val"><?= $humeur ?></span>
        </label>
        <input type="range" id="sl-humeur" min="1" max="5" step="1"
               value="<?= $humeur ?>">
    </div>

    <div class="slider-group">
        <label for="sl-energie">
            Énergie
            <span id="sl-energie-val"><?= $energie ?></span>
        </label>
        <input type="range" id="sl-energie" min="1" max="5" step="1"
               value="<?= $energie ?>">
    </div>

    <button type="button" class="btn btn-primary btn-full" id="btn-save-suivi">
        Enregistrer
    </button>
    <p id="suivi-feedback" class="text-sm text-muted mt-8" style="display:none;"></p>
</div>

<?php if (!empty($alertes)): ?>
<!-- Card Alertes péremption -->
<div class="card">
    <div class="card-title" style="color:var(--warning);">⚠ Alertes péremption</div>
    <?php foreach ($alertes as $alerte): ?>
        <div class="meal-item">
            <div class="meal-info">
                <div class="meal-name"><?= htmlspecialchars($alerte['nom']) ?></div>
            </div>
            <span class="badge badge-warning">
                <?= htmlspecialchars(date('d/m', strtotime($alerte['date_peremption']))) ?>
            </span>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
document.getElementById('btn-save-suivi').addEventListener('click', async function () {
    const poids   = parseFloat(document.getElementById('sl-poids').value);
    const humeur  = parseInt(document.getElementById('sl-humeur').value, 10);
    const energie = parseInt(document.getElementById('sl-energie').value, 10);
    const fb = document.getElementById('suivi-feedback');

    try {
        const res = await api('suivi', 'POST', {
            action: 'maj_jour',
            date: '<?= $today ?>',
            poids,
            humeur,
            energie
        });
        fb.style.display = 'block';
        fb.textContent = res.ok ? 'Sauvegardé ✓' : (res.error || 'Erreur');
        fb.style.color = res.ok ? 'var(--success)' : 'var(--danger)';
    } catch (err) {
        fb.style.display = 'block';
        fb.textContent = 'Erreur réseau';
        fb.style.color = 'var(--danger)';
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
