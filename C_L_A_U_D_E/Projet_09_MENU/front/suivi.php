<?php
/**
 * MealCoach — Suivi du jour
 * Suivi complet : statut des repas + bien-être (poids, humeur, énergie, sommeil)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/models/Suivi.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

// ── Données du jour ───────────────────────────────────────────────────────────
$today     = date('Y-m-d');
$suiviJour = Suivi::getJour($today);

$suiviRepas = [];
if ($suiviJour) {
    foreach (Suivi::getRepas((int) $suiviJour['id']) as $sr) {
        $suiviRepas[$sr['type_repas']] = $sr;
    }
}

// Valeurs bien-être existantes
$poids   = $suiviJour ? (float) ($suiviJour['poids']   ?? 70)  : 70;
$humeur  = $suiviJour ? (int)   ($suiviJour['humeur']  ?? 5)   : 5;
$energie = $suiviJour ? (int)   ($suiviJour['energie'] ?? 5)   : 5;
$sommeil = $suiviJour ? (int)   ($suiviJour['sommeil'] ?? 7)   : 7;
$note    = $suiviJour ? ($suiviJour['note'] ?? '') : '';

// Configuration repas
$repasConfig = [
    'petit_dej' => ['emoji' => '🌅', 'label' => 'Petit déjeuner'],
    'dejeuner'  => ['emoji' => '☀️',  'label' => 'Déjeuner'],
    'encas'     => ['emoji' => '🍎',  'label' => 'En-cas 16h'],
    'diner'     => ['emoji' => '🌙',  'label' => 'Dîner'],
    'dessert'   => ['emoji' => '🍮',  'label' => 'Dessert'],
];

// ── Rendu ─────────────────────────────────────────────────────────────────────
$pageTitle = 'Suivi du jour';
$activeNav = 'plus';
ob_start();
?>

<div style="display:flex; align-items:baseline; gap:8px; margin-bottom:4px;">
    <h1>Suivi du jour</h1>
    <span class="text-muted"><?= date('d/m', strtotime($today)) ?></span>
</div>
<p class="text-muted text-sm mb-16"><?= date('l d F Y', strtotime($today)) ?></p>

<!-- ── Section Repas ───────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-title">Mes repas</div>

    <?php foreach ($repasConfig as $type => $cfg):
        $statutActuel = isset($suiviRepas[$type]) ? $suiviRepas[$type]['statut'] : null;
    ?>
    <div class="suivi-repas-row" data-type="<?= htmlspecialchars($type) ?>">
        <div class="suivi-repas-header">
            <span class="meal-emoji"><?= $cfg['emoji'] ?></span>
            <span class="meal-name"><?= htmlspecialchars($cfg['label']) ?></span>
            <?php if ($statutActuel): ?>
                <span class="badge <?= match($statutActuel) {
                    'mange'    => 'badge-success',
                    'saute'    => 'badge-neutral',
                    'craquage' => 'badge-danger',
                    default    => 'badge-warning',
                } ?> suivi-badge" id="badge-<?= htmlspecialchars($type) ?>">
                    <?= match($statutActuel) {
                        'mange'    => 'Mangé',
                        'saute'    => 'Sauté',
                        'craquage' => 'Craquage',
                        default    => ucfirst($statutActuel),
                    } ?>
                </span>
            <?php else: ?>
                <span class="badge badge-warning suivi-badge" id="badge-<?= htmlspecialchars($type) ?>" style="display:none;"></span>
            <?php endif; ?>
        </div>
        <div class="suivi-repas-btns">
            <button type="button"
                    class="btn btn-sm btn-success-outline suivi-btn <?= $statutActuel === 'mange' ? 'active' : '' ?>"
                    data-type="<?= htmlspecialchars($type) ?>"
                    data-statut="mange"
                    onclick="majRepas('<?= htmlspecialchars($type) ?>', 'mange', this)">
                Mangé
            </button>
            <button type="button"
                    class="btn btn-sm btn-outline suivi-btn <?= $statutActuel === 'saute' ? 'active' : '' ?>"
                    data-type="<?= htmlspecialchars($type) ?>"
                    data-statut="saute"
                    onclick="majRepas('<?= htmlspecialchars($type) ?>', 'saute', this)">
                Sauté
            </button>
            <button type="button"
                    class="btn btn-sm btn-danger-outline suivi-btn <?= $statutActuel === 'craquage' ? 'active' : '' ?>"
                    data-type="<?= htmlspecialchars($type) ?>"
                    data-statut="craquage"
                    onclick="majRepas('<?= htmlspecialchars($type) ?>', 'craquage', this)">
                Craquage
            </button>
        </div>
    </div>
    <?php endforeach; ?>

    <div id="repas-feedback" class="text-sm" style="display:none; margin-top:8px;"></div>
</div>

<!-- ── Section Bien-être ───────────────────────────────────────────────── -->
<div class="card">
    <div class="card-title">Bien-être</div>

    <!-- Poids -->
    <div class="slider-group">
        <label for="sl-poids">
            Poids (kg)
            <span id="sl-poids-val"><?= number_format($poids, 1) ?></span>
        </label>
        <input type="range"
               id="sl-poids"
               min="40" max="150" step="0.1"
               value="<?= htmlspecialchars(number_format($poids, 1, '.', '')) ?>">
    </div>

    <!-- Humeur -->
    <div class="slider-group">
        <label for="sl-humeur">
            Humeur
            <span id="sl-humeur-val"><?= $humeur ?></span><span class="text-muted">/10</span>
        </label>
        <input type="range"
               id="sl-humeur"
               min="1" max="10" step="1"
               value="<?= $humeur ?>">
        <div class="slider-labels">
            <span>Morose</span><span>Excellent</span>
        </div>
    </div>

    <!-- Énergie -->
    <div class="slider-group">
        <label for="sl-energie">
            Énergie
            <span id="sl-energie-val"><?= $energie ?></span><span class="text-muted">/10</span>
        </label>
        <input type="range"
               id="sl-energie"
               min="1" max="10" step="1"
               value="<?= $energie ?>">
        <div class="slider-labels">
            <span>Épuisé</span><span>Au top</span>
        </div>
    </div>

    <!-- Sommeil -->
    <div class="slider-group">
        <label for="sl-sommeil">
            Sommeil (h)
            <span id="sl-sommeil-val"><?= $sommeil ?></span><span class="text-muted">h</span>
        </label>
        <input type="range"
               id="sl-sommeil"
               min="1" max="10" step="1"
               value="<?= $sommeil ?>">
        <div class="slider-labels">
            <span>1h</span><span>10h+</span>
        </div>
    </div>

    <!-- Note libre -->
    <div style="margin-top:12px;">
        <label for="note-jour" style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; color:var(--text-muted,#888);">
            Note du jour
        </label>
        <textarea
            id="note-jour"
            rows="3"
            placeholder="Comment tu te sens aujourd'hui ?"
            style="
                width:100%;
                padding:10px 12px;
                border:1px solid var(--border,#ddd);
                border-radius:10px;
                font-size:0.9rem;
                resize:vertical;
                background:var(--bg,#f5f5f5);
                color:var(--text,#222);
                box-sizing:border-box;
            "
        ><?= htmlspecialchars($note) ?></textarea>
    </div>

    <button type="button" class="btn btn-primary btn-full mt-12" id="btn-save-bienetre">
        Enregistrer le bien-être
    </button>
    <p id="bienetre-feedback" class="text-sm text-muted mt-8" style="display:none;"></p>
</div>

<style>
.suivi-repas-row {
    padding: 10px 0;
    border-bottom: 1px solid var(--border, #f0f0f0);
}
.suivi-repas-row:last-of-type { border-bottom: none; }

.suivi-repas-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}
.suivi-repas-header .meal-name {
    flex: 1;
    font-weight: 600;
    font-size: 0.9rem;
}

.suivi-repas-btns {
    display: flex;
    gap: 8px;
}
.suivi-repas-btns .btn {
    flex: 1;
    text-align: center;
    font-size: 0.82rem;
    padding: 8px 4px;
}

/* Variantes boutons statuts */
.btn-success-outline {
    border: 1.5px solid var(--success, #2e7d32);
    color: var(--success, #2e7d32);
    background: transparent;
}
.btn-success-outline.active,
.btn-success-outline:active {
    background: var(--success, #2e7d32);
    color: #fff;
}
.btn-danger-outline {
    border: 1.5px solid var(--danger, #c62828);
    color: var(--danger, #c62828);
    background: transparent;
}
.btn-danger-outline.active,
.btn-danger-outline:active {
    background: var(--danger, #c62828);
    color: #fff;
}
.btn-outline.active {
    background: var(--border, #ccc);
    color: var(--text, #333);
}

/* Sliders */
.slider-group { margin-bottom: 16px; }
.slider-group label {
    display: flex;
    align-items: baseline;
    gap: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 6px;
    color: var(--text, #222);
}
.slider-group label span:first-of-type {
    margin-left: auto;
    font-size: 1rem;
    font-weight: 700;
    color: var(--primary, #4CAF50);
}
.slider-group input[type="range"] {
    width: 100%;
    accent-color: var(--primary, #4CAF50);
}
.slider-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.72rem;
    color: var(--text-muted, #888);
    margin-top: 2px;
}
</style>

<script>
(function () {
    var today = '<?= $today ?>';

    /* ── Sliders live ───────────────────────────────── */
    function bindSlider(id, displayId, decimals) {
        var slider  = document.getElementById(id);
        var display = document.getElementById(displayId);
        if (!slider || !display) return;
        slider.addEventListener('input', function () {
            display.textContent = decimals
                ? parseFloat(this.value).toFixed(1)
                : this.value;
        });
    }
    bindSlider('sl-poids',   'sl-poids-val',   true);
    bindSlider('sl-humeur',  'sl-humeur-val',  false);
    bindSlider('sl-energie', 'sl-energie-val', false);
    bindSlider('sl-sommeil', 'sl-sommeil-val', false);

    /* ── Maj repas ──────────────────────────────────── */
    window.majRepas = async function (typeRepas, statut, btn) {
        var row  = btn.closest('.suivi-repas-row');
        var btns = row ? row.querySelectorAll('.suivi-btn') : [];
        var fb   = document.getElementById('repas-feedback');

        // Désactiver boutons le temps de la requête
        btns.forEach(function (b) { b.disabled = true; });

        try {
            var res = await api('suivi', 'POST', {
                action:     'maj_repas',
                type_repas: typeRepas,
                statut:     statut,
                date:       today,
            });

            if (res.ok || res.id) {
                // Mettre à jour l'état visuel des boutons
                btns.forEach(function (b) {
                    b.classList.toggle('active', b.dataset.statut === statut);
                });

                // Mettre à jour le badge
                var badge = document.getElementById('badge-' + typeRepas);
                if (badge) {
                    badge.style.display = '';
                    badge.className = 'badge suivi-badge ' + statutToBadgeClass(statut);
                    badge.textContent = statutToLabel(statut);
                }

                // Feedback discret
                fb.style.display = 'block';
                fb.textContent = 'Mis à jour ✓';
                fb.style.color = 'var(--success)';
                setTimeout(function () { fb.style.display = 'none'; }, 1500);
            } else {
                fb.style.display = 'block';
                fb.textContent = res.error || 'Erreur lors de la mise à jour.';
                fb.style.color = 'var(--danger)';
            }
        } catch (e) {
            fb.style.display = 'block';
            fb.textContent = 'Erreur réseau.';
            fb.style.color = 'var(--danger)';
        } finally {
            btns.forEach(function (b) { b.disabled = false; });
        }
    };

    function statutToBadgeClass(statut) {
        return {
            mange:    'badge-success',
            saute:    'badge-neutral',
            craquage: 'badge-danger',
        }[statut] || 'badge-warning';
    }

    function statutToLabel(statut) {
        return {
            mange:    'Mangé',
            saute:    'Sauté',
            craquage: 'Craquage',
        }[statut] || statut;
    }

    /* ── Sauvegarde bien-être ────────────────────────── */
    document.getElementById('btn-save-bienetre').addEventListener('click', async function () {
        var btn  = this;
        var fb   = document.getElementById('bienetre-feedback');
        btn.disabled    = true;
        btn.textContent = 'Enregistrement…';

        try {
            var res = await api('suivi', 'POST', {
                action:  'maj_jour',
                date:    today,
                poids:   parseFloat(document.getElementById('sl-poids').value),
                humeur:  parseInt(document.getElementById('sl-humeur').value, 10),
                energie: parseInt(document.getElementById('sl-energie').value, 10),
                sommeil: parseInt(document.getElementById('sl-sommeil').value, 10),
                note:    document.getElementById('note-jour').value,
            });

            fb.style.display = 'block';
            if (res.ok) {
                fb.textContent = 'Bien-être enregistré ✓';
                fb.style.color = 'var(--success)';
                btn.textContent = 'Enregistrer le bien-être';
            } else {
                fb.textContent = res.error || 'Erreur lors de la sauvegarde.';
                fb.style.color = 'var(--danger)';
                btn.textContent = 'Enregistrer le bien-être';
            }
        } catch (e) {
            fb.style.display = 'block';
            fb.textContent = 'Erreur réseau.';
            fb.style.color = 'var(--danger)';
            btn.textContent = 'Enregistrer le bien-être';
        } finally {
            btn.disabled = false;
        }
    });
}());
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
