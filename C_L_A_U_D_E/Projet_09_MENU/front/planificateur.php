<?php
/**
 * MealCoach V2 — Planificateur de semaine
 * Choisir ses recettes et les placer dans la semaine
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

// ── Recettes disponibles ────────────────────────────────────────────────────
$recettes = fetchAll('SELECT * FROM recettes WHERE actif = 1 ORDER BY nom');

// Grouper par protéine pour les filtres
$proteines = [];
foreach ($recettes as $r) {
    $p = $r['proteine'] ?: 'autre';
    if (!in_array($p, $proteines)) $proteines[] = $p;
}
sort($proteines);

// Labels protéines
$proteineLabels = [
    'volaille' => '🍗 Volaille',
    'oeufs' => '🥚 Oeufs',
    'boeuf' => '🥩 Boeuf',
    'veau' => '🥩 Veau',
    'porc' => '🥓 Porc',
    'jambon' => '🥓 Jambon',
    'poisson' => '🐟 Poisson',
    'thon' => '🐟 Thon',
    'sardines' => '🐟 Sardines',
    'crevettes' => '🦐 Crevettes',
    'legumineuses' => '🫘 Végétarien',
];

// Tags uniques
$allTags = [];
foreach ($recettes as $r) {
    $tags = array_map('trim', explode(',', $r['tags'] ?? ''));
    foreach ($tags as $t) {
        if ($t && !in_array($t, $allTags)) $allTags[] = $t;
    }
}
sort($allTags);

$nomsJours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
$joursAbbr = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

// 2 semaines glissantes : semaine en cours + semaine suivante
$today = new DateTime();
$dow = (int)$today->format('N'); // 1=lun ... 7=dim
$mondayS1 = (clone $today)->modify('-' . ($dow - 1) . ' days');
$sundayS1 = (clone $mondayS1)->modify('+6 days');
$mondayS2 = (clone $mondayS1)->modify('+7 days');
$sundayS2 = (clone $mondayS2)->modify('+6 days');

$semaines = [
    ['label' => 'Cette semaine',    'monday' => $mondayS1, 'sunday' => $sundayS1],
    ['label' => 'Semaine prochaine','monday' => $mondayS2, 'sunday' => $sundayS2],
];

// ── Repas déjà planifiés sur ces 2 semaines ─────────────────────────────────
$dateDebut = $mondayS1->format('Y-m-d');
$dateFin   = $sundayS2->format('Y-m-d');

$repasExistants = fetchAll(
    'SELECT mj.date, mr.type_repas, mr.nom_plat, mr.contenu, mr.recette_id
     FROM menu_repas mr
     JOIN menu_jours mj ON mj.id = mr.menu_jour_id
     WHERE mj.date >= :d1 AND mj.date <= :d2
     ORDER BY mj.date, mr.type_repas',
    [':d1' => $dateDebut, ':d2' => $dateFin]
);

// Indexer par "sX-jour-type"
$repasMap = [];
foreach ($repasExistants as $r) {
    $rDate = new DateTime($r['date']);
    // Déterminer dans quelle semaine (0 ou 1)
    $diffDays = (int)$mondayS1->diff($rDate)->days;
    $si = ($diffDays >= 7) ? 1 : 0;
    $jour = (int)$rDate->format('N') - 1; // 0=lun
    $key = 's' . $si . '-' . $jour . '-' . $r['type_repas'];
    $repasMap[$key] = [
        'nom'        => $r['nom_plat'],
        'contenu'    => $r['contenu'],
        'recette_id' => (int)($r['recette_id'] ?? 0),
    ];
}

// ── Rendu ─────────────────────────────────────────────────────────────────────
$pageTitle = 'Planifier ma semaine';
$activeNav = 'compositeur';
ob_start();
?>

<div class="page-header">
    <h2>Planifier mes repas</h2>
    <div class="page-header-meta">
        <span class="page-header-badge"><?= count($recettes) ?> recettes</span>
    </div>
</div>

<?php foreach ($semaines as $si => $sem):
    $mon = $sem['monday'];
    $sun = $sem['sunday'];
    $totalSlots = 14;
?>
<div class="plan-week" data-semaine="<?= $si ?>">
    <div class="plan-week-header">
        <span class="plan-week-title"><?= $sem['label'] ?></span>
        <span class="plan-week-dates"><?= $mon->format('d/m') ?> → <?= $sun->format('d/m') ?></span>
    </div>
    <div class="plan-grid">
        <?php for ($j = 0; $j < 7; $j++):
            $jourDate = (clone $mon)->modify('+' . $j . ' days');
            $isToday = $jourDate->format('Y-m-d') === date('Y-m-d');
            $slotPrefix = 's' . $si . '-' . $j;
        ?>
        <div class="plan-day<?= $isToday ? ' plan-day--today' : '' ?>">
            <div class="plan-day-header">
                <span class="plan-day-name"><?= $joursAbbr[$j] ?></span>
                <span class="plan-day-date"><?= $jourDate->format('d/m') ?></span>
            </div>
            <?php foreach (['dejeuner' => 'Déj.', 'diner' => 'Dîner'] as $typeKey => $typeLabel):
                $slotKey = $slotPrefix . '-' . $typeKey;
                $existing = $repasMap[$slotKey] ?? null;
            ?>
            <div class="plan-slot" data-semaine="<?= $si ?>" data-jour="<?= $j ?>" data-type="<?= $typeKey ?>" data-date="<?= $jourDate->format('Y-m-d') ?>" onclick="openCatalogue(this)">
                <div class="plan-slot-label"><?= $typeLabel ?></div>
                <div class="plan-slot-recipe" id="slot-<?= $slotKey ?>">
                    <?php if ($existing): ?>
                    <div class="plan-slot-name"><?= htmlspecialchars($existing['nom']) ?></div>
                    <button class="plan-slot-remove" onclick="event.stopPropagation(); removeSlot('<?= $slotKey ?>', this)">✕</button>
                    <?php else: ?>
                    <span class="plan-slot-empty">+</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endfor; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Compteur -->
<div class="plan-status" id="planStatus">0 / 28 repas planifiés</div>

<!-- Bouton valider -->
<button class="plan-validate-btn" id="validateBtn" onclick="validerSemaine()" disabled>
    Valider ma planification
</button>

<!-- ═══ MODAL CATALOGUE ═══ -->
<div class="catalogue-overlay" id="catalogueOverlay" onclick="closeCatalogue()"></div>
<div class="catalogue-panel" id="cataloguePanel">
    <div class="catalogue-header">
        <h3 id="catalogueTitle">Choisir une recette</h3>
        <button class="catalogue-close" onclick="closeCatalogue()">✕</button>
    </div>

    <!-- Filtres -->
    <div class="catalogue-filters">
        <div class="filter-label">Type de plat</div>
        <div class="filter-pills" id="filterTypePills">
            <button class="filter-pill active" data-group="type" data-value="all" onclick="pillFilter(this)">Tous</button>
            <button class="filter-pill" data-group="type" data-value="bowl" onclick="pillFilter(this)"><span class="pill-icon">🥣</span> Bowls</button>
            <button class="filter-pill" data-group="type" data-value="salade" onclick="pillFilter(this)"><span class="pill-icon">🥗</span> Salades</button>
            <button class="filter-pill" data-group="type" data-value="rapide" onclick="pillFilter(this)"><span class="pill-icon">⚡</span> Rapide</button>
            <button class="filter-pill" data-group="type" data-value="classique" onclick="pillFilter(this)"><span class="pill-icon">🍳</span> Traditionnel</button>
            <button class="filter-pill" data-group="type" data-value="asiatique" onclick="pillFilter(this)"><span class="pill-icon">🥢</span> Asiatique</button>
        </div>
        <div class="filter-label">Protéine</div>
        <div class="filter-pills" id="filterIngrPills">
            <button class="filter-pill active" data-group="ingr" data-value="all" onclick="pillFilter(this)">Tous</button>
            <button class="filter-pill" data-group="ingr" data-value="viandes" onclick="pillFilter(this)"><span class="pill-icon">🥩</span> Viandes</button>
            <button class="filter-pill" data-group="ingr" data-value="poisson" onclick="pillFilter(this)"><span class="pill-icon">🐟</span> Poisson</button>
            <button class="filter-pill" data-group="ingr" data-value="charcuterie" onclick="pillFilter(this)"><span class="pill-icon">🥓</span> Charcuterie</button>
            <button class="filter-pill" data-group="ingr" data-value="oeufs" onclick="pillFilter(this)"><span class="pill-icon">🥚</span> Oeufs</button>
            <button class="filter-pill" data-group="ingr" data-value="vegetal" onclick="pillFilter(this)"><span class="pill-icon">🫘</span> Végétal</button>
        </div>
    </div>

    <!-- Recherche -->
    <div class="catalogue-search">
        <input type="search" id="catalogueSearch" placeholder="Rechercher une recette..." oninput="searchRecettes(this.value)">
    </div>

    <!-- Liste recettes -->
    <div class="catalogue-list" id="catalogueList">
        <?php foreach ($recettes as $r):
            $tags = $r['tags'] ?? '';
            $duree = (int)($r['duree_minutes'] ?? 20);
        ?>
        <div class="catalogue-item"
             data-id="<?= $r['id'] ?>"
             data-proteine="<?= htmlspecialchars($r['proteine'] ?? '') ?>"
             data-tags="<?= htmlspecialchars($tags) ?>"
             data-nom="<?= htmlspecialchars(mb_strtolower($r['nom'])) ?>"
             data-saison="<?= htmlspecialchars($r['saison'] ?? 'toutes') ?>"
             onclick="selectRecette(<?= $r['id'] ?>, this)">
            <div class="catalogue-item-main">
                <div class="catalogue-item-nom"><?= htmlspecialchars($r['nom']) ?></div>
                <div class="catalogue-item-meta">
                    <span class="catalogue-item-duree"><?= $duree ?> min</span>
                    <?php
                    $tagArr = array_filter(array_map('trim', explode(',', $tags)));
                    $displayTags = array_slice($tagArr, 0, 3);
                    foreach ($displayTags as $t):
                    ?>
                    <span class="catalogue-item-tag"><?= htmlspecialchars($t) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="catalogue-item-arrow">→</div>
        </div>
        <?php endforeach; ?>
        <div class="catalogue-empty" id="catalogueEmpty" style="display:none;">Aucune recette trouvée</div>
    </div>
</div>

<!-- Données JSON pour JS -->
<script>
var RECETTES = <?= json_encode(array_map(function($r) {
    return [
        'id' => (int)$r['id'],
        'nom' => $r['nom'],
        'proteine' => $r['proteine'],
        'feculent' => $r['feculent'],
        'legume' => $r['legume'],
        'contenu' => $r['contenu'],
        'instructions' => $r['instructions'],
        'tags' => $r['tags'],
        'duree_minutes' => (int)($r['duree_minutes'] ?? 20),
        'saison' => $r['saison'] ?? 'toutes',
    ];
}, $recettes), JSON_UNESCAPED_UNICODE) ?>;

var JOURS = <?= json_encode($nomsJours) ?>;
var SEMAINES = <?= json_encode(array_map(function($s) {
    return ['monday' => $s['monday']->format('Y-m-d'), 'sunday' => $s['sunday']->format('Y-m-d'), 'label' => $s['label']];
}, $semaines), JSON_UNESCAPED_UNICODE) ?>;
var planning = <?php
$planInit = [];
foreach ($repasMap as $key => $r) {
    if (empty($r['nom'])) continue;
    // key = "s0-3-dejeuner" → semaine=0, jour=3
    preg_match('/^s(\d+)-(\d+)-/', $key, $m);
    $si = (int)($m[1] ?? 0);
    $ji = (int)($m[2] ?? 0);
    $jourDate = (clone $semaines[$si]['monday'])->modify('+' . $ji . ' days');
    $planInit[$key] = [
        'id' => $r['recette_id'],
        'nom' => $r['nom'],
        'contenu' => $r['contenu'],
        'semaine' => $si,
        'jour' => $ji,
        'date' => $jourDate->format('Y-m-d'),
    ];
}
echo json_encode($planInit ?: new stdClass(), JSON_UNESCAPED_UNICODE);
?>;
var currentSlot = null;
var activeType = 'all';
var activeIngr = 'all';

// Mapping ingrédient → protéines en BDD
var INGR_MAP = {
    'viandes': ['volaille', 'boeuf', 'veau', 'porc'],
    'poisson': ['poisson', 'thon', 'sardines', 'crevettes'],
    'charcuterie': ['jambon'],
    'oeufs': ['oeufs'],
    'vegetal': ['legumineuses']
};

function openCatalogue(slotEl) {
    currentSlot = slotEl;
    var jour = parseInt(slotEl.dataset.jour);
    var type = slotEl.dataset.type;
    var dateStr = slotEl.dataset.date;
    var d = new Date(dateStr + 'T00:00:00');
    var dateLabel = d.toLocaleDateString('fr-FR', {day: 'numeric', month: 'short'});
    var title = JOURS[jour] + ' ' + dateLabel + ' — ' + (type === 'dejeuner' ? 'Déjeuner' : 'Dîner');
    document.getElementById('catalogueTitle').textContent = title;
    document.getElementById('catalogueOverlay').classList.add('open');
    document.getElementById('cataloguePanel').classList.add('open');
    document.getElementById('catalogueSearch').value = '';
    // Reset filtres
    activeType = 'all';
    activeIngr = 'all';
    resetPills('filterTypePills');
    resetPills('filterIngrPills');
    applyFilters();
}

function closeCatalogue() {
    document.getElementById('catalogueOverlay').classList.remove('open');
    document.getElementById('cataloguePanel').classList.remove('open');
    currentSlot = null;
}

function selectRecette(id, el) {
    if (!currentSlot) return;
    var r = RECETTES.find(function(x) { return x.id === id; });
    if (!r) return;

    var sem = currentSlot.dataset.semaine;
    var jour = currentSlot.dataset.jour;
    var type = currentSlot.dataset.type;
    var date = currentSlot.dataset.date;
    var key = 's' + sem + '-' + jour + '-' + type;

    planning[key] = { id: r.id, nom: r.nom, contenu: r.contenu, date: date, semaine: parseInt(sem), jour: parseInt(jour) };

    var slotRecipe = document.getElementById('slot-s' + sem + '-' + jour + '-' + type);
    slotRecipe.textContent = '';
    var nameEl = document.createElement('div');
    nameEl.className = 'plan-slot-name';
    nameEl.textContent = r.nom;
    slotRecipe.appendChild(nameEl);

    var removeBtn = document.createElement('button');
    removeBtn.className = 'plan-slot-remove';
    removeBtn.textContent = '✕';
    removeBtn.onclick = function(e) {
        e.stopPropagation();
        delete planning[key];
        slotRecipe.textContent = '';
        var empty = document.createElement('span');
        empty.className = 'plan-slot-empty';
        empty.textContent = '+';
        slotRecipe.appendChild(empty);
        updateStatus();
    };
    slotRecipe.appendChild(removeBtn);

    closeCatalogue();
    updateStatus();
}

function removeSlot(key, btn) {
    delete planning[key];
    var container = document.getElementById('slot-' + key);
    container.textContent = '';
    var empty = document.createElement('span');
    empty.className = 'plan-slot-empty';
    empty.textContent = '+';
    container.appendChild(empty);
    updateStatus();
}

function updateStatus() {
    var count = Object.keys(planning).length;
    document.getElementById('planStatus').textContent = count + ' / 28 repas planifiés';
    document.getElementById('validateBtn').disabled = count === 0;
}

// Init status au chargement
updateStatus();

// ── Filtres ──────────────────────────────────
function pillFilter(btn) {
    var group = btn.dataset.group;
    var value = btn.dataset.value;
    // Désactiver les frères
    btn.parentElement.querySelectorAll('.filter-pill').forEach(function(p) { p.classList.remove('active'); });
    btn.classList.add('active');
    if (group === 'type') activeType = value;
    if (group === 'ingr') activeIngr = value;
    applyFilters();
}

function resetPills(containerId) {
    var c = document.getElementById(containerId);
    c.querySelectorAll('.filter-pill').forEach(function(p) { p.classList.remove('active'); });
    c.querySelector('[data-value="all"]').classList.add('active');
}

function setFilter(group, value) {
    if (group === 'type') activeType = value;
    if (group === 'ingr') activeIngr = value;
    applyFilters();
}

function searchRecettes(q) {
    applyFilters(q.trim().toLowerCase());
}

function applyFilters(searchQ) {
    if (searchQ === undefined) {
        searchQ = (document.getElementById('catalogueSearch').value || '').trim().toLowerCase();
    }
    var items = document.querySelectorAll('.catalogue-item');
    var visible = 0;

    items.forEach(function(item) {
        var show = true;
        var itemTags = item.dataset.tags || '';
        var itemProt = item.dataset.proteine || '';

        // Filtre type
        if (activeType !== 'all') {
            if (itemTags.indexOf(activeType) < 0) show = false;
        }

        // Filtre ingrédient
        if (activeIngr !== 'all') {
            var allowed = INGR_MAP[activeIngr] || [];
            if (allowed.indexOf(itemProt) < 0) show = false;
        }

        // Recherche texte
        if (searchQ && item.dataset.nom.indexOf(searchQ) === -1) show = false;

        item.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('catalogueEmpty').style.display = visible === 0 ? 'block' : 'none';
}

// ── Validation ──────────────────────────────────
async function validerSemaine() {
    var btn = document.getElementById('validateBtn');
    btn.disabled = true;
    btn.textContent = 'Création en cours...';

    try {
        // Regrouper le planning par semaine
        for (var si = 0; si < SEMAINES.length; si++) {
            var weekPlanning = {};
            var hasEntries = false;
            Object.keys(planning).forEach(function(key) {
                var p = planning[key];
                if (p.semaine === si) {
                    // Clé pour l'API : "jour-type"
                    var apiKey = p.jour + '-' + key.split('-').pop();
                    weekPlanning[apiKey] = { id: p.id };
                    hasEntries = true;
                }
            });
            if (!hasEntries) continue;

            var res = await api('compositeur', 'POST', {
                action: 'planifier_semaine',
                planning: weekPlanning,
                date_debut: SEMAINES[si].monday,
                date_fin: SEMAINES[si].sunday
            });
            if (!res.ok) {
                btn.textContent = 'Erreur : ' + (res.error || 'inconnue');
                btn.disabled = false;
                return;
            }
        }
        btn.textContent = '✓ Planification enregistrée !';
        btn.style.background = 'var(--success)';
        setTimeout(function() {
            window.location.href = '<?= BASE_URL ?>/semaine';
        }, 1000);
    } catch(e) {
        btn.textContent = 'Erreur réseau';
        btn.disabled = false;
    }
}
</script>

<style>
/* ═══ Semaines ═══ */
.plan-week { margin-bottom: 20px; }
.plan-week-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 12px; margin-bottom: 4px;
}
.plan-week-title {
    font-weight: 800; font-size: 0.95rem; color: var(--text);
}
.plan-week-dates {
    font-size: 0.78rem; font-weight: 600; color: var(--text-light);
    background: var(--card); padding: 4px 10px; border-radius: 20px;
}

