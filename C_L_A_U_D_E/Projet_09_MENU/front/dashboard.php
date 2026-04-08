<?php
/**
 * MealCoach V2 — Dashboard (Aujourd'hui)
 * Swipe par jour + progress ring + suivi rapide + alerte J-2
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/models/Suivi.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

// ── Date & jour ──────────────────────────────────────────────
$today       = date('Y-m-d');
$jourSemaine = (int) date('N') - 1; // 0=lundi … 6=dimanche
$nomsJours   = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
$moisFr = ['janvier','fevrier','mars','avril','mai','juin','juillet','aout','septembre','octobre','novembre','decembre'];
$dateFormatted = $nomsJours[$jourSemaine] . ' ' . (int) date('d') . ' ' . $moisFr[(int) date('m') - 1];

// ── Semaine active ───────────────────────────────────────────
$semaine = fetchOne(
    "SELECT * FROM semaines WHERE statut = 'active' ORDER BY date_debut DESC LIMIT 1"
);

// ── Repas de la semaine ──────────────────────────────────────
$repasParJour = [];
if ($semaine) {
    $menuJours = fetchAll(
        'SELECT * FROM menu_jours WHERE semaine_id = :sid ORDER BY jour',
        [':sid' => $semaine['id']]
    );
    foreach ($menuJours as $mj) {
        $repas = fetchAll(
            'SELECT * FROM menu_repas WHERE menu_jour_id = :mjid
             ORDER BY CASE type_repas
                WHEN \'petit_dej\' THEN 1
                WHEN \'dejeuner\'  THEN 2
                WHEN \'encas\'     THEN 3
                WHEN \'diner\'     THEN 4
                WHEN \'dessert\'   THEN 5
                ELSE 6 END',
            [':mjid' => $mj['id']]
        );
        $repasParJour[(int) $mj['jour']] = $repas;
    }
}

// ── Suivi du jour ────────────────────────────────────────────
$suiviJour  = Suivi::getJour($today);
$suiviRepas = [];
if ($suiviJour) {
    foreach (Suivi::getRepas((int) $suiviJour['id']) as $sr) {
        $suiviRepas[$sr['type_repas']] = $sr;
    }
}
$poids   = $suiviJour ? (float)  ($suiviJour['poids']  ?? 70)  : 70;
$humeur  = $suiviJour ? (int)    ($suiviJour['humeur'] ?? 3)   : 3;
$energie = $suiviJour ? (int)    ($suiviJour['energie'] ?? 3)  : 3;

// ── Alerte J-2 ──────────────────────────────────────────────
$j2Date = date('Y-m-d', strtotime('+2 days'));
$j2Jour = ((int) date('N', strtotime('+2 days'))) - 1;
$j2Repas = [];
$j2NomJour = $nomsJours[$j2Jour] ?? '';
if ($semaine) {
    $j2MenuJour = fetchOne(
        'SELECT * FROM menu_jours WHERE semaine_id = :sid AND jour = :jour',
        [':sid' => $semaine['id'], ':jour' => $j2Jour]
    );
    if ($j2MenuJour) {
        $j2Repas = fetchAll(
            'SELECT nom_plat FROM menu_repas WHERE menu_jour_id = :mjid',
            [':mjid' => $j2MenuJour['id']]
        );
    }
}


// ── Équivalences pour le swap inline ────────────────────────
$swapData = [];
// Map catégorie contenu → catégorie equivalences
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
// Charger équivalences par catégorie
$equivRows = fetchAll('SELECT e.id, e.categorie, e.description FROM equivalences e ORDER BY e.categorie, e.description');
foreach ($equivRows as $r) {
    $swapData[$r['categorie']][] = $r['description'];
}
// Légumes depuis produits
$legRows = fetchAll('SELECT nom FROM produits WHERE categorie = :c AND exclu = 0 ORDER BY nom', [':c' => 'legumes']);
$swapData['legumes'] = array_map(fn($r) => $r['nom'], $legRows);

// ── Parser nom_plat → titre + composants structurés ─────────
function parseMealName(string $nomPlat): array {
    $titre = $nomPlat;
    $composants = [];

    // Approche robuste : chercher ** avec strpos
    $pos1 = mb_strpos($nomPlat, '**');
    if ($pos1 !== false) {
        $pos2 = mb_strpos($nomPlat, '**', $pos1 + 2);
        if ($pos2 !== false) {
            $titre = trim(mb_substr($nomPlat, $pos1 + 2, $pos2 - $pos1 - 2));
            $reste = trim(mb_substr($nomPlat, $pos2 + 2));
            $reste = ltrim($reste, " \t\n\r\0\x0B-–—");
        } else {
            // Un seul ** trouvé, nettoyer
            $titre = str_replace('**', '', $nomPlat);
            $parts = explode(' - ', $titre, 2);
            $titre = trim($parts[0]);
            $reste = $parts[1] ?? '';
        }
    } else {
        // Pas de **, split sur premier " - "
        $parts = explode(' - ', $nomPlat, 2);
        $titre = trim($parts[0]);
        $reste = $parts[1] ?? '';
    }

    // Parser les composants : "Catégorie : valeur - Catégorie : valeur"
    if (!empty($reste)) {
        // Split sur " - " (espace tiret espace)
        $segments = preg_split('/ - /', $reste);
        foreach ($segments as $seg) {
            $seg = trim($seg);
            if (empty($seg)) continue;

            // Format "Catégorie : valeur"
            $colonPos = mb_strpos($seg, ' : ');
            if ($colonPos !== false) {
                $cat = trim(mb_substr($seg, 0, $colonPos));
                $val = trim(mb_substr($seg, $colonPos + 3));
                $composants[] = ['cat' => $cat, 'val' => $val];
            } else {
                // Texte libre (note, astuce...)
                $composants[] = ['cat' => '', 'val' => $seg];
            }
        }
    }

    return ['titre' => $titre, 'composants' => $composants];
}

// ── Parser contenu → composants structurés ──────────────────
function parseContenu(string $contenu): array {
    $composants = [];
    $segments = preg_split('/ - /', $contenu);
    foreach ($segments as $seg) {
        $seg = trim($seg);
        if (empty($seg)) continue;
        $colonPos = mb_strpos($seg, ' : ');
        if ($colonPos !== false) {
            $composants[] = [
                'cat' => trim(mb_substr($seg, 0, $colonPos)),
                'val' => trim(mb_substr($seg, $colonPos + 3)),
            ];
        } else {
            $composants[] = ['cat' => '', 'val' => $seg];
        }
    }
    return $composants;
}

// Emoji par catégorie de composant
function catEmoji(string $cat): string {
    $cat = mb_strtolower(trim($cat));
    $map = [
        'legumes'       => '🥬', 'légumes' => '🥬',
        'legumes accomp' => '🥗', 'légumes accomp.' => '🥗', 'legumes accomp.' => '🥗',
        'proteine'      => '🍗', 'protéine' => '🍗',
        'sucre lent'    => '🍞', 'feculent' => '🍞', 'féculents' => '🍞',
        'laitage'       => '🥛',
        'mg'            => '🫒', 'matiere grasse' => '🫒',
        'cereale'       => '🥣', 'céréale' => '🥣',
        'fruit'         => '🍎',
        'fromage'       => '🧀',
        'boisson'       => '☕',
        'entree legumes' => '🥗', 'entrée légumes' => '🥗',
    ];
    // Chercher une correspondance partielle
    foreach ($map as $key => $emoji) {
        if (str_contains($cat, $key)) return $emoji;
    }
    return '•';
}

// ── Helpers ──────────────────────────────────────────────────
$repasConfig = [
    'petit_dej' => ['emoji' => '🌅', 'label' => 'Petit dejeuner', 'class' => 'petitdej'],
    'dejeuner'  => ['emoji' => '☀️', 'label' => 'Dejeuner',       'class' => 'dejeuner'],
    'encas'     => ['emoji' => '🍎', 'label' => 'En-cas 16h',     'class' => 'encas'],
    'diner'     => ['emoji' => '🌙', 'label' => 'Diner',          'class' => 'diner'],
    'dessert'   => ['emoji' => '🍵', 'label' => 'Soiree',         'class' => 'soiree'],
];

// Count done meals today
$doneCount = 0;
$totalCount = 0;
if (isset($repasParJour[$jourSemaine])) {
    $totalCount = count($repasParJour[$jourSemaine]);
    foreach ($repasParJour[$jourSemaine] as $r) {
        if (isset($suiviRepas[$r['type_repas']]) && $suiviRepas[$r['type_repas']]['statut'] === 'mange') {
            $doneCount++;
        }
    }
}
$circumference = 163;
$progressOffset = $totalCount > 0 ? $circumference * (1 - $doneCount / $totalCount) : $circumference;

// ── Rendu ────────────────────────────────────────────────────
$pageTitle = 'Accueil';
$activeNav = 'dashboard';
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1>Bonjour Jorge 👋</h1>
    <div class="page-header-sub"><?= htmlspecialchars($dateFormatted) ?></div>
    <div class="page-header-meta">
        <?php if ($semaine): ?>
            <span class="page-header-badge">Semaine <?= (int) $semaine['numero'] ?></span>
            <?php if (!empty($semaine['saison'])): ?>
                <span class="page-header-badge">🌸 <?= htmlspecialchars(ucfirst($semaine['saison'])) ?></span>
            <?php endif; ?>
        <?php else: ?>
            <span class="page-header-badge">Aucune semaine active</span>
        <?php endif; ?>
    </div>
</div>

<?php if (!$semaine): ?>
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

<!-- Alerte J-2 -->
<?php if (!empty($j2Repas)): ?>
<div class="alert-card alert-card--warning" id="alertJ2">
    <span class="alert-icon">🛒</span>
    <div class="alert-text">
        <strong><?= htmlspecialchars($j2NomJour) ?></strong> tu manges :
        <?php foreach ($j2Repas as $r): ?>
            <div class="j2-plat"><?= htmlspecialchars($r['nom_plat'] ?? '') ?></div>
        <?php endforeach; ?>
        <div class="j2-question">Tu as tout ce qu'il faut ?</div>
        <div class="j2-actions">
            <button class="btn btn-sm btn-success" onclick="j2Reponse('oui')">Oui, c'est bon !</button>
            <a href="<?= BASE_URL ?>/courses" class="btn btn-sm btn-ghost">Voir ma liste</a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Progress Ring -->
<div class="progress-card">
    <div class="progress-ring-wrap">
        <svg width="64" height="64" viewBox="0 0 64 64">
            <circle class="ring-bg" cx="32" cy="32" r="26"/>
            <circle class="ring-fill" cx="32" cy="32" r="26"
                    stroke-dasharray="<?= $circumference ?>"
                    stroke-dashoffset="<?= $progressOffset ?>"/>
        </svg>
        <div class="progress-center">
            <div class="big"><?= $doneCount ?></div>
            <div class="small">sur <?= $totalCount ?></div>
        </div>
    </div>
    <div class="progress-info">
        <h3>Repas du jour</h3>
        <p><?= $doneCount ?> repas fait<?= $doneCount > 1 ? 's' : '' ?> sur <?= $totalCount ?>.
        <?= $doneCount === $totalCount && $totalCount > 0 ? 'Parfait !' : 'Continue !' ?></p>
    </div>
</div>

<!-- Repas d'aujourd'hui -->
<div class="section-title">Aujourd'hui — <?= $nomsJours[$jourSemaine] ?></div>
<div class="page-inner">
    <?php
        $jourRepas = $repasParJour[$jourSemaine] ?? [];
        $isToday = true;
    ?>
    <?php if (empty($jourRepas)): ?>
        <div class="card" style="text-align:center;color:var(--text-muted);padding:30px;">
            Aucun repas planifie pour aujourd'hui
        </div>
    <?php else: ?>
            <?php foreach ($jourRepas as $repas):
                $type = $repas['type_repas'];
                $cfg = $repasConfig[$type] ?? ['emoji' => '🍽️', 'label' => ucfirst($type), 'class' => 'dejeuner'];
                $suivi = $suiviRepas[$type] ?? null;
                $isDone = $isToday && $suivi && $suivi['statut'] === 'mange';
                $isSaute = $isToday && $suivi && $suivi['statut'] === 'saute';
                $parsed = parseMealName($repas['nom_plat']);
                // Si pas de composants dans nom_plat, parser contenu
                if (empty($parsed['composants']) && !empty($repas['contenu'])) {
                    $parsedContenu = parseContenu($repas['contenu']);
                    $parsed['composants'] = $parsedContenu;
                }
                $hasDetail = !empty($parsed['composants']);
            ?>
            <div class="meal-card meal-card--<?= $cfg['class'] ?><?= $isDone ? ' meal-card--done' : '' ?><?= $hasDetail ? ' meal-card--expandable' : '' ?>"
                 <?php if ($isSaute): ?>style="opacity:0.3;"<?php endif; ?>
                 <?php if ($hasDetail): ?>onclick="toggleMealDetail(this, event)"<?php endif; ?>>
                <div class="meal-card-color"></div>

                <!-- Header compact -->
                <div class="meal-card-header">
                    <div class="meal-card-head">
                        <div class="meal-card-type"><?= htmlspecialchars($cfg['label']) ?></div>
                        <div class="meal-card-title"><?= htmlspecialchars($parsed['titre']) ?></div>
                    </div>
                    <div class="meal-card-right">
                        <?php if ($isDone): ?>
                            <div class="done-check">✓</div>
                        <?php elseif ($isSaute): ?>
                            <span class="badge badge-neutral">Saute</span>
                        <?php elseif ($isToday): ?>
                            <button class="action-btn"
                                    onclick="event.stopPropagation(); marquerRepas(<?= (int) $repas['id'] ?>, '<?= htmlspecialchars($type) ?>', 'mange', this)">
                                C'est fait !
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($hasDetail): ?>
                <!-- Chevron -->
                <div class="meal-card-toggle">
                    <span class="toggle-text">▾ Voir le detail</span>
                </div>

                <!-- Detail structuré -->
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
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
</div>

<div class="spacer"></div>

<!-- Suivi Rapide (only on today view) -->
<div class="section-title">Ton suivi</div>
<div class="page-inner">
    <div class="card">
        <div class="suivi-stats">
            <div class="stat-block stat-block--peach">
                <div class="stat-value"><?= number_format($poids, 1) ?><span class="stat-unit">kg</span></div>
                <div class="stat-label">⚖️ Poids</div>
            </div>
            <div class="stat-block stat-block--pink">
                <div class="stat-value"><?= $humeur ?><span class="stat-unit">/5</span></div>
                <div class="stat-label">😊 Humeur</div>
            </div>
            <div class="stat-block stat-block--sky">
                <div class="stat-value"><?= $energie ?><span class="stat-unit">/5</span></div>
                <div class="stat-label">⚡ Energie</div>
            </div>
        </div>

        <div class="spacer"></div>

        <div class="slider-group">
            <label for="sl-poids">
                <span>⚖️ Poids (kg)</span>
                <span id="sl-poids-val"><?= number_format($poids, 1) ?></span>
            </label>
            <input type="range" id="sl-poids" min="50" max="120" step="0.1"
                   value="<?= htmlspecialchars((string) $poids) ?>">
        </div>
        <div class="slider-group">
            <label for="sl-humeur">
                <span>😊 Humeur</span>
                <span id="sl-humeur-val"><?= $humeur ?></span>
            </label>
            <input type="range" id="sl-humeur" min="1" max="5" step="1" value="<?= $humeur ?>">
        </div>
        <div class="slider-group">
            <label for="sl-energie">
                <span>⚡ Energie</span>
                <span id="sl-energie-val"><?= $energie ?></span>
            </label>
            <input type="range" id="sl-energie" min="1" max="5" step="1" value="<?= $energie ?>">
        </div>

        <button type="button" class="btn btn-accent btn-full" id="btn-save-suivi">
            Enregistrer
        </button>
        <p id="suivi-feedback" class="text-sm text-muted mt-8" style="display:none;"></p>
    </div>
</div>

<?php endif; ?>

<script>
// ── Swap inline ──────────────────────────────────────────
var swapData = <?= json_encode($swapData, JSON_UNESCAPED_UNICODE) ?>;
var activeSwapPanel = null;

function openSwap(compEl, repasId, compIndex, swapCat) {
    // Fermer panel existant
    if (activeSwapPanel) {
        activeSwapPanel.remove();
        // Si on re-clique sur le même, juste fermer
        if (activeSwapPanel._parentComp === compEl) {
            activeSwapPanel = null;
            return;
        }
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
        // Highlight si c'est la valeur actuelle (match partiel)
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
            action: 'swap_composant',
            repas_id: repasId,
            comp_index: compIndex,
            new_value: newVal
        });
        if (res.ok) {
            compEl.querySelector('.comp-val').textContent = newVal;
            panel.remove();
            activeSwapPanel = null;
            // Flash vert
            compEl.style.background = 'color-mix(in srgb, var(--success) 15%, transparent)';
            setTimeout(function() { compEl.style.background = ''; }, 1000);
        } else {
            alert(res.error || 'Erreur');
        }
    } catch(e) {
        alert('Erreur reseau');
    }
}

// Fermer swap panel si on clique ailleurs
document.addEventListener('click', function(e) {
    if (activeSwapPanel && !activeSwapPanel.contains(e.target) && !e.target.closest('.meal-comp--swappable')) {
        activeSwapPanel.remove();
        activeSwapPanel = null;
    }
});

document.getElementById('btn-save-suivi')?.addEventListener('click', async function () {
    var poids   = parseFloat(document.getElementById('sl-poids').value);
    var humeur  = parseInt(document.getElementById('sl-humeur').value, 10);
    var energie = parseInt(document.getElementById('sl-energie').value, 10);
    var fb = document.getElementById('suivi-feedback');
    try {
        var res = await api('suivi', 'POST', {
            action: 'maj_jour',
            date: '<?= $today ?>',
            poids: poids,
            humeur: humeur,
            energie: energie
        });
        if (fb) {
            fb.style.display = 'block';
            fb.textContent = res.ok ? 'Sauvegarde ✓' : (res.error || 'Erreur');
            fb.style.color = res.ok ? 'var(--success)' : 'var(--danger)';
        }
    } catch (err) {
        if (fb) {
            fb.style.display = 'block';
            fb.textContent = 'Erreur reseau';
            fb.style.color = 'var(--danger)';
        }
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
