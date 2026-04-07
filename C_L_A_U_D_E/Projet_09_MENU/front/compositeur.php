<?php
/**
 * MealCoach — Compositeur de repas
 * Compose un repas personnalisé à partir des équivalences nutritionnelles
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

// ── Rendu ─────────────────────────────────────────────────────────────────────
$pageTitle = 'Compositeur';
$activeNav = 'compositeur';
ob_start();
?>

<h1 class="mb-8">Compositeur</h1>
<p class="text-muted text-sm mb-16">Compose ton repas par équivalences</p>

<!-- Step 1 : Choix du type de repas -->
<div id="step-type" class="card">
    <div class="card-title">Quel repas ?</div>
    <div class="type-repas-grid">
        <?php
        $typesRepas = [
            'petit_dej' => ['label' => 'Petit déjeuner', 'emoji' => '🌅'],
            'dejeuner'  => ['label' => 'Déjeuner',       'emoji' => '☀️'],
            'encas'     => ['label' => 'En-cas',         'emoji' => '🍎'],
            'diner'     => ['label' => 'Dîner',          'emoji' => '🌙'],
            'dessert'   => ['label' => 'Dessert',        'emoji' => '🍮'],
        ];
        foreach ($typesRepas as $type => $cfg): ?>
        <button type="button"
                class="btn-type-repas"
                data-type="<?= htmlspecialchars($type) ?>"
                onclick="choisirType('<?= htmlspecialchars($type) ?>')">
            <span class="type-emoji"><?= $cfg['emoji'] ?></span>
            <span class="type-label"><?= htmlspecialchars($cfg['label']) ?></span>
        </button>
        <?php endforeach; ?>
    </div>
</div>

<!-- Step 2 : Composition (masqué par défaut) -->
<div id="step-composer" style="display:none;">

    <!-- Barre type repas actif -->
    <div class="card" style="padding:12px 16px; display:flex; align-items:center; gap:10px; margin-bottom:0;">
        <span id="active-type-label" style="font-weight:700; font-size:1rem;"></span>
        <button type="button"
                class="btn btn-outline btn-sm"
                style="margin-left:auto;"
                onclick="resetType()">Changer</button>
    </div>

    <!-- Favoris -->
    <div id="favoris-section" style="display:none;">
        <div class="card">
            <div class="card-title" style="color:var(--primary);">★ Favoris</div>
            <div id="favoris-list"></div>
        </div>
    </div>

    <!-- Message de chargement -->
    <div id="loading-equiv" class="text-muted text-sm" style="padding:16px 0; display:none;">
        Chargement…
    </div>

    <!-- Validation globale -->
    <div id="validation-banner" style="display:none; padding:8px 0 4px;"></div>

    <!-- Catégories d'équivalences -->
    <div id="categories-container"></div>

</div>

<!-- Barre sticky bas -->
<div id="sticky-save" style="
    display:none;
    position:fixed;
    bottom:64px;
    left:0; right:0;
    padding:12px 16px;
    background:var(--bg-card, #fff);
    border-top:1px solid var(--border, #eee);
    z-index:100;
">
    <div style="display:flex; gap:10px; align-items:center;">
        <div id="global-status" style="flex:1; font-size:0.85rem;"></div>
        <button type="button" class="btn btn-primary" id="btn-sauvegarder" onclick="sauvegarder()">
            Sauvegarder
        </button>
    </div>
</div>

<style>
.type-repas-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    padding-top: 4px;
}
.btn-type-repas {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 14px 8px;
    border: 2px solid var(--border, #eee);
    border-radius: 12px;
    background: var(--bg-card, #fff);
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s;
    font-size: 0.85rem;
    color: var(--text, #222);
}
.btn-type-repas:active,
.btn-type-repas.selected {
    border-color: var(--primary, #4CAF50);
    background: color-mix(in srgb, var(--primary, #4CAF50) 8%, transparent);
}
.type-emoji { font-size: 1.6rem; }
.type-label { font-weight: 600; text-align: center; }

.equiv-categorie { margin-bottom: 8px; }
.equiv-cat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: var(--bg-card, #fff);
    border-radius: 12px;
    margin-bottom: 0;
    cursor: pointer;
    border: 1px solid var(--border, #eee);
}
.equiv-cat-header.open {
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
}
.equiv-cat-name { font-weight: 700; font-size: 0.95rem; }
.equiv-cat-status {
    width: 12px; height: 12px;
    border-radius: 50%;
    background: var(--border, #ccc);
    flex-shrink: 0;
    transition: background 0.2s;
}
.equiv-cat-status.ok    { background: var(--success, #2e7d32); }
.equiv-cat-status.warn  { background: var(--warning, #f57c00); }
.equiv-cat-status.error { background: var(--danger,  #c62828); }

.equiv-items-wrapper {
    display: none;
    background: var(--bg-card, #fff);
    border: 1px solid var(--border, #eee);
    border-top: none;
    border-radius: 0 0 12px 12px;
    padding: 8px 0;
}
.equiv-cat-header.open + .equiv-items-wrapper { display: block; }

.equiv-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    cursor: pointer;
    border-radius: 8px;
    margin: 2px 8px;
    transition: background 0.1s;
}
.equiv-item:active { background: var(--bg, #f5f5f5); }
.equiv-item.selected {
    background: color-mix(in srgb, var(--primary, #4CAF50) 12%, transparent);
}
.equiv-check {
    width: 22px; height: 22px;
    border-radius: 50%;
    border: 2px solid var(--border, #ccc);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    font-size: 0.75rem;
    transition: border-color 0.15s, background 0.15s;
}
.equiv-item.selected .equiv-check {
    border-color: var(--primary, #4CAF50);
    background: var(--primary, #4CAF50);
    color: #fff;
}
.equiv-nom  { font-weight: 600; font-size: 0.9rem; }
.equiv-portion  { font-size: 0.8rem; color: var(--text-muted, #888); }
.equiv-details  { font-size: 0.78rem; color: var(--text-muted, #888); }

.favori-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 4px;
    border-bottom: 1px solid var(--border, #eee);
    cursor: pointer;
}
.favori-item:last-child { border-bottom: none; }
.favori-nom  { font-weight: 600; font-size: 0.9rem; }
.favori-meta { font-size: 0.78rem; color: var(--text-muted, #888); }
</style>

<script>
(function () {
    var typeActif   = null;
    var selections  = {};
    var equivData   = {};
    var validResult = {};

    var typesLabels = {
        petit_dej: '🌅 Petit déjeuner',
        dejeuner:  '☀️ Déjeuner',
        encas:     '🍎 En-cas',
        diner:     '🌙 Dîner',
        dessert:   '🍮 Dessert',
    };

    function el(tag, cls, text) {
        var e = document.createElement(tag);
        if (cls)  e.className = cls;
        if (text !== undefined) e.textContent = text;
        return e;
    }

    /* ── Choix du type ──────────────────────────────── */
    window.choisirType = function (type) {
        typeActif = type;
        selections = {};
        validResult = {};

        document.getElementById('step-type').style.display = 'none';
        document.getElementById('step-composer').style.display = 'block';
        document.getElementById('active-type-label').textContent = typesLabels[type] || type;
        document.getElementById('sticky-save').style.display = 'block';

        chargerEquivalences(type);
    };

    window.resetType = function () {
        typeActif = null;
        selections = {};
        document.getElementById('step-type').style.display = 'block';
        document.getElementById('step-composer').style.display = 'none';
        document.getElementById('sticky-save').style.display = 'none';
        document.getElementById('categories-container').textContent = '';
        document.getElementById('favoris-section').style.display = 'none';
    };

    /* ── Chargement des équivalences ────────────────── */
    async function chargerEquivalences(type) {
        var loading = document.getElementById('loading-equiv');
        loading.style.display = 'block';
        loading.textContent = 'Chargement…';
        loading.style.color = '';
        document.getElementById('categories-container').textContent = '';

        try {
            var data = await api('compositeur?type_repas=' + encodeURIComponent(type));

            equivData = {};
            var equivs = data.equivalences || data;

            if (Array.isArray(equivs)) {
                equivs.forEach(function (item) {
                    var cat = item.categorie || 'Divers';
                    if (!equivData[cat]) equivData[cat] = [];
                    equivData[cat].push(item);
                });
            } else if (typeof equivs === 'object' && equivs !== null) {
                equivData = equivs;
            }

            if (data.favoris && data.favoris.length > 0) {
                afficherFavoris(data.favoris);
            } else {
                document.getElementById('favoris-section').style.display = 'none';
            }

            loading.style.display = 'none';
            renderCategories();
        } catch (err) {
            loading.style.display = 'block';
            loading.textContent = 'Erreur de chargement.';
            loading.style.color = 'var(--danger)';
        }
    }

    /* ── Rendu catégories ───────────────────────────── */
    function renderCategories() {
        var container = document.getElementById('categories-container');
        container.textContent = '';

        Object.entries(equivData).forEach(function (entry) {
            var categorie = entry[0];
            var items     = entry[1];

            var wrap   = el('div', 'equiv-categorie');
            var header = el('div', 'equiv-cat-header');
            var nameEl = el('span', 'equiv-cat-name', categorie);
            var dot    = el('span', 'equiv-cat-status');
            dot.id = 'status-' + categorie.replace(/[^a-zA-Z0-9]/g, '-');
            header.appendChild(nameEl);
            header.appendChild(dot);
            header.addEventListener('click', function () {
                header.classList.toggle('open');
            });

            var itemsWrapper = el('div', 'equiv-items-wrapper');

            items.forEach(function (item) {
                var row = el('div', 'equiv-item');
                row.dataset.id        = item.id;
                row.dataset.categorie = categorie;

                var check   = el('span', 'equiv-check', '✓');
                var infoDiv = el('div');
                infoDiv.style.flex = '1';

                var nomEl = el('div', 'equiv-nom', item.nom);
                infoDiv.appendChild(nomEl);
                if (item.portion) {
                    infoDiv.appendChild(el('div', 'equiv-portion', item.portion));
                }
                if (item.details) {
                    infoDiv.appendChild(el('div', 'equiv-details', item.details));
                }

                row.appendChild(check);
                row.appendChild(infoDiv);
                row.addEventListener('click', function () {
                    toggleSelection(categorie, item.id, row);
                });
                itemsWrapper.appendChild(row);
            });

            wrap.appendChild(header);
            wrap.appendChild(itemsWrapper);
            container.appendChild(wrap);
        });
    }

    /* ── Sélection / désélection ────────────────────── */
    function toggleSelection(categorie, id, rowEl) {
        if (!selections[categorie]) selections[categorie] = [];
        var idx = selections[categorie].indexOf(id);
        if (idx === -1) {
            selections[categorie].push(id);
            rowEl.classList.add('selected');
        } else {
            selections[categorie].splice(idx, 1);
            rowEl.classList.remove('selected');
        }
        validerEnTempsReel();
    }

    /* ── Validation temps réel ──────────────────────── */
    var validTimeout = null;
    function validerEnTempsReel() {
        clearTimeout(validTimeout);
        validTimeout = setTimeout(async function () {
            var allIds = [];
            Object.values(selections).forEach(function (ids) {
                ids.forEach(function (id) { allIds.push(id); });
            });

            if (allIds.length === 0) {
                clearValidation();
                return;
            }

            try {
                var res = await api('compositeur', 'POST', {
                    action:     'valider',
                    type_repas: typeActif,
                    selections: allIds,
                });
                validResult = res;
                afficherValidation(res);
            } catch (e) {
                // silencieux
            }
        }, 400);
    }

    function clearValidation() {
        validResult = {};
        document.getElementById('validation-banner').style.display = 'none';
        document.getElementById('global-status').textContent = '';
        document.querySelectorAll('.equiv-cat-status').forEach(function (dot) {
            dot.className = 'equiv-cat-status';
        });
    }

    function afficherValidation(res) {
        var banner       = document.getElementById('validation-banner');
        var globalStatus = document.getElementById('global-status');

        if (res.categories) {
            Object.entries(res.categories).forEach(function (entry) {
                var cat  = entry[0];
                var info = entry[1];
                var dotId = 'status-' + cat.replace(/[^a-zA-Z0-9]/g, '-');
                var dot = document.getElementById(dotId);
                if (dot) {
                    dot.className = 'equiv-cat-status ' + (info.ok ? 'ok' : (info.warning ? 'warn' : 'error'));
                    dot.title = info.message || '';
                }
            });
        }

        banner.textContent = '';
        if (res.ok) {
            var alertOk = el('div', null, res.message || 'Repas équilibré ✓');
            alertOk.style.cssText = 'background:color-mix(in srgb,var(--success)12%,transparent);border-left:3px solid var(--success);padding:8px 12px;border-radius:8px;color:var(--success);font-weight:600;font-size:0.85rem;';
            banner.appendChild(alertOk);
            banner.style.display = 'block';
            globalStatus.style.color = 'var(--success)';
            globalStatus.textContent = res.message || 'Équilibré ✓';
        } else if (res.message) {
            var alertWarn = el('div', null, res.message);
            alertWarn.style.cssText = 'background:color-mix(in srgb,var(--warning)12%,transparent);border-left:3px solid var(--warning);padding:8px 12px;border-radius:8px;color:var(--warning);font-size:0.85rem;';
            banner.appendChild(alertWarn);
            banner.style.display = 'block';
            globalStatus.style.color = 'var(--warning)';
            globalStatus.textContent = res.message;
        } else {
            banner.style.display = 'none';
            globalStatus.textContent = '';
        }
    }

    /* ── Sauvegarde ─────────────────────────────────── */
    window.sauvegarder = async function () {
        var nom = prompt('Nom de ce repas (ex: Mon déjeuner légumes) :');
        if (nom === null) return;

        var allIds = [];
        Object.values(selections).forEach(function (ids) {
            ids.forEach(function (id) { allIds.push(id); });
        });

        var btn = document.getElementById('btn-sauvegarder');
        btn.disabled = true;
        btn.textContent = 'Sauvegarde…';

        try {
            var res = await api('compositeur', 'POST', {
                action:     'sauvegarder',
                type_repas: typeActif,
                selections: allIds,
                nom:        nom.trim() || 'Sans nom',
            });
            if (res.ok || res.id) {
                btn.textContent = 'Sauvegardé ✓';
                btn.style.background = 'var(--success)';
                setTimeout(function () {
                    btn.disabled = false;
                    btn.textContent = 'Sauvegarder';
                    btn.style.background = '';
                }, 2000);
            } else {
                alert('Erreur : ' + (res.error || 'Impossible de sauvegarder.'));
                btn.disabled = false;
                btn.textContent = 'Sauvegarder';
            }
        } catch (e) {
            alert('Erreur réseau.');
            btn.disabled = false;
            btn.textContent = 'Sauvegarder';
        }
    };

    /* ── Favoris ────────────────────────────────────── */
    function afficherFavoris(favoris) {
        var section = document.getElementById('favoris-section');
        var list    = document.getElementById('favoris-list');
        list.textContent = '';

        favoris.forEach(function (fav) {
            var row    = el('div', 'favori-item');
            var info   = el('div');
            var nomEl  = el('div', 'favori-nom', fav.nom || fav.name || '');
            info.appendChild(nomEl);
            if (fav.nb_selections) {
                info.appendChild(el('div', 'favori-meta', fav.nb_selections + ' fois choisi'));
            }
            var arrow = el('span', null, '→');
            arrow.style.fontSize = '1.2rem';
            row.appendChild(info);
            row.appendChild(arrow);
            row.addEventListener('click', function () { chargerFavori(fav); });
            list.appendChild(row);
        });

        section.style.display = 'block';
    }

    function chargerFavori(fav) {
        if (!fav.selections) return;
        selections = {};
        document.querySelectorAll('.equiv-item.selected').forEach(function (e) {
            e.classList.remove('selected');
        });
        (Array.isArray(fav.selections) ? fav.selections : []).forEach(function (id) {
            var row = document.querySelector('.equiv-item[data-id="' + id + '"]');
            if (row) {
                var cat = row.dataset.categorie;
                if (!selections[cat]) selections[cat] = [];
                selections[cat].push(id);
                row.classList.add('selected');
            }
        });
        validerEnTempsReel();
    }
}());
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
