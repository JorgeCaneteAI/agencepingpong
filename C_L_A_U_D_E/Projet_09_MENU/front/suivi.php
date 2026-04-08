<?php
/**
 * MealCoach V2 — Suivi & Progression
 * Tableau de bord : courbes poids, humeur/énergie, taux de respect menu
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/models/Suivi.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

$today = date('Y-m-d');

// ── Historique complet ──────────────────────────────────────────────────────
$historique = fetchAll(
    'SELECT * FROM suivi_jours ORDER BY date ASC'
);

// ── Stats repas par jour ────────────────────────────────────────────────────
$repasStats = [];
foreach ($historique as $jour) {
    $repas = fetchAll(
        'SELECT statut, COUNT(*) as nb FROM suivi_repas WHERE suivi_jour_id = :id GROUP BY statut',
        [':id' => (int) $jour['id']]
    );
    $stats = ['mange' => 0, 'saute' => 0, 'craquage' => 0, 'total' => 0];
    foreach ($repas as $r) {
        $stats[$r['statut']] = (int) $r['nb'];
        $stats['total'] += (int) $r['nb'];
    }
    $repasStats[$jour['date']] = $stats;
}

// ── Données du jour pour le formulaire ──────────────────────────────────────
$suiviJour = Suivi::getJour($today);
$suiviRepas = [];
if ($suiviJour) {
    foreach (Suivi::getRepas((int) $suiviJour['id']) as $sr) {
        $suiviRepas[$sr['type_repas']] = $sr;
    }
}
$poids   = $suiviJour ? (float) ($suiviJour['poids']   ?? 70) : 70;
$humeur  = $suiviJour ? (int)   ($suiviJour['humeur']  ?? 3)  : 3;
$energie = $suiviJour ? (int)   ($suiviJour['energie'] ?? 3)  : 3;
$sommeil = $suiviJour ? (int)   ($suiviJour['sommeil'] ?? 7)  : 7;
$note    = $suiviJour ? ($suiviJour['note'] ?? '') : '';

// ── Calculs agrégés ─────────────────────────────────────────────────────────
$totalManges = 0; $totalSautes = 0; $totalCraquages = 0; $totalRepas = 0;
foreach ($repasStats as $s) {
    $totalManges += $s['mange'];
    $totalSautes += $s['saute'];
    $totalCraquages += $s['craquage'];
    $totalRepas += $s['total'];
}
$tauxRespect = $totalRepas > 0 ? round($totalManges / $totalRepas * 100) : 0;
$nbJoursSuivis = count($historique);

// Préparer les données JSON pour les graphiques
$chartDates = [];
$chartPoids = [];
$chartHumeur = [];
$chartEnergie = [];
$chartRespect = [];

foreach ($historique as $jour) {
    $d = date('d/m', strtotime($jour['date']));
    $chartDates[] = $d;
    $chartPoids[] = $jour['poids'] ? (float) $jour['poids'] : null;
    $chartHumeur[] = $jour['humeur'] ? (int) $jour['humeur'] : null;
    $chartEnergie[] = $jour['energie'] ? (int) $jour['energie'] : null;

    $rs = $repasStats[$jour['date']] ?? ['mange' => 0, 'total' => 0];
    $chartRespect[] = $rs['total'] > 0 ? round($rs['mange'] / $rs['total'] * 100) : null;
}

$repasConfig = [
    'petit_dej' => ['emoji' => '🌅', 'label' => 'Petit dejeuner'],
    'dejeuner'  => ['emoji' => '☀️', 'label' => 'Dejeuner'],
    'encas'     => ['emoji' => '🍎', 'label' => 'En-cas 16h'],
    'diner'     => ['emoji' => '🌙', 'label' => 'Diner'],
    'dessert'   => ['emoji' => '🍵', 'label' => 'Soiree'],
];

// ── Rendu ────────────────────────────────────────────────────────────────────
$pageTitle = 'Suivi';
$activeNav = 'suivi';
ob_start();
?>

<div class="page-header">
    <h1>Ma progression</h1>
    <div class="page-header-meta">
        <span class="page-header-badge"><?= $nbJoursSuivis ?> jour<?= $nbJoursSuivis > 1 ? 's' : '' ?> suivi<?= $nbJoursSuivis > 1 ? 's' : '' ?></span>
        <span class="page-header-badge"><?= $tauxRespect ?>% de respect</span>
    </div>
</div>

<!-- ── Stats résumé ──────────────────────────────────────────────── -->
<div class="page-inner">
    <div class="suivi-stats">
        <div class="stat-block stat-block--peach">
            <div class="stat-value"><?= $tauxRespect ?><span class="stat-unit">%</span></div>
            <div class="stat-label">Respect menu</div>
        </div>
        <div class="stat-block stat-block--pink">
            <div class="stat-value"><?= $totalManges ?><span class="stat-unit">/<?= $totalRepas ?></span></div>
            <div class="stat-label">Repas suivis</div>
        </div>
        <div class="stat-block stat-block--sky">
            <div class="stat-value"><?= $totalCraquages ?></div>
            <div class="stat-label">Craquages</div>
        </div>
    </div>
</div>

<!-- ── Graphique Poids ───────────────────────────────────────────── -->
<?php if (count(array_filter($chartPoids)) > 0): ?>
<div class="section-title">Poids</div>
<div class="page-inner">
    <div class="card">
        <div class="chart-container" id="chartPoids"></div>
    </div>
</div>
<?php endif; ?>

<!-- ── Graphique Humeur / Énergie ────────────────────────────────── -->
<?php if (count(array_filter($chartHumeur)) > 0): ?>
<div class="section-title">Humeur & Energie</div>
<div class="page-inner">
    <div class="card">
        <div class="chart-container" id="chartBienEtre"></div>
    </div>
</div>
<?php endif; ?>

<!-- ── Graphique Respect menu ────────────────────────────────────── -->
<?php if (count(array_filter($chartRespect)) > 0): ?>
<div class="section-title">Respect du menu</div>
<div class="page-inner">
    <div class="card">
        <div class="chart-bars" id="chartRespect"></div>
    </div>
</div>
<?php endif; ?>

<!-- ── Historique jour par jour ───────────────────────────────────── -->
<div class="section-title">Historique</div>
<div class="page-inner">
<?php if (empty($historique)): ?>
    <div class="card" style="text-align:center;color:var(--text-muted);padding:24px;">
        Aucune donnee enregistree.<br>Commence par remplir ton suivi du jour sur l'accueil.
    </div>
<?php else: ?>
    <?php foreach (array_reverse($historique) as $jour):
        $d = date('d/m', strtotime($jour['date']));
        $jourNom = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'][(int)date('w', strtotime($jour['date']))];
        $rs = $repasStats[$jour['date']] ?? ['mange' => 0, 'saute' => 0, 'craquage' => 0, 'total' => 0];
        $pct = $rs['total'] > 0 ? round($rs['mange'] / $rs['total'] * 100) : 0;
        $isToday = ($jour['date'] === $today);
    ?>
    <div class="histo-row<?= $isToday ? ' histo-row--today' : '' ?>">
        <div class="histo-date">
            <div class="histo-day"><?= $jourNom ?></div>
            <div class="histo-num"><?= $d ?></div>
        </div>
        <div class="histo-body">
            <div class="histo-bar-wrap">
                <div class="histo-bar" style="width:<?= $pct ?>%;background:<?= $pct >= 80 ? 'var(--success)' : ($pct >= 50 ? 'var(--warning)' : 'var(--danger)') ?>"></div>
            </div>
            <div class="histo-detail">
                <span class="histo-pct"><?= $pct ?>%</span>
                <?php if ($rs['mange']): ?><span class="histo-tag histo-tag--ok"><?= $rs['mange'] ?> OK</span><?php endif; ?>
                <?php if ($rs['saute']): ?><span class="histo-tag histo-tag--skip"><?= $rs['saute'] ?> sauté</span><?php endif; ?>
                <?php if ($rs['craquage']): ?><span class="histo-tag histo-tag--crack"><?= $rs['craquage'] ?> craq.</span><?php endif; ?>
                <?php if ($jour['poids']): ?><span class="histo-tag"><?= number_format((float)$jour['poids'], 1) ?>kg</span><?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<div class="spacer"></div>

<!-- ── Formulaire du jour ────────────────────────────────────────── -->
<div class="section-title">Suivi du jour</div>
<div class="page-inner">
    <div class="card">
        <div class="slider-group">
            <label for="sl-poids"><span>⚖️ Poids (kg)</span><span id="sl-poids-val"><?= number_format($poids, 1) ?></span></label>
            <input type="range" id="sl-poids" min="50" max="120" step="0.1" value="<?= htmlspecialchars((string) $poids) ?>">
        </div>
        <div class="slider-group">
            <label for="sl-humeur"><span>😊 Humeur</span><span id="sl-humeur-val"><?= $humeur ?></span></label>
            <input type="range" id="sl-humeur" min="1" max="5" step="1" value="<?= $humeur ?>">
        </div>
        <div class="slider-group">
            <label for="sl-energie"><span>⚡ Energie</span><span id="sl-energie-val"><?= $energie ?></span></label>
            <input type="range" id="sl-energie" min="1" max="5" step="1" value="<?= $energie ?>">
        </div>
        <button type="button" class="btn btn-accent btn-full" id="btn-save-suivi">Enregistrer</button>
        <p id="suivi-feedback" class="text-sm text-muted mt-8" style="display:none;"></p>
    </div>
</div>

<style>
/* Charts */
.chart-container {
    height: 160px;
    position: relative;
    display: flex;
    align-items: flex-end;
    gap: 2px;
    padding: 8px 0;
    overflow-x: auto;
}
.chart-point {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    min-width: 36px;
    flex: 1;
}
.chart-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--primary);
    position: relative;
}
.chart-dot--secondary { background: var(--accent); }
.chart-val {
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--text-muted);
}
.chart-label {
    font-size: 0.6rem;
    color: var(--text-muted);
}
.chart-bar-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    min-width: 36px;
    flex: 1;
}
.chart-bar-fill {
    width: 20px;
    border-radius: 6px 6px 0 0;
    background: var(--primary);
    transition: height 0.3s;
}

