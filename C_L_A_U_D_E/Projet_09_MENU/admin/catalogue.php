<?php
/**
 * MealCoach Admin — Catalogue Produits
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/models/Produit.php';
require_once __DIR__ . '/../auth.php';

session_start();
requireLogin();

$pageTitle = 'Catalogue produits';
$activeNav = 'admin-catalogue';

// ─── Traitement POST ──────────────────────────────────────────────────────────
$message = null;
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $message = 'Token CSRF invalide.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'ajouter_produit') {
            $nom       = trim($_POST['nom'] ?? '');
            $categorie = trim($_POST['categorie'] ?? 'epicerie');
            $prix      = (float) ($_POST['prix'] ?? 0);

            if ($nom !== '') {
                Produit::create([
                    'nom'          => $nom,
                    'categorie'    => $categorie,
                    'prix_unitaire' => $prix,
                    'exclu'        => 0,
                ]);
                $message = "Produit « {$nom} » ajouté.";
            } else {
                $message     = 'Le nom du produit est requis.';
                $messageType = 'error';
            }
        } elseif ($action === 'exclure_par_nom') {
            $nom = trim($_POST['nom'] ?? '');
            if ($nom !== '') {
                Produit::exclureParNom($nom);
                $message = "Produit « {$nom} » retiré du catalogue.";
            } else {
                $message     = 'Nom requis.';
                $messageType = 'error';
            }
        }
    }
}

// ─── Données ──────────────────────────────────────────────────────────────────
$categories = Produit::getCategories();
$produits   = Produit::getAll(true); // inclure les exclus pour affichage toggle

ob_start();
?>
<div class="catalogue-page">

    <?php if ($message): ?>
    <div class="alert alert--<?= $messageType === 'error' ? 'danger' : 'success' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- Recherche -->
    <div class="search-bar">
        <input
            type="search"
            id="catalogue-search"
            class="input input--search"
            placeholder="Rechercher un produit…"
            aria-label="Rechercher un produit"
            autocomplete="off"
        >
    </div>

    <!-- Onglets catégories -->
    <div class="category-tabs" role="tablist" aria-label="Catégories">
        <button class="tab-btn active" data-cat="all" role="tab" aria-selected="true">Tous</button>
        <?php foreach ($categories as $cat): ?>
        <button class="tab-btn" data-cat="<?= htmlspecialchars($cat['categorie']) ?>" role="tab" aria-selected="false">
            <?= htmlspecialchars($cat['categorie']) ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Liste produits -->
    <ul class="product-list" id="product-list" aria-label="Liste des produits">
        <?php foreach ($produits as $p): ?>
        <li class="product-item<?= $p['exclu'] ? ' product-item--exclu' : '' ?>"
            data-nom="<?= htmlspecialchars(strtolower($p['nom'])) ?>"
            data-cat="<?= htmlspecialchars($p['categorie']) ?>">

            <div class="product-item__info">
                <span class="product-item__nom"><?= htmlspecialchars($p['nom']) ?></span>
                <span class="product-item__meta">
                    <?= htmlspecialchars($p['categorie']) ?>
                    <?php if ($p['prix_unitaire']): ?>
                     · <?= number_format((float)$p['prix_unitaire'], 2, ',', '') ?> €
                    <?php endif; ?>
                </span>
            </div>

            <div class="product-item__actions">
                <?php if ($p['exclu']): ?>
                <button class="btn btn--sm btn--success btn-toggle"
                        data-id="<?= (int)$p['id'] ?>"
                        data-action="inclure"
                        aria-label="Inclure <?= htmlspecialchars($p['nom']) ?>">
                    Inclure
                </button>
                <?php else: ?>
                <button class="btn btn--sm btn--danger btn-toggle"
                        data-id="<?= (int)$p['id'] ?>"
                        data-action="exclure"
                        aria-label="Exclure <?= htmlspecialchars($p['nom']) ?>">
                    Exclure
                </button>
                <?php endif; ?>
            </div>

        </li>
        <?php endforeach; ?>
    </ul>

    <!-- Retirer un produit par nom -->
    <section class="form-section">
        <h2 class="section-title">Retirer un produit</h2>
        <form method="POST" action="" class="form">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="exclure_par_nom">
            <div class="form-row">
                <input type="text" name="nom" class="input" placeholder="Nom du produit…" required aria-label="Nom du produit à retirer">
                <button type="submit" class="btn btn--danger">Retirer</button>
            </div>
            <p class="form-hint">Le produit sera créé comme exclu s'il n'existe pas encore.</p>
        </form>
    </section>

    <!-- Ajouter un produit -->
    <section class="form-section">
        <h2 class="section-title">Ajouter un produit</h2>
        <form method="POST" action="" class="form" id="form-ajouter">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="ajouter_produit">

            <div class="form-group">
                <label for="add-nom" class="form-label">Nom</label>
                <input type="text" id="add-nom" name="nom" class="input" required placeholder="ex: Quinoa">
            </div>

            <div class="form-group">
                <label for="add-cat" class="form-label">Catégorie</label>
                <select id="add-cat" name="categorie" class="input input--select">
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['categorie']) ?>">
                        <?= htmlspecialchars($cat['categorie']) ?>
                    </option>
                    <?php endforeach; ?>
                    <option value="autre">autre</option>
                </select>
            </div>

            <div class="form-group">
                <label for="add-prix" class="form-label">Prix unitaire (€)</label>
                <input type="number" id="add-prix" name="prix" class="input" step="0.01" min="0" placeholder="0.00">
            </div>

            <button type="submit" class="btn btn--primary btn--full">Ajouter le produit</button>
        </form>
    </section>

</div>

<script>
(function () {
    'use strict';

    const BASE_URL = document.querySelector('meta[name="base-url"]').content;

    // ─── Filtre de recherche ───────────────────────────────────────────────────
    const searchInput = document.getElementById('catalogue-search');
    const items       = Array.from(document.querySelectorAll('#product-list .product-item'));

    function filterItems() {
        const q   = searchInput.value.toLowerCase().trim();
        const cat = document.querySelector('.tab-btn.active')?.dataset.cat || 'all';

        items.forEach(function (item) {
            const matchSearch = q === '' || item.dataset.nom.includes(q);
            const matchCat    = cat === 'all' || item.dataset.cat === cat;
            item.hidden = !(matchSearch && matchCat);
        });
    }

    searchInput.addEventListener('input', filterItems);

    // ─── Onglets catégories ────────────────────────────────────────────────────
    document.querySelectorAll('.tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.tab-btn').forEach(function (b) {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            btn.classList.add('active');
            btn.setAttribute('aria-selected', 'true');
            filterItems();
        });
    });

    // ─── Boutons exclure / inclure ─────────────────────────────────────────────
    document.querySelectorAll('.btn-toggle').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const id     = btn.dataset.id;
            const action = btn.dataset.action;

            btn.disabled = true;

            try {
                const res = await fetch(BASE_URL + '/api/produits', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action, id: parseInt(id, 10) })
                });
                const data = await res.json();

                if (data.ok) {
                    const li = btn.closest('.product-item');
                    if (action === 'exclure') {
                        li.classList.add('product-item--exclu');
                        btn.dataset.action = 'inclure';
                        btn.textContent    = 'Inclure';
                        btn.classList.remove('btn--danger');
                        btn.classList.add('btn--success');
                        btn.setAttribute('aria-label', 'Inclure ' + li.querySelector('.product-item__nom').textContent);
                    } else {
                        li.classList.remove('product-item--exclu');
                        btn.dataset.action = 'exclure';
                        btn.textContent    = 'Exclure';
                        btn.classList.remove('btn--success');
                        btn.classList.add('btn--danger');
                        btn.setAttribute('aria-label', 'Exclure ' + li.querySelector('.product-item__nom').textContent);
                    }
                } else {
                    alert(data.error || 'Erreur serveur.');
                }
            } catch (e) {
                alert('Erreur réseau : ' + e.message);
            } finally {
                btn.disabled = false;
            }
        });
    });
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
