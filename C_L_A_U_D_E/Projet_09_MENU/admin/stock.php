<?php
/**
 * MealCoach Admin — Gestion du stock
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/models/Stock.php';
require_once __DIR__ . '/../src/models/Produit.php';
require_once __DIR__ . '/../auth.php';

session_start();
requireLogin();

$pageTitle = 'Stock';
$activeNav = 'admin-stock';

// ─── Données ──────────────────────────────────────────────────────────────────
$stockItems  = Stock::getAll();
$alertes     = Stock::alertesPeremption(3);
$produits    = Produit::getAll(false); // pour le select d'ajout

ob_start();
?>
<div class="stock-page">

    <!-- Alertes péremption -->
    <?php if (!empty($alertes)): ?>
    <section class="alert-section">
        <h2 class="section-title section-title--alert">
            Peremptions proches (3 jours ou moins)
        </h2>
        <ul class="peremption-list" aria-label="Produits a peremption proche">
            <?php foreach ($alertes as $a): ?>
            <li class="peremption-item">
                <span class="peremption-item__nom"><?= htmlspecialchars($a['nom']) ?></span>
                <span class="peremption-item__date"><?= htmlspecialchars($a['date_peremption']) ?></span>
                <span class="peremption-item__qty">
                    <?= htmlspecialchars($a['quantite']) ?> <?= htmlspecialchars($a['unite_mesure'] ?? $a['unite'] ?? '') ?>
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- Liste du stock -->
    <section class="stock-section">
        <h2 class="section-title">Mon stock</h2>

        <?php if (empty($stockItems)): ?>
        <p class="empty-state">Aucun produit en stock.</p>
        <?php else: ?>
        <ul class="stock-list" id="stock-list" aria-label="Articles en stock">
            <?php foreach ($stockItems as $item): ?>
            <li class="stock-item" data-produit-id="<?= (int)$item['produit_id'] ?>">
                <div class="stock-item__info">
                    <span class="stock-item__nom"><?= htmlspecialchars($item['nom']) ?></span>
                    <span class="stock-item__meta"><?= htmlspecialchars($item['categorie']) ?></span>
                    <?php if ($item['date_peremption']): ?>
                    <span class="stock-item__peremption">DLC : <?= htmlspecialchars($item['date_peremption']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="stock-item__qty-ctrl" role="group" aria-label="Quantite <?= htmlspecialchars($item['nom']) ?>">
                    <button class="btn-qty btn-qty--minus"
                            data-produit-id="<?= (int)$item['produit_id'] ?>"
                            data-action="retirer"
                            aria-label="Retirer 1 unite de <?= htmlspecialchars($item['nom']) ?>">
                        -
                    </button>
                    <span class="stock-item__qty" id="qty-<?= (int)$item['produit_id'] ?>">
                        <?= htmlspecialchars($item['quantite']) ?>
                        <small><?= htmlspecialchars($item['unite_mesure'] ?? $item['unite'] ?? '') ?></small>
                    </span>
                    <button class="btn-qty btn-qty--plus"
                            data-produit-id="<?= (int)$item['produit_id'] ?>"
                            data-action="ajouter"
                            aria-label="Ajouter 1 unite de <?= htmlspecialchars($item['nom']) ?>">
                        +
                    </button>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>

    <!-- Ajouter au stock -->
    <section class="form-section">
        <h2 class="section-title">Ajouter au stock</h2>

        <div class="form">
            <div class="form-group">
                <label for="stock-search" class="form-label">Rechercher un produit</label>
                <input type="search"
                       id="stock-search"
                       class="input input--search"
                       placeholder="Nom du produit..."
                       autocomplete="off"
                       aria-label="Rechercher un produit a ajouter au stock">
                <ul class="autocomplete-list" id="stock-autocomplete" hidden aria-label="Suggestions"></ul>
            </div>

            <div class="form-group" id="add-stock-fields" hidden>
                <input type="hidden" id="add-produit-id">

                <label for="add-qty" class="form-label">Quantite</label>
                <input type="number" id="add-qty" class="input" min="1" step="1" value="1">

                <label for="add-peremption" class="form-label">Date de peremption (optionnel)</label>
                <input type="date" id="add-peremption" class="input">

                <button type="button" id="btn-add-stock" class="btn btn--primary btn--full">
                    Ajouter au stock
                </button>
            </div>
        </div>
    </section>

</div>

<script>
(function () {
    'use strict';

    const BASE_URL = document.querySelector('meta[name="base-url"]').content;

    // Donnees produits pour autocomplete (JSON encode cote serveur)
    const produits = <?= json_encode(array_map(function($p) {
        return ['id' => (int)$p['id'], 'nom' => $p['nom'], 'categorie' => $p['categorie']];
    }, $produits), JSON_UNESCAPED_UNICODE) ?>;

    // ─── Boutons +/- stock ─────────────────────────────────────────────────────
    document.querySelectorAll('.btn-qty--plus, .btn-qty--minus').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const produitId = parseInt(btn.dataset.produitId, 10);
            const action    = btn.dataset.action; // 'ajouter' | 'retirer'

            btn.disabled = true;

            try {
                const res = await fetch(BASE_URL + '/api/stock', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action, produit_id: produitId, quantite: 1 })
                });
                const data = await res.json();

                if (data.ok) {
                    const qtyEl = document.getElementById('qty-' + produitId);
                    if (qtyEl && data.quantite !== undefined) {
                        // Mettre a jour l'affichage quantite
                        const small = qtyEl.querySelector('small');
                        const unit  = small ? small.textContent : '';
                        qtyEl.textContent = String(data.quantite);
                        if (unit) {
                            const sm = document.createElement('small');
                            sm.textContent = unit;
                            qtyEl.appendChild(sm);
                        }
                    }
                    // Si quantite tombe a 0, retirer la ligne
                    if (data.quantite !== undefined && data.quantite <= 0) {
                        const li = btn.closest('.stock-item');
                        if (li) li.remove();
                    }
                } else {
                    alert(data.error || 'Erreur serveur.');
                }
            } catch (e) {
                alert('Erreur reseau : ' + e.message);
            } finally {
                btn.disabled = false;
            }
        });
    });

    // ─── Autocomplete ajout stock ──────────────────────────────────────────────
    const searchInput   = document.getElementById('stock-search');
    const autocomplete  = document.getElementById('stock-autocomplete');
    const addFields     = document.getElementById('add-stock-fields');
    const addProduitId  = document.getElementById('add-produit-id');
    const addQty        = document.getElementById('add-qty');
    const addPeremption = document.getElementById('add-peremption');
    const btnAddStock   = document.getElementById('btn-add-stock');

    searchInput.addEventListener('input', function () {
        const q = searchInput.value.toLowerCase().trim();
        // Vider la liste de suggestions (DOM safe, pas de innerHTML)
        while (autocomplete.firstChild) {
            autocomplete.removeChild(autocomplete.firstChild);
        }

        if (q.length < 2) {
            autocomplete.hidden = true;
            return;
        }

        const matches = produits.filter(function (p) {
            return p.nom.toLowerCase().includes(q);
        }).slice(0, 8);

        if (matches.length === 0) {
            autocomplete.hidden = true;
            return;
        }

        matches.forEach(function (p) {
            const li  = document.createElement('li');
            li.className = 'autocomplete-item';
            li.textContent = p.nom + ' (' + p.categorie + ')';
            li.setAttribute('tabindex', '0');
            li.setAttribute('role', 'option');

            function selectProduit() {
                searchInput.value   = p.nom;
                addProduitId.value  = String(p.id);
                autocomplete.hidden = true;
                addFields.hidden    = false;
                addQty.focus();
            }

            li.addEventListener('click', selectProduit);
            li.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    selectProduit();
                }
            });
            autocomplete.appendChild(li);
        });

        autocomplete.hidden = false;
    });

    document.addEventListener('click', function (e) {
        if (!autocomplete.contains(e.target) && e.target !== searchInput) {
            autocomplete.hidden = true;
        }
    });

    // ─── Ajout au stock ────────────────────────────────────────────────────────
    btnAddStock.addEventListener('click', async function () {
        const produitId  = parseInt(addProduitId.value, 10);
        const quantite   = parseFloat(addQty.value) || 1;
        const peremption = addPeremption.value || null;

        if (!produitId) {
            alert('Selectionner un produit.');
            return;
        }

        btnAddStock.disabled = true;

        try {
            const res = await fetch(BASE_URL + '/api/stock', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'ajouter',
                    produit_id: produitId,
                    quantite: quantite,
                    peremption: peremption
                })
            });
            const data = await res.json();

            if (data.ok) {
                window.location.reload();
            } else {
                alert(data.error || 'Erreur serveur.');
            }
        } catch (e) {
            alert('Erreur reseau : ' + e.message);
        } finally {
            btnAddStock.disabled = false;
        }
    });
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