/* ═══ Grille planification ═══ */
.plan-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
    padding: 0 4px;
    overflow-x: auto;
}
@media (max-width: 700px) {
    .plan-grid {
        grid-template-columns: repeat(7, minmax(90px, 1fr));
    }
}
.plan-day-header {
    text-align: center; padding: 6px 0 4px;
    display: flex; flex-direction: column; align-items: center; gap: 1px;
}
.plan-day-name {
    font-weight: 700; font-size: 0.7rem;
    color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;
}
.plan-day-date {
    font-size: 0.65rem; font-weight: 600; color: var(--text-light);
}
.plan-day--today .plan-day-name {
    color: var(--accent);
}
.plan-day--today .plan-day-date {
    background: var(--accent); color: white;
    padding: 1px 6px; border-radius: 10px; font-size: 0.6rem;
}
.plan-slot {
    background: var(--card); border: 2px dashed rgba(80,50,50,0.12);
    border-radius: 10px; padding: 6px; min-height: 70px;
    cursor: pointer; transition: border-color 0.2s, background 0.2s;
    margin-bottom: 4px; position: relative;
}
.plan-slot:hover { border-color: var(--accent); }
.plan-slot:has(.plan-slot-name) {
    border-style: solid; border-color: var(--accent);
    background: color-mix(in srgb, var(--accent) 6%, white);
}
.plan-slot-label {
    font-size: 0.6rem; font-weight: 700; text-transform: uppercase;
    color: var(--text-muted); letter-spacing: 0.5px; margin-bottom: 4px;
}
.plan-slot-empty {
    font-size: 0.75rem; color: var(--text-muted); opacity: 0.6;
    display: block; text-align: center; padding: 8px 0;
}
.plan-slot-name {
    font-size: 0.7rem; font-weight: 600; line-height: 1.3;
    color: var(--text); padding-right: 16px;
}
.plan-slot-remove {
    position: absolute; top: 4px; right: 4px;
    width: 18px; height: 18px; border-radius: 50%;
    background: var(--danger, #e74c3c); color: white;
    border: none; font-size: 0.6rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
}

.plan-status {
    text-align: center; font-size: 0.85rem; font-weight: 600;
    color: var(--text-muted); padding: 12px 0;
}
.plan-validate-btn {
    display: block; width: calc(100% - 32px); margin: 0 16px 16px;
    padding: 14px; border: none; border-radius: var(--radius);
    background: var(--accent); color: white; font-size: 1rem;
    font-weight: 700; cursor: pointer; transition: opacity 0.2s;
}
.plan-validate-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* ═══ Catalogue (bottom sheet) ═══ */
.catalogue-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.4);
    z-index: 200; opacity: 0; pointer-events: none; transition: opacity 0.2s;
}
.catalogue-overlay.open { opacity: 1; pointer-events: auto; }

