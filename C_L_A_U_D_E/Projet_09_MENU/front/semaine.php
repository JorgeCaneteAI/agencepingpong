<?php
/**
 * MealCoach V2 — Semaine (planning read-only)
 * Vue semaine complete avec swipe horizontal par jour
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers/meteo.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

// ── Date & jour ─────────────────────────────────────────────────
$jourSemaine = (int) date('N') - 1; // 0=lundi … 6=dimanche
$nomsJoursCourts = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

// ── Semaine (par ID ou active) ──────────────────────────────────
$reqSid = isset($_GET['sid']) ? (int)$_GET['sid'] : 0;
if ($reqSid > 0) {
    $semaine = fetchOne('SELECT * FROM semaines WHERE id = :id', [':id' => $reqSid]);
} else {
    $semaine = fetchOne("SELECT * FROM semaines WHERE statut = 'active' ORDER BY date_debut DESC LIMIT 1");
}

// ── Navigation prev/next ────────────────────────────────────────
$prevSemaine = null;
$nextSemaine = null;
if ($semaine) {
    $prevSemaine = fetchOne('SELECT id, numero, date_debut, date_fin FROM semaines WHERE date_debut < :d ORDER BY date_debut DESC LIMIT 1', [':d' => $semaine['date_debut']]);
    $nextSemaine = fetchOne('SELECT id, numero, date_debut, date_fin FROM semaines WHERE date_debut > :d ORDER BY date_debut ASC LIMIT 1', [':d' => $semaine['date_debut']]);
}

// ── Dates des jours ─────────────────────────────────────────────
$datesTabs = [];
if ($semaine && !empty($semaine['date_debut'])) {
    $debut = new DateTime($semaine['date_debut']);
    for ($j = 0; $j <= 6; $j++) {
        $d = clone $debut;
        $d->modify('+' . $j . ' days');
        $datesTabs[$j] = $d->format('d');
    }
}

// ── Tous les repas de la semaine (pour le swiper) ───────────────
$repasParJour = [];
if ($semaine) {
    $menuJours = fetchAll(
        'SELECT * FROM menu_jours WHERE semaine_id = :sid ORDER BY jour',
        [':sid' => $semaine['id']]
    );
    foreach ($menuJours as $mj) {
        $repas = fetchAll(
            'SELECT mr.*, r.instructions, r.duree_minutes
             FROM menu_repas mr
             LEFT JOIN recettes r ON r.id = mr.recette_id
             WHERE mr.menu_jour_id = :mjid
             ORDER BY CASE mr.type_repas
                WHEN \'petit_dejeuner\' THEN 1
                WHEN \'petit_dej\' THEN 1
                WHEN \'dejeuner\'  THEN 2
                WHEN \'gouter\'    THEN 3
                WHEN \'encas\'     THEN 3
                WHEN \'diner\'     THEN 4
                WHEN \'dessert\'   THEN 5
                ELSE 6 END',
            [':mjid' => $mj['id']]
        );
        $repasParJour[(int) $mj['jour']] = $repas;
    }
}

// ── Météo semaine ───────────────────────────────────────────────
$meteoSemaine = getMeteoSemaine();

// ── Équivalences pour le swap inline ────────────────────────────
$swapData = [];
$catMap = [
    'légumes' => 'legumes', 'legumes' => 'legumes',
    'protéine' => 'proteine_repas', 'proteine' => 'proteine_repas',
    'sucre lent' => 'sucre_lent',
    'céréale' => 'sucre_lent', 'cereale' => 'sucre_lent',
    'féculent' => 'sucre_lent', 'feculent' => 'sucre_lent',
    'laitage' => 'laitage_pdj',
    'mg' => 'matiere_grasse', 'matiere grasse' => 'matiere_grasse',
    'fromage' => 'fromage_repas',
    'fruit' => 'fruit',
];
$equivRows = fetchAll('SELECT e.id, e.categorie, e.description FROM equivalences e ORDER BY e.categorie, e.description');
foreach ($equivRows as $r) {
    $swapData[$r['categorie']][] = $r['description'];
}
$legRows = fetchAll('SELECT nom FROM produits WHERE categorie = :c AND exclu = 0 ORDER BY nom', [':c' => 'legumes']);
$swapData['legumes'] = array_map(fn($r) => $r['nom'], $legRows);

// ── Parser nom_plat (même logique que dashboard) ────────────────
function parseMealName(string $nomPlat): array {
    $titre = $nomPlat;
    $composants = [];
    $reste = '';

    $pos1 = mb_strpos($nomPlat, '**');
    if ($pos1 !== false) {
        $pos2 = mb_strpos($nomPlat, '**', $pos1 + 2);
        if ($pos2 !== false) {
            $titre = trim(mb_substr($nomPlat, $pos1 + 2, $pos2 - $pos1 - 2));
            $reste = ltrim(trim(mb_substr($nomPlat, $pos2 + 2)), " \t\n\r\0\x0B-–—");
        } else {
            $titre = str_replace('**', '', $nomPlat);
            $parts = explode(' - ', $titre, 2);
            $titre = trim($parts[0]);
            $reste = $parts[1] ?? '';
        }
    } else {
        $parts = explode(' - ', $nomPlat, 2);
        $titre = trim($parts[0]);
        $reste = $parts[1] ?? '';
    }

    if (!empty($reste)) {
        $segments = preg_split('/ - /', $reste);
        foreach ($segments as $seg) {
            $seg = trim($seg);
            if (empty($seg)) continue;
            $colonPos = mb_strpos($seg, ' : ');
            if ($colonPos !== false) {
                $composants[] = ['cat' => trim(mb_substr($seg, 0, $colonPos)), 'val' => trim(mb_substr($seg, $colonPos + 3))];
            } else {
                $composants[] = ['cat' => '', 'val' => $seg];
            }
        }
    }
    return ['titre' => $titre, 'composants' => $composants];
}
function parseContenu(string $contenu): array {
    $composants = [];
    $segments = preg_split('/ - /', $contenu);
    foreach ($segments as $seg) {
        $seg = trim($seg);
        if (empty($seg)) continue;
        $colonPos = mb_strpos($seg, ' : ');
        if ($colonPos !== false) {
            $composants[] = ['cat' => trim(mb_substr($seg, 0, $colonPos)), 'val' => trim(mb_substr($seg, $colonPos + 3))];
        } else {
            $composants[] = ['cat' => '', 'val' => $seg];
        }
    }
    return $composants;
}
function catEmoji(string $cat): string {
    $cat = mb_strtolower(trim($cat));
    $map = ['legumes' => '🥬', 'légumes' => '🥬', 'proteine' => '🍗', 'protéine' => '🍗',
            'sucre lent' => '🍞', 'laitage' => '🥛', 'mg' => '🫒', 'cereale' => '🥣',
            'céréale' => '🥣', 'fruit' => '🍎', 'fromage' => '🧀', 'boisson' => '☕'];
    foreach ($map as $key => $emoji) { if (str_contains($cat, $key)) return $emoji; }
    return '•';
}

// ── Helpers ─────────────────────────────────────────────────────
$repasConfig = [
    'petit_dejeuner' => ['emoji' => '🌅', 'label' => 'Petit-déjeuner', 'class' => 'petitdej'],
    'petit_dej'      => ['emoji' => '🌅', 'label' => 'Petit-déjeuner', 'class' => 'petitdej'],
    'dejeuner'       => ['emoji' => '☀️', 'label' => 'Déjeuner',       'class' => 'dejeuner'],
    'gouter'         => ['emoji' => '🍫', 'label' => 'Goûter',         'class' => 'encas'],
    'encas'          => ['emoji' => '🍎', 'label' => 'En-cas 16h',     'class' => 'encas'],
    'diner'          => ['emoji' => '🌙', 'label' => 'Dîner',          'class' => 'diner'],
    'dessert'        => ['emoji' => '🍵', 'label' => 'Soirée',         'class' => 'soiree'],
];

// ── Rendu ───────────────────────────────────────────────────────
$pageTitle = 'Semaine';
$activeNav = 'semaine';
ob_start();
?>

<?php if (!$semaine): ?>
<!-- No active semaine -->
<div class="page-header">
    <h2>Menu de la semaine</h2>
</div>
<div class="page-inner">
    <div class="alert-card alert-card--warning">
        <span class="alert-icon">⚠️</span>
        <div class="alert-text">
            Aucune semaine active.
            <a href="<?= BASE_URL ?>/admin/import" style="font-weight:700;text-decoration:underline;">Importer un menu</a>
        </div>
    </div>
</div>
<?php else: ?>

<?php
    $moisFr = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
    $debutDt = new DateTime($semaine['date_debut']);
    $finDt   = new DateTime($semaine['date_fin']);
    $moisDebut = $moisFr[(int)$debutDt->format('n') - 1];
    $moisFin   = $moisFr[(int)$finDt->format('n') - 1];
    if ($moisDebut === $moisFin) {
        $headerDate = $debutDt->format('d') . ' – ' . $finDt->format('d') . ' ' . $moisFin;
    } else {
        $headerDate = $debutDt->format('d') . ' ' . $moisDebut . ' – ' . $finDt->format('d') . ' ' . $moisFin;
    }
?>
<!-- Page Header with week navigation -->
<div class="page-header">
    <div class="week-nav">
        <?php if ($prevSemaine): ?>
        <a href="<?= BASE_URL ?>/semaine?sid=<?= $prevSemaine['id'] ?>" class="week-nav-btn">‹</a>
        <?php else: ?>
        <span class="week-nav-btn week-nav-btn--disabled">‹</span>
        <?php endif; ?>

        <div class="week-nav-center">
            <h2><?= $headerDate ?></h2>
            <?php if (!empty($semaine['saison'])): ?>
            <div class="page-header-meta">
                <span class="page-header-badge"><?= htmlspecialchars(ucfirst($semaine['saison'])) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($nextSemaine): ?>
        <a href="<?= BASE_URL ?>/semaine?sid=<?= $nextSemaine['id'] ?>" class="week-nav-btn">›</a>
        <?php else: ?>
        <span class="week-nav-btn week-nav-btn--disabled">›</span>
        <?php endif; ?>
    </div>
</div>

<!-- Day Tabs -->
<div class="day-tabs" id="dayTabs">
    <?php for ($j = 0; $j <= 6; $j++): ?>
    <div class="day-tab<?= $j === $jourSemaine ? ' today' : '' ?>"
         data-day="<?= $j ?>">
        <div class="day-name"><?= $nomsJoursCourts[$j] ?></div>
        <div class="day-num"><?= $datesTabs[$j] ?? ($j + 7) ?></div>
    </div>
    <?php endfor; ?>
</div>

<!-- Day Swiper -->
<div class="day-swiper" id="daySwiper" data-today="<?= $jourSemaine ?>">
    <?php for ($j = 0; $j <= 6; $j++):
        $jourRepas = $repasParJour[$j] ?? [];
    ?>
    <div class="day-panel" data-day="<?= $j ?>">
        <?php
        $dJ = isset($semaine['date_debut']) ? (clone new DateTime($semaine['date_debut']))->modify('+' . $j . ' days')->format('Y-m-d') : '';
        $meteoJ = $meteoSemaine[$dJ] ?? null;
        ?>
        <?php if ($meteoJ): ?>
        <div class="meteo-day-chip">
            <?= $meteoJ['icon'] ?> <?= $meteoJ['temp_min'] ?>° / <?= $meteoJ['temp_max'] ?>°C — <?= htmlspecialchars($meteoJ['label']) ?>
        </div>
        <?php endif; ?>
        <?php if (empty($jourRepas)): ?>
            <div class="card" style="text-align:center;color:var(--text-muted);padding:30px;">
                Aucun repas planifie
            </div>
        <?php else: ?>
            <?php foreach ($jourRepas as $repas):
                $type = $repas['type_repas'];
                $cfg = $repasConfig[$type] ?? ['emoji' => '🍽️', 'label' => ucfirst($type), 'class' => 'dejeuner'];
                $parsed = parseMealName($repas['nom_plat']);
                if (empty($parsed['composants']) && !empty($repas['contenu'])) {
                    $parsed['composants'] = parseContenu($repas['contenu']);
                }
                $hasDetail = !empty($parsed['composants']);
            ?>
            <div class="meal-card meal-card--<?= $cfg['class'] ?><?= $hasDetail ? ' meal-card--expandable' : '' ?>"
                 <?php if ($hasDetail): ?>onclick="toggleMealDetail(this, event)"<?php endif; ?>>
                <div class="meal-card-color"></div>
                <div class="meal-card-header">
                    <div class="meal-card-head">
                        <div class="meal-card-type"><?= htmlspecialchars($cfg['label']) ?></div>
                        <div class="meal-card-title"><?= htmlspecialchars($parsed['titre']) ?></div>
                    </div>
                </div>
                <?php if ($hasDetail): ?>
                <div class="meal-card-toggle">
                    <span class="toggle-text">▾ Voir le detail</span>
                </div>
                <div class="meal-card-detail">
                    <?php if (!empty($parsed['composants'])): ?>
                    <div class="meal-components">
                        <?php foreach ($parsed['composants'] as $ci => $comp):
                            $canSwap = in_array($type, ['dejeuner', 'diner']) && !empty($comp['cat']);
                            $swapCat = $canSwap ? ($catMap[mb_strtolower(trim($comp['cat']))] ?? '') : '';
                        ?>
                        <div class="meal-comp<?= $canSwap && $swapCat ? ' meal-comp--swappable' : '' ?>"
                             <?php if ($canSwap && $swapCat): ?>
                             onclick="event.stopPropagation(); openSwap(this, <?= (int) $repas['id'] ?>, <?= $ci ?>, '<?= htmlspecialchars($swapCat) ?>')"
                             <?php endif; ?>>
                            <div class="comp-body">
                                <?php if (!empty($comp['cat'])): ?>
                                <div class="comp-cat"><?= htmlspecialchars($comp['cat']) ?></div>
                                <?php endif; ?>
                                <div class="comp-val"><?= htmlspecialchars($comp['val']) ?></div>
                            </div>
                            <?php if ($canSwap && $swapCat): ?>
                            <div class="comp-swap-icon">↔</div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($repas['instructions'])): ?>
                    <div class="meal-recipe">
                        <div class="meal-recipe-header" onclick="event.stopPropagation(); this.parentElement.classList.toggle('open')">
                            <span class="meal-recipe-icon">👨‍🍳</span>
                            <span class="meal-recipe-label">Comment préparer</span>
                            <?php if (!empty($repas['duree_minutes'])): ?>
                            <span class="meal-recipe-time"><?= (int)$repas['duree_minutes'] ?> min</span>
                            <?php endif; ?>
                            <span class="meal-recipe-chevron">▾</span>
                        </div>
                        <div class="meal-recipe-body">
                            <?php
                            $steps = preg_split('/\.\s+/', trim($repas['instructions']));
                            $steps = array_filter($steps, fn($s) => !empty(trim($s)));
                            ?>
                            <ol class="meal-recipe-steps">
                                <?php foreach ($steps as $step): ?>
                                <li><?= htmlspecialchars(trim($step)) ?>.</li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endfor; ?>
</div>

<?php endif; ?>

<script>
var swapData = <?= json_encode($swapData, JSON_UNESCAPED_UNICODE) ?>;
var activeSwapPanel = null;

function openSwap(compEl, repasId, compIndex, swapCat) {
    if (activeSwapPanel) {
        activeSwapPanel.remove();
        if (activeSwapPanel._parentComp === compEl) { activeSwapPanel = null; return; }
        activeSwapPanel = null;
    }
    var options = swapData[swapCat] || [];
    if (options.length === 0) return;
    var currentVal = compEl.querySelector('.comp-val').textContent.trim();
    var panel = document.createElement('div');
    panel.className = 'swap-panel';
    panel._parentComp = compEl;
    var title = document.createElement('div');
    title.className = 'swap-panel-title';
    title.textContent = 'Remplacer par :';
    panel.appendChild(title);
    options.forEach(function(opt) {
        var div = document.createElement('div');
        div.className = 'swap-option';
        if (currentVal.toLowerCase().indexOf(opt.toLowerCase()) !== -1 ||
            opt.toLowerCase().indexOf(currentVal.toLowerCase().split('(')[0].trim()) !== -1) {
            div.classList.add('swap-option--current');
        }
        div.textContent = opt;
        div.addEventListener('click', function(e) {
            e.stopPropagation();
            doSwap(repasId, compIndex, opt, compEl, panel);
        });
        panel.appendChild(div);
    });
    compEl.after(panel);
    activeSwapPanel = panel;
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function doSwap(repasId, compIndex, newVal, compEl, panel) {
    try {
        var res = await api('compositeur', 'POST', {
            action: 'swap_composant', repas_id: repasId,
            comp_index: compIndex, new_value: newVal
        });
        if (res.ok) {
            compEl.querySelector('.comp-val').textContent = newVal;
            panel.remove();
            activeSwapPanel = null;
            compEl.style.background = 'color-mix(in srgb, var(--success) 15%, transparent)';
            setTimeout(function() { compEl.style.background = ''; }, 1000);
        } else { alert(res.error || 'Erreur'); }
    } catch(e) { alert('Erreur reseau'); }
}

document.addEventListener('click', function(e) {
    if (activeSwapPanel && !activeSwapPanel.contains(e.target) && !e.target.closest('.meal-comp--swappable')) {
        activeSwapPanel.remove();
        activeSwapPanel = null;
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
