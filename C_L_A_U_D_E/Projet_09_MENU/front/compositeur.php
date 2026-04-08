<?php
/**
 * MealCoach V2 — Compositeur de repas
 * Flow en 3 etapes : moment > composants > resultat
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers/meteo.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

// ── Donnees pour les selects ─────────────────────────────────────────────────

// Equivalences regroupees par categorie + moment
$equivRows = fetchAll('
    SELECT e.id, e.categorie, e.description, e.quantite, e.unite, e.moment,
           e.est_non_raffine, e.produit_id,
           p.nom AS produit_nom, p.saisons AS produit_saisons
    FROM equivalences e
    LEFT JOIN produits p ON e.produit_id = p.id
    ORDER BY e.categorie, e.description
');

$equivalences = [];
foreach ($equivRows as $row) {
    $cat = $row['categorie'];
    if (!isset($equivalences[$cat])) $equivalences[$cat] = [];
    $equivalences[$cat][] = $row;
}

// Legumes (depuis produits, pas dans equivalences) — pour dejeuner/diner
$legumes = fetchAll('
    SELECT id, nom, saisons
    FROM produits
    WHERE categorie = :cat AND exclu = 0
    ORDER BY nom
', [':cat' => 'legumes']);

// Saison courante pour trier "de saison" en premier
$moisActuel = (int) date('n');
$saisonMap = [
    1 => 'hiver', 2 => 'hiver', 3 => 'printemps',
    4 => 'printemps', 5 => 'printemps', 6 => 'ete',
    7 => 'ete', 8 => 'ete', 9 => 'automne',
    10 => 'automne', 11 => 'automne', 12 => 'hiver',
];
$saisonActuelle = $saisonMap[$moisActuel];

// Semaine active (pour "ajouter au menu")
$semaineActive = fetchOne("SELECT * FROM semaines WHERE statut = 'active' ORDER BY date_debut DESC LIMIT 1");

// Repas existants par jour (pour le remplacement)
$repasExistantsParJour = [];
$nomsJours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
$repasLabels = [
    'petit_dej' => 'Petit-dej',
    'dejeuner'  => 'Dejeuner',
    'encas'     => 'En-cas',
    'diner'     => 'Diner',
    'dessert'   => 'Soiree',
];
if ($semaineActive) {
    $menuJours = fetchAll(
        'SELECT * FROM menu_jours WHERE semaine_id = :sid ORDER BY jour',
        [':sid' => $semaineActive['id']]
    );
    foreach ($menuJours as $mj) {
        $repas = fetchAll(
            'SELECT id, type_repas, nom_plat FROM menu_repas WHERE menu_jour_id = :mjid ORDER BY CASE type_repas
                WHEN \'petit_dej\' THEN 1 WHEN \'dejeuner\' THEN 2 WHEN \'encas\' THEN 3
                WHEN \'diner\' THEN 4 WHEN \'dessert\' THEN 5 ELSE 6 END',
            [':mjid' => $mj['id']]
        );
        $repasExistantsParJour[(int)$mj['jour']] = $repas;
    }
}

// Helper : determine si un produit est de saison
function estDeSaison(?string $saisonsJson, string $saison): bool {
    if (!$saisonsJson) return false;
    $arr = json_decode($saisonsJson, true);
    return is_array($arr) && in_array($saison, $arr);
}

// ── Paramètres URL (pré-remplissage depuis dashboard) ──────────────────────
$presetMoment  = $_GET['moment']  ?? null;
$presetReplace = $_GET['replace'] ?? null;
$presetJour    = $_GET['jour']    ?? null;

// ── Météo pour suggestions contextuelles ────────────────────────────────────
$meteoToday = getMeteoJour(date('Y-m-d'));
$meteoTip = null;
if ($meteoToday) {
    $tempMax = $meteoToday['temp_max'];
    if ($tempMax >= SEUIL_CHAUD) {
        $meteoTip = [
            'icon'  => $meteoToday['icon'],
            'temp'  => $tempMax,
            'type'  => 'chaud',
            'msg'   => "Il va faire {$tempMax}°C — privilegie les plats frais et legers !",
            'suggestions' => SUGGESTIONS_ETE,
        ];
    } elseif ($tempMax <= SEUIL_FROID) {
        $meteoTip = [
            'icon'  => $meteoToday['icon'],
            'temp'  => $tempMax,
            'type'  => 'froid',
            'msg'   => "Il va faire {$tempMax}°C — c'est le moment des plats reconfortants !",
            'suggestions' => SUGGESTIONS_HIVER,
        ];
    }
}

// ── Rendu ─────────────────────────────────────────────────────────────────────
$pageTitle = 'Composer';
$activeNav = 'compositeur';
ob_start();
?>

<div class="page-header">
    <h1>Composer un repas</h1>
    <p class="text-muted text-sm">3 etapes pour un repas equilibre</p>
</div>

<?php if ($meteoTip): ?>
<div class="page-inner" style="margin-bottom:12px;">
    <div class="meteo-composer-tip">
        <div class="meteo-tip-header">
            <span class="meteo-tip-icon"><?= $meteoTip['icon'] ?></span>
            <span class="meteo-tip-temp"><?= $meteoTip['temp'] ?>°C</span>
            <span class="meteo-tip-msg"><?= htmlspecialchars($meteoTip['msg']) ?></span>
        </div>
        <div class="meteo-tip-ideas" id="meteoTipIdeas">
            <!-- Rempli dynamiquement selon le moment choisi -->
        </div>
    </div>
</div>
<?php endif; ?>

<div class="page-inner">

    <!-- ═══════════════════════════════════════════════════════
         STEP 1 : Quel repas ?
         ═══════════════════════════════════════════════════════ -->
    <div id="step1">
        <div class="section-title">1. Quel repas ?</div>
        <div class="step-selector">
            <div class="step-btn" onclick="selectMoment('petit_dej')">
                <span class="step-emoji">🌅</span>
                <span class="step-label">Petit-dej</span>
            </div>
            <div class="step-btn" onclick="selectMoment('dejeuner')">
                <span class="step-emoji">☀️</span>
                <span class="step-label">Dejeuner</span>
            </div>
            <div class="step-btn" onclick="selectMoment('encas')">
                <span class="step-emoji">🍎</span>
                <span class="step-label">En-cas</span>
            </div>
            <div class="step-btn" onclick="selectMoment('diner')">
                <span class="step-emoji">🌙</span>
                <span class="step-label">Diner</span>
            </div>
            <div class="step-btn" onclick="selectMoment('soiree')">
                <span class="step-emoji">🫖</span>
                <span class="step-label">Soiree</span>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         STEP 2 : Composer les composants
         ═══════════════════════════════════════════════════════ -->
    <div id="step2" style="display:none;">
        <div class="section-title">
            2. Compose ton repas
            <button type="button" class="btn-back" onclick="backToStep1()">← Changer</button>
        </div>

        <div id="moment-badge" class="card" style="padding:10px 16px;margin-bottom:12px;">
            <span id="moment-label" style="font-weight:700;"></span>
        </div>

        <!-- Petit-dej selects -->
        <div id="selects-petit_dej" class="composer-selects" style="display:none;">
            <div class="composer-field">
                <label>🥛 Laitage</label>
                <?php echo buildSelect('laitage_pdj', $equivalences['laitage_pdj'] ?? [], $saisonActuelle); ?>
            </div>
            <div class="composer-field">
                <label>🥣 Cereale / Feculent</label>
                <?php echo buildSelect('cereale_pdj', $equivalences['cereale_pdj'] ?? [], $saisonActuelle); ?>
            </div>
            <div class="composer-field">
                <label>🥚 Proteine</label>
                <?php echo buildSelect('proteine_pdj', $equivalences['proteine_pdj'] ?? [], $saisonActuelle); ?>
            </div>
        </div>

        <!-- Dejeuner selects -->
        <div id="selects-dejeuner" class="composer-selects" style="display:none;">
            <div class="composer-field">
                <label>🥬 Legumes</label>
                <?php echo buildLegumesSelect($legumes, $saisonActuelle); ?>
            </div>
            <div class="composer-field">
                <label>🍗 Proteine</label>
                <?php echo buildSelect('proteine_repas', $equivalences['proteine_repas'] ?? [], $saisonActuelle); ?>
            </div>
            <div class="composer-field">
                <label>🧀 Fromage</label>
                <?php echo buildSelect('fromage_repas', $equivalences['fromage_repas'] ?? [], $saisonActuelle); ?>
            </div>
            <div class="composer-field">
                <label>🍎 Fruit</label>
                <?php echo buildSelect('fruit', $equivalences['fruit'] ?? [], $saisonActuelle); ?>
            </div>
            <div class="composer-field">
                <label>🍞 Feculent</label>
                <?php echo buildSelect('sucre_lent', $equivalences['sucre_lent'] ?? [], $saisonActuelle); ?>
            </div>
            <div class="composer-field">
                <label>🧈 Matiere grasse</label>
                <?php echo buildSelect('matiere_grasse', $equivalences['matiere_grasse'] ?? [], $saisonActuelle); ?>
            </div>
        </div>

        <!-- Diner selects (meme structure que dejeuner) -->
        <div id="selects-diner" class="composer-selects" style="display:none;">
            <div class="composer-field">
                <label>🥬 Legumes</label>
                <?php echo buildLegumesSelect($legumes, $saisonActuelle, 'diner'); ?>
            </div>
            <div class="composer-field">
                <label>🍗 Proteine</label>
                <?php echo buildSelect('proteine_repas', $equivalences['proteine_repas'] ?? [], $saisonActuelle, 'diner'); ?>
            </div>
            <div class="composer-field">
                <label>🧀 Fromage</label>
                <?php echo buildSelect('fromage_repas', $equivalences['fromage_repas'] ?? [], $saisonActuelle, 'diner'); ?>
            </div>
            <div class="composer-field">
                <label>🍎 Fruit</label>
                <?php echo buildSelect('fruit', $equivalences['fruit'] ?? [], $saisonActuelle, 'diner'); ?>
            </div>
            <div class="composer-field">
                <label>🍞 Feculent</label>
                <?php echo buildSelect('sucre_lent', $equivalences['sucre_lent'] ?? [], $saisonActuelle, 'diner'); ?>
            </div>
            <div class="composer-field">
                <label>🧈 Matiere grasse</label>
                <?php echo buildSelect('matiere_grasse', $equivalences['matiere_grasse'] ?? [], $saisonActuelle, 'diner'); ?>
            </div>
        </div>

        <!-- En-cas selects -->
        <div id="selects-encas" class="composer-selects" style="display:none;">
            <div class="composer-field">
                <label>🍎 Fruit / Noix</label>
                <?php echo buildSelect('fruit', $equivalences['fruit'] ?? [], $saisonActuelle, 'encas'); ?>
            </div>
            <div class="composer-field">
                <label>🥛 Laitage</label>
                <?php echo buildSelect('laitage_pdj', $equivalences['laitage_pdj'] ?? [], $saisonActuelle, 'encas'); ?>
            </div>
        </div>

        <!-- Soiree selects -->
        <div id="selects-soiree" class="composer-selects" style="display:none;">
            <div class="composer-field">
                <label>🫖 Boisson chaude</label>
                <select class="composer-select" id="sel-boisson-soiree">
                    <option value="">— Choisir —</option>
                    <option value="tisane">Tisane</option>
                    <option value="the_vert">The vert</option>
                    <option value="rooibos">Rooibos</option>
                    <option value="lait_chaud">Lait chaud</option>
                    <option value="chocolat_chaud">Chocolat chaud leger</option>
                </select>
            </div>
            <div class="composer-field">
                <label>🍫 Douceur</label>
                <select class="composer-select" id="sel-douceur-soiree">
                    <option value="">— Choisir —</option>
                    <option value="chocolat_noir">1 carre chocolat noir 85%</option>
                    <option value="compote">1 compote sans sucre ajoute</option>
                    <option value="datte">1 datte</option>
                    <option value="rien">Rien (juste la boisson)</option>
                </select>
            </div>
        </div>

        <button type="button" class="btn-accent btn-full" style="margin-top:16px;" onclick="composerResult()">
            Voir le resultat →
        </button>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         STEP 3 : Resultat + Remplacement
         ═══════════════════════════════════════════════════════ -->
    <div id="step3" style="display:none;">
        <div class="section-title">
            3. Ton repas
            <button type="button" class="btn-back" onclick="backToStep2()">← Modifier</button>
        </div>

        <div class="card" id="result-card">
            <div id="result-moment" style="font-size:0.8rem;color:var(--text-muted);margin-bottom:8px;"></div>
            <div id="result-name" style="font-weight:700;font-size:1.1rem;margin-bottom:12px;"></div>
            <div id="result-items"></div>
        </div>

        <!-- Sélecteur : quel repas remplacer ? -->
        <div class="section-title" style="margin-top:20px;">Remplacer quel repas ?</div>

        <div class="replace-day-selector">
            <?php for ($j = 0; $j < 7; $j++): ?>
            <button type="button" class="replace-day-btn" data-jour="<?= $j ?>" onclick="selectReplaceDay(<?= $j ?>)">
                <?= $nomsJours[$j] ?>
            </button>
            <?php endfor; ?>
        </div>

        <div id="replace-meals" style="display:none;">
            <div class="section-title" style="font-size:0.75rem;">Repas a remplacer :</div>
            <div id="replace-meals-list"></div>
        </div>

        <div id="replace-confirm" style="display:none;margin-top:12px;">
            <button type="button" class="btn-accent btn-full" id="btn-remplacer" onclick="remplacerRepas()">
                Remplacer ce repas
            </button>
        </div>

        <button type="button" class="btn-full" style="margin-top:8px;background:var(--bg-card);border:1px solid var(--border);color:var(--text);border-radius:12px;padding:12px;font-weight:600;cursor:pointer;" onclick="backToStep1()">
            Composer un autre repas
        </button>
    </div>

</div>

<?php
// ── PHP helpers pour generer les selects ─────────────────────────────────────

function buildSelect(string $categorie, array $items, string $saison, string $suffix = ''): string {
    $id = 'sel-' . $categorie . ($suffix ? '-' . $suffix : '');
    $deSaison = [];
    $autres = [];

    foreach ($items as $item) {
        $saisonsRaw = $item['produit_saisons'] ?? '';
        if (estDeSaison($saisonsRaw, $saison)) {
            $deSaison[] = $item;
        } else {
            $autres[] = $item;
        }
    }

    $html = '<select class="composer-select" id="' . htmlspecialchars($id) . '" data-cat="' . htmlspecialchars($categorie) . '">';
    $html .= '<option value="">— Choisir —</option>';

    if (!empty($deSaison)) {
        $html .= '<optgroup label="De saison">';
        foreach ($deSaison as $item) {
            $html .= '<option value="' . (int)$item['id'] . '" data-desc="' . htmlspecialchars($item['description']) . '">';
            $html .= htmlspecialchars($item['description']);
            $html .= '</option>';
        }
        $html .= '</optgroup>';
    }

    $labelAutres = !empty($deSaison) ? 'Autres' : 'Choix';
    $html .= '<optgroup label="' . $labelAutres . '">';
    foreach ($autres as $item) {
        $html .= '<option value="' . (int)$item['id'] . '" data-desc="' . htmlspecialchars($item['description']) . '">';
        $html .= htmlspecialchars($item['description']);
        $html .= '</option>';
    }
    $html .= '</optgroup>';

    $html .= '</select>';
    return $html;
}

function buildLegumesSelect(array $legumes, string $saison, string $suffix = ''): string {
    $id = 'sel-legumes' . ($suffix ? '-' . $suffix : '');
    $deSaison = [];
    $autres = [];

    foreach ($legumes as $leg) {
        if (estDeSaison($leg['saisons'] ?? '', $saison)) {
            $deSaison[] = $leg;
        } else {
            $autres[] = $leg;
        }
    }

    $html = '<select class="composer-select" id="' . htmlspecialchars($id) . '" data-cat="legumes">';
    $html .= '<option value="">— Choisir —</option>';

    if (!empty($deSaison)) {
        $html .= '<optgroup label="De saison">';
        foreach ($deSaison as $leg) {
            $html .= '<option value="leg-' . (int)$leg['id'] . '" data-desc="' . htmlspecialchars($leg['nom']) . '">';
            $html .= htmlspecialchars($leg['nom']);
            $html .= '</option>';
        }
        $html .= '</optgroup>';
    }

    $labelAutres = !empty($deSaison) ? 'Autres' : 'Choix';
    $html .= '<optgroup label="' . $labelAutres . '">';
    foreach ($autres as $leg) {
        $html .= '<option value="leg-' . (int)$leg['id'] . '" data-desc="' . htmlspecialchars($leg['nom']) . '">';
        $html .= htmlspecialchars($leg['nom']);
        $html .= '</option>';
    }
    $html .= '</optgroup>';

    $html .= '</select>';
    return $html;
}
?>

<style>
.step-selector {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-top: 12px;
}
@media (max-width: 380px) {
    .step-selector { grid-template-columns: repeat(2, 1fr); }
}
.step-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 16px 10px;
    border: 2px solid var(--border, #eee);
    border-radius: 14px;
    background: var(--bg-card, #fff);
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s, transform 0.1s;
}
.step-btn:active {
    transform: scale(0.96);
    border-color: var(--primary, #4CAF50);
    background: color-mix(in srgb, var(--primary, #4CAF50) 8%, transparent);
}
.step-emoji { font-size: 1.8rem; }
.step-label { font-weight: 600; font-size: 0.85rem; color: var(--text); text-align: center; }

.btn-back {
    background: none;
    border: none;
    color: var(--primary, #4CAF50);
    font-weight: 600;
    font-size: 0.82rem;
    cursor: pointer;
    padding: 4px 8px;
    float: right;
}

.composer-selects {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.composer-field label {
    display: block;
    font-weight: 600;
    font-size: 0.85rem;
    margin-bottom: 6px;
    color: var(--text);
}
.composer-select {
    width: 100%;
    padding: 12px 14px;
    border: 2px solid var(--border, #eee);
    border-radius: 12px;
    background: var(--bg-card, #fff);
    font-size: 0.9rem;
    color: var(--text, #222);
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23888' d='M2 4l4 4 4-4'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    cursor: pointer;
    transition: border-color 0.15s;
}
.composer-select:focus {
    outline: none;
    border-color: var(--primary, #4CAF50);
}
.composer-select option {
    padding: 8px;
}

/* Météo tip in composer */
.meteo-composer-tip {
    background: linear-gradient(135deg, var(--sky, #C4DAF0) 0%, #E8F0FA 100%);
    border-radius: 14px;
    padding: 14px 16px;
    border: 1px solid rgba(106,159,212,0.2);
}
.meteo-tip-header {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.meteo-tip-icon { font-size: 1.3rem; }
.meteo-tip-temp { font-size: 1.1rem; font-weight: 800; }
.meteo-tip-msg { font-size: 0.8rem; color: var(--text-light, #8A7F7F); font-weight: 500; }
.meteo-tip-ideas {
    margin-top: 10px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.meteo-idea-chip {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 10px;
    font-size: 0.72rem;
    font-weight: 600;
    background: rgba(255,255,255,0.7);
    color: var(--accent, #D4869A);
    border: 1px solid var(--accent, #D4869A);
    cursor: default;
}

/* Replace day selector */
.replace-day-selector {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 8px;
}
.replace-day-btn {
    flex: 1;
    min-width: 44px;
    padding: 10px 4px;
    border: 2px solid var(--border, #eee);
    border-radius: 10px;
    background: var(--bg-card, #fff);
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--text);
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s;
    text-align: center;
}
.replace-day-btn:active,
.replace-day-btn--active {
    border-color: var(--primary, #4CAF50);
    background: color-mix(in srgb, var(--primary, #4CAF50) 10%, transparent);
    color: var(--primary, #4CAF50);
}

.replace-meal-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    margin-bottom: 6px;
    border: 2px solid var(--border, #eee);
    border-radius: 12px;
    background: var(--bg-card, #fff);
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s;
}
.replace-meal-option:active,
.replace-meal-option--active {
    border-color: var(--accent, #D4869A);
    background: color-mix(in srgb, var(--accent, #D4869A) 8%, transparent);
}
.replace-meal-type {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--text-muted);
    letter-spacing: 0.5px;
}
.replace-meal-name {
    font-weight: 600;
    font-size: 0.88rem;
    margin-top: 2px;
}
.replace-meal-arrow {
    font-size: 1rem;
    color: var(--text-muted);
}

/* Result card items */
.result-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid var(--border, #eee);
    font-size: 0.9rem;
}
.result-item:last-child { border-bottom: none; }
.result-item-cat {
    font-size: 0.75rem;
    color: var(--text-muted);
    min-width: 70px;
}
.result-item-val { font-weight: 600; flex: 1; }
.result-empty {
    color: var(--text-muted);
    font-size: 0.85rem;
    font-style: italic;
    padding: 12px 0;
}
</style>

<script>
(function() {
    var currentMoment = null;
    var momentLabels = {
        petit_dej: 'Petit-dej',
        dejeuner:  'Dejeuner',
        encas:     'En-cas',
        diner:     'Diner',
        soiree:    'Soiree'
    };
    var momentEmojis = {
        petit_dej: '🌅',
        dejeuner:  '☀️',
        encas:     '🍎',
        diner:     '🌙',
        soiree:    '🫖'
    };

    // ── Step navigation ───────────────────────────────────

    // Suggestions météo par moment (injectées depuis PHP)
    var meteoSuggestions = <?= $meteoTip ? json_encode($meteoTip['suggestions']) : '{}' ?>;

    window.selectMoment = function(type) {
        currentMoment = type;
        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = 'block';
        document.getElementById('step3').style.display = 'none';

        document.getElementById('moment-label').textContent =
            momentEmojis[type] + ' ' + momentLabels[type];

        // Hide all select groups, show the right one
        var groups = document.querySelectorAll('.composer-selects');
        for (var i = 0; i < groups.length; i++) {
            groups[i].style.display = 'none';
        }
        var target = document.getElementById('selects-' + type);
        if (target) target.style.display = 'flex';

        // Update météo suggestions for this moment
        updateMeteoIdeas(type);
    };

    function updateMeteoIdeas(moment) {
        var container = document.getElementById('meteoTipIdeas');
        if (!container) return;
        container.textContent = '';
        var ideas = meteoSuggestions[moment] || meteoSuggestions['dejeuner'] || [];
        for (var i = 0; i < ideas.length; i++) {
            var chip = document.createElement('span');
            chip.className = 'meteo-idea-chip';
            chip.textContent = ideas[i];
            container.appendChild(chip);
        }
    }

    window.backToStep1 = function() {
        currentMoment = null;
        document.getElementById('step1').style.display = 'block';
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step3').style.display = 'none';
        // Reset all selects
        var selects = document.querySelectorAll('.composer-select');
        for (var i = 0; i < selects.length; i++) {
            selects[i].selectedIndex = 0;
        }
    };

    window.backToStep2 = function() {
        document.getElementById('step2').style.display = 'block';
        document.getElementById('step3').style.display = 'none';
    };

    // ── Build result ──────────────────────────────────────

    window.composerResult = function() {
        if (!currentMoment) return;

        var container = document.getElementById('selects-' + currentMoment);
        if (!container) return;

        var selects = container.querySelectorAll('.composer-select');
        var items = [];
        var nameParts = [];

        for (var i = 0; i < selects.length; i++) {
            var sel = selects[i];
            if (sel.value) {
                var opt = sel.options[sel.selectedIndex];
                var desc = opt.getAttribute('data-desc') || opt.textContent;
                var label = sel.closest('.composer-field').querySelector('label').textContent;
                items.push({ cat: label, value: desc, selectId: sel.id, optValue: sel.value });
                nameParts.push(desc);
            }
        }

        if (items.length === 0) {
            alert('Choisis au moins un element pour composer ton repas.');
            return;
        }

        // Show step 3
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step3').style.display = 'block';

        document.getElementById('result-moment').textContent =
            momentEmojis[currentMoment] + ' ' + momentLabels[currentMoment];

        // Auto-generated name
        var autoName = nameParts.slice(0, 3).join(' + ');
        if (nameParts.length > 3) autoName += '...';
        document.getElementById('result-name').textContent = autoName;

        // Items list
        var itemsDiv = document.getElementById('result-items');
        itemsDiv.innerHTML = '';

        for (var j = 0; j < items.length; j++) {
            var row = document.createElement('div');
            row.className = 'result-item';
            row.innerHTML =
                '<span class="result-item-cat">' + escHtml(items[j].cat) + '</span>' +
                '<span class="result-item-val">' + escHtml(items[j].value) + '</span>';
            itemsDiv.appendChild(row);
        }

        // Store for save
        window._composerResult = {
            moment: currentMoment,
            name: autoName,
            items: items
        };
    };

    // ── Replacement flow ─────────────────────────────────

    // Données des repas existants injectées depuis PHP
    var repasParJour = <?= json_encode($repasExistantsParJour, JSON_UNESCAPED_UNICODE) ?>;
    var repasLabels = <?= json_encode($repasLabels, JSON_UNESCAPED_UNICODE) ?>;
    var selectedDay = null;
    var selectedRepasId = null;

    window.selectReplaceDay = function(jour) {
        selectedDay = jour;
        selectedRepasId = null;

        // Highlight selected day
        var btns = document.querySelectorAll('.replace-day-btn');
        for (var i = 0; i < btns.length; i++) {
            btns[i].classList.toggle('replace-day-btn--active', parseInt(btns[i].dataset.jour) === jour);
        }

        // Show meals for this day
        var mealsDiv = document.getElementById('replace-meals');
        var listDiv = document.getElementById('replace-meals-list');
        var confirmDiv = document.getElementById('replace-confirm');
        mealsDiv.style.display = 'block';
        confirmDiv.style.display = 'none';
        listDiv.innerHTML = '';

        var repas = repasParJour[jour] || repasParJour[String(jour)] || [];
        if (repas.length === 0) {
            listDiv.innerHTML = '<div style="color:var(--text-muted);font-size:0.85rem;padding:12px 0;">Aucun repas ce jour-la.</div>';
            return;
        }

        for (var j = 0; j < repas.length; j++) {
            var r = repas[j];
            var label = repasLabels[r.type_repas] || r.type_repas;
            // Extraire le titre (enlever le format **titre**)
            var titre = r.nom_plat || '';
            var m = titre.match(/\*\*(.+?)\*\*/);
            if (m) titre = m[1];
            var parts = titre.split(' - ');
            titre = parts[0];

            var opt = document.createElement('div');
            opt.className = 'replace-meal-option';
            opt.dataset.repasId = r.id;
            opt.innerHTML =
                '<div>' +
                    '<div class="replace-meal-type">' + escHtml(label) + '</div>' +
                    '<div class="replace-meal-name">' + escHtml(titre) + '</div>' +
                '</div>' +
                '<div class="replace-meal-arrow">→</div>';
            opt.addEventListener('click', (function(id) {
                return function() { selectRepasToReplace(id); };
            })(r.id));
            listDiv.appendChild(opt);
        }
    };

    function selectRepasToReplace(repasId) {
        selectedRepasId = repasId;
        // Highlight
        var opts = document.querySelectorAll('.replace-meal-option');
        for (var i = 0; i < opts.length; i++) {
            opts[i].classList.toggle('replace-meal-option--active', parseInt(opts[i].dataset.repasId) === repasId);
        }
        document.getElementById('replace-confirm').style.display = 'block';
    }

    window.remplacerRepas = async function() {
        if (!selectedRepasId || !window._composerResult) return;

        var btn = document.getElementById('btn-remplacer');
        btn.disabled = true;
        btn.textContent = 'Remplacement...';

        var result = window._composerResult;

        // Build contenu string: "Catégorie : valeur - Catégorie : valeur"
        var contenuParts = [];
        for (var i = 0; i < result.items.length; i++) {
            var item = result.items[i];
            // Extraire le label de catégorie (enlever l'emoji du début)
            var catLabel = item.cat.replace(/^[^\w\s]+\s*/, '').trim();
            contenuParts.push(catLabel + ' : ' + item.value);
        }

        try {
            var res = await api('compositeur', 'POST', {
                action:     'remplacer',
                repas_id:   selectedRepasId,
                nom_plat:   result.name,
                contenu:    contenuParts.join(' - ')
            });

            if (res.ok) {
                btn.textContent = 'Remplace !';
                btn.style.background = 'var(--success)';
                btn.style.color = '#fff';

                // Update local data so next replacement reflects the change
                for (var jour in repasParJour) {
                    var arr = repasParJour[jour];
                    for (var k = 0; k < arr.length; k++) {
                        if (arr[k].id === selectedRepasId) {
                            arr[k].nom_plat = result.name;
                        }
                    }
                }

                setTimeout(function() {
                    btn.disabled = false;
                    btn.textContent = 'Remplacer ce repas';
                    btn.style.background = '';
                    btn.style.color = '';
                    // Reset selection
                    selectedRepasId = null;
                    document.getElementById('replace-confirm').style.display = 'none';
                    var opts = document.querySelectorAll('.replace-meal-option');
                    for (var o = 0; o < opts.length; o++) opts[o].classList.remove('replace-meal-option--active');
                    // Refresh meal list for the day
                    if (selectedDay !== null) selectReplaceDay(selectedDay);
                }, 2000);
            } else {
                alert('Erreur : ' + (res.error || 'Impossible de remplacer.'));
                btn.disabled = false;
                btn.textContent = 'Remplacer ce repas';
            }
        } catch(e) {
            alert('Erreur reseau.');
            btn.disabled = false;
            btn.textContent = 'Remplacer ce repas';
        }
    };

    // ── Helpers ───────────────────────────────────────────

    function escHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // ── Auto-init depuis paramètres URL (venant du dashboard) ──
    var presetMoment  = <?= json_encode($presetMoment) ?>;
    var presetReplace = <?= json_encode($presetReplace) ?>;
    var presetJour    = <?= json_encode($presetJour) ?>;

    if (presetMoment) {
        // Stocker l'ID du repas à remplacer pour skip le sélecteur jour/repas
        if (presetReplace) {
            window._presetReplaceId = parseInt(presetReplace);
            window._presetJour = presetJour !== null ? parseInt(presetJour) : null;
        }
        selectMoment(presetMoment);
    }

    // Override composerResult pour auto-sélectionner le repas à remplacer
    var _origComposerResult = window.composerResult;
    window.composerResult = function() {
        _origComposerResult();
        // Si preset, auto-sélectionner jour + repas
        if (window._presetReplaceId && window._presetJour !== null && window._presetJour !== undefined) {
            selectReplaceDay(window._presetJour);
            setTimeout(function() {
                selectRepasToReplace(window._presetReplaceId);
                // Scroll vers le bouton remplacer
                var btn = document.getElementById('btn-remplacer');
                if (btn) btn.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
            // Reset pour ne pas re-déclencher
            window._presetReplaceId = null;
        }
    };
}());
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