.catalogue-panel {
    position: fixed; bottom: 0; left: 0; right: 0;
    max-height: 85vh; background: var(--bg);
    border-radius: 20px 20px 0 0; z-index: 201;
    transform: translateY(100%); transition: transform 0.3s ease;
    display: flex; flex-direction: column;
    box-shadow: 0 -4px 30px rgba(0,0,0,0.15);
}
.catalogue-panel.open { transform: translateY(0); }

.catalogue-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px 8px; flex-shrink: 0;
}
.catalogue-header h3 { font-size: 1rem; font-weight: 700; margin: 0; }
.catalogue-close {
    width: 32px; height: 32px; border-radius: 50%;
    border: none; background: var(--card); font-size: 1rem;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
}

.catalogue-filters { padding: 4px 16px 0; flex-shrink: 0; }
.filter-label {
    font-size: 0.65rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.8px; color: var(--text-muted);
    margin: 8px 0 6px 4px;
}
.filter-pills {
    display: flex; gap: 6px; overflow-x: auto;
    padding-bottom: 4px; -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}
.filter-pills::-webkit-scrollbar { display: none; }
.filter-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 8px 14px; border-radius: 50px;
    border: 2px solid rgba(80,50,50,0.1);
    background: var(--card); color: var(--text-muted);
    font-size: 0.78rem; font-weight: 600;
    white-space: nowrap; cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
}
.filter-pill:active { transform: scale(0.95); }
.filter-pill .pill-icon { font-size: 0.85rem; }
.filter-pill.active {
    background: var(--accent);
    color: white; border-color: var(--accent);
    box-shadow: 0 2px 8px color-mix(in srgb, var(--accent) 40%, transparent);
}
.filter-pill:not(.active):hover {
    border-color: color-mix(in srgb, var(--accent) 50%, transparent);
    background: color-mix(in srgb, var(--accent) 8%, white);
    color: var(--text);
}