/* Bars for respect */
.chart-bars {
    display: flex;
    align-items: flex-end;
    gap: 4px;
    height: 120px;
    padding: 8px 0;
    overflow-x: auto;
}

/* Historique */
.histo-row {
    display: flex;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--border, #eee);
}
.histo-row:last-child { border-bottom: none; }
.histo-row--today { background: color-mix(in srgb, var(--primary) 5%, transparent); border-radius: 8px; padding: 10px 8px; }
.histo-date {
    min-width: 55px;
    text-align: center;
}
.histo-day { font-size: 0.7rem; color: var(--text-muted); font-weight: 600; }
.histo-num { font-size: 0.82rem; font-weight: 700; }
.histo-body { flex: 1; }
.histo-bar-wrap {
    height: 6px;
    background: var(--border, #eee);
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 6px;
}
.histo-bar {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s;
}
.histo-detail {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    align-items: center;
}
.histo-pct {
    font-weight: 800;
    font-size: 0.82rem;
}
.histo-tag {
    font-size: 0.65rem;
    padding: 2px 6px;
    border-radius: 6px;
    background: var(--bg, #f5f5f5);
    font-weight: 600;
}
.histo-tag--ok { color: var(--success); background: color-mix(in srgb, var(--success) 10%, transparent); }
.histo-tag--skip { color: var(--text-muted); }
.histo-tag--crack { color: var(--danger); background: color-mix(in srgb, var(--danger) 10%, transparent); }
</style>

<script>
(function() {
    // ── Sliders live ─────────────────────────────────
    ['poids','humeur','energie'].forEach(function(id) {
        var sl = document.getElementById('sl-' + id);
        var val = document.getElementById('sl-' + id + '-val');
        if (sl && val) sl.addEventListener('input', function() {
            val.textContent = id === 'poids' ? parseFloat(this.value).toFixed(1) : this.value;
        });
    });

    // ── Save suivi ───────────────────────────────────
    document.getElementById('btn-save-suivi').addEventListener('click', async function() {
        var fb = document.getElementById('suivi-feedback');
        try {
            var res = await api('suivi', 'POST', {
                action: 'maj_jour',
                date: '<?= $today ?>',
                poids: parseFloat(document.getElementById('sl-poids').value),
                humeur: parseInt(document.getElementById('sl-humeur').value),
                energie: parseInt(document.getElementById('sl-energie').value)
            });
            fb.style.display = 'block';
            fb.textContent = res.ok ? 'Sauvegarde ✓' : (res.error || 'Erreur');
            fb.style.color = res.ok ? 'var(--success)' : 'var(--danger)';
        } catch(e) {
            fb.style.display = 'block';
            fb.textContent = 'Erreur reseau';
            fb.style.color = 'var(--danger)';
        }
    });

    // ── Charts (pur JS/CSS) ──────────────────────────
    var dates = <?= json_encode($chartDates) ?>;
    var poids = <?= json_encode($chartPoids) ?>;
    var humeur = <?= json_encode($chartHumeur) ?>;
    var energie = <?= json_encode($chartEnergie) ?>;
    var respect = <?= json_encode($chartRespect) ?>;

    // Poids chart (dots positionnés verticalement)
    var poidsEl = document.getElementById('chartPoids');
    if (poidsEl && poids.some(function(v) { return v !== null; })) {
        var validPoids = poids.filter(function(v) { return v !== null; });
        var minP = Math.min.apply(null, validPoids) - 1;
        var maxP = Math.max.apply(null, validPoids) + 1;
        var range = maxP - minP || 1;

        poids.forEach(function(val, i) {
            var col = document.createElement('div');
            col.className = 'chart-point';
            if (val !== null) {
                var pct = ((val - minP) / range) * 100;
                var spacer = document.createElement('div');
                spacer.style.flex = '1';
                spacer.style.minHeight = (100 - pct) + '%';
                col.appendChild(spacer);

                var dot = document.createElement('div');
                dot.className = 'chart-dot';
                col.appendChild(dot);

                var valEl = document.createElement('div');
                valEl.className = 'chart-val';
                valEl.textContent = val.toFixed(1);
                col.appendChild(valEl);
            } else {
                col.style.flex = '1';
            }
            var label = document.createElement('div');
            label.className = 'chart-label';
            label.textContent = dates[i];
            col.appendChild(label);
            poidsEl.appendChild(col);
        });
    }

    // Bien-être chart (humeur + énergie)
    var beEl = document.getElementById('chartBienEtre');
    if (beEl) {
        humeur.forEach(function(h, i) {
            var col = document.createElement('div');
            col.className = 'chart-point';
            col.style.height = '100%';
            col.style.justifyContent = 'flex-end';

            if (h !== null) {
                var barH = document.createElement('div');
                barH.style.width = '8px';
                barH.style.height = (h / 5 * 80) + '%';
                barH.style.background = 'var(--primary)';
                barH.style.borderRadius = '4px';
                barH.title = 'Humeur: ' + h;
                col.appendChild(barH);
            }
            if (energie[i] !== null) {
                var barE = document.createElement('div');
                barE.style.width = '8px';
                barE.style.height = (energie[i] / 5 * 80) + '%';
                barE.style.background = 'var(--accent)';
                barE.style.borderRadius = '4px';
                barE.title = 'Énergie: ' + energie[i];
                barE.style.marginLeft = '2px';
                col.appendChild(barE);
            }

            var label = document.createElement('div');
            label.className = 'chart-label';
            label.textContent = dates[i];
            col.appendChild(label);
            beEl.appendChild(col);
        });
    }

    // Respect chart (barres)
    var respEl = document.getElementById('chartRespect');
    if (respEl) {
        respect.forEach(function(val, i) {
            var col = document.createElement('div');
            col.className = 'chart-bar-col';

            if (val !== null) {
                var bar = document.createElement('div');
                bar.className = 'chart-bar-fill';
                bar.style.height = val + '%';
                bar.style.background = val >= 80 ? 'var(--success)' : (val >= 50 ? 'var(--warning)' : 'var(--danger)');
                col.appendChild(bar);

                var valEl = document.createElement('div');
                valEl.className = 'chart-val';
                valEl.textContent = val + '%';
                col.appendChild(valEl);
            }

            var label = document.createElement('div');
            label.className = 'chart-label';
            label.textContent = dates[i];
            col.appendChild(label);
            respEl.appendChild(col);
        });
    }
}());
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