.catalogue-search { padding: 8px 20px; flex-shrink: 0; }
.catalogue-search input {
    width: 100%; padding: 10px 14px; border: 1px solid rgba(80,50,50,0.1);
    border-radius: var(--radius); font-size: 0.9rem;
    background: var(--card); box-sizing: border-box;
}

.catalogue-list {
    flex: 1; overflow-y: auto; padding: 0 12px 80px;
    -webkit-overflow-scrolling: touch;
}
.catalogue-item {
    display: flex; align-items: center; gap: 10px;
    padding: 12px; margin-bottom: 4px;
    background: var(--card); border-radius: var(--radius);
    border: 1.5px solid transparent; cursor: pointer;
    transition: border-color 0.15s;
}
.catalogue-item:active { border-color: var(--accent); }
.catalogue-item-main { flex: 1; min-width: 0; }
.catalogue-item-nom {
    font-size: 0.88rem; font-weight: 600; line-height: 1.3;
}
.catalogue-item-meta {
    display: flex; gap: 6px; flex-wrap: wrap; margin-top: 4px;
}
.catalogue-item-duree {
    font-size: 0.7rem; color: var(--text-muted); font-weight: 600;
}
.catalogue-item-tag {
    font-size: 0.65rem; padding: 1px 6px; border-radius: 6px;
    background: color-mix(in srgb, var(--accent) 10%, transparent);
    color: var(--accent); font-weight: 600;
}
.catalogue-item-arrow {
    color: var(--text-muted); font-size: 1.1rem; flex-shrink: 0;
}
.catalogue-empty {
    text-align: center; padding: 24px; color: var(--text-muted);
    font-size: 0.85rem;
}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
