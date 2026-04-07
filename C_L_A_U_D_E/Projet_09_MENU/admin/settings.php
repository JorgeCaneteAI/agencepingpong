<?php
/**
 * MealCoach Admin — Paramètres
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../auth.php';

session_start();
requireLogin();

$pageTitle = 'Paramètres';
$activeNav = 'admin-settings';

$message     = null;
$messageType = 'success';

// ─── Traitement POST ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrf)) {
        $message     = 'Token CSRF invalide. Veuillez réessayer.';
        $messageType = 'error';
    } else {
        $budgetMax     = trim($_POST['budget_max'] ?? '');
        $poidsObjectif = trim($_POST['poids_objectif'] ?? '');
        $saison        = trim($_POST['saison'] ?? '');
        $newPassword   = $_POST['new_password'] ?? '';

        $errors = [];

        // Validation budget
        if ($budgetMax !== '' && (!is_numeric($budgetMax) || (float)$budgetMax < 0)) {
            $errors[] = 'Le budget max doit être un nombre positif.';
        }

        // Validation poids
        if ($poidsObjectif !== '' && (!is_numeric($poidsObjectif) || (float)$poidsObjectif <= 0)) {
            $errors[] = 'Le poids objectif doit être un nombre positif.';
        }

        // Validation saison
        $saisonsValides = ['printemps', 'ete', 'automne', 'hiver'];
        if ($saison !== '' && !in_array($saison, $saisonsValides, true)) {
            $errors[] = 'Saison invalide.';
        }

        if (empty($errors)) {
            if ($budgetMax !== '') {
                setSetting('budget_max', $budgetMax);
            }
            if ($poidsObjectif !== '') {
                setSetting('poids_objectif', $poidsObjectif);
            }
            if ($saison !== '') {
                setSetting('saison', $saison);
            }
            if ($newPassword !== '') {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                setSetting('password', $hash);
            }

            $message = 'Paramètres sauvegardés.';
        } else {
            $message     = implode(' ', $errors);
            $messageType = 'error';
        }
    }
}

// ─── Valeurs actuelles ────────────────────────────────────────────────────────
$budgetMax     = getSetting('budget_max')     ?? '';
$poidsObjectif = getSetting('poids_objectif') ?? '';
$saison        = getSetting('saison')         ?? 'printemps';

ob_start();
?>
<div class="settings-page">

    <?php if ($message): ?>
    <div class="alert alert--<?= $messageType === 'error' ? 'danger' : 'success' ?>" role="alert">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="" class="form settings-form" novalidate>
        <?= csrfField() ?>

        <!-- Budget -->
        <div class="form-group">
            <label for="budget_max" class="form-label">Budget hebdomadaire max (€)</label>
            <input
                type="number"
                id="budget_max"
                name="budget_max"
                class="input"
                step="0.01"
                min="0"
                value="<?= htmlspecialchars($budgetMax) ?>"
                placeholder="ex: 80"
                aria-describedby="budget-hint"
            >
            <span id="budget-hint" class="form-hint">Budget courses maximum par semaine.</span>
        </div>

        <!-- Poids objectif -->
        <div class="form-group">
            <label for="poids_objectif" class="form-label">Poids objectif (kg)</label>
            <input
                type="number"
                id="poids_objectif"
                name="poids_objectif"
                class="input"
                step="0.1"
                min="0"
                value="<?= htmlspecialchars($poidsObjectif) ?>"
                placeholder="ex: 65.5"
                aria-describedby="poids-hint"
            >
            <span id="poids-hint" class="form-hint">Poids cible pour le suivi de progression.</span>
        </div>

        <!-- Saison -->
        <div class="form-group">
            <label for="saison" class="form-label">Saison actuelle</label>
            <select id="saison" name="saison" class="input input--select" aria-describedby="saison-hint">
                <option value="printemps" <?= $saison === 'printemps' ? 'selected' : '' ?>>Printemps</option>
                <option value="ete"       <?= $saison === 'ete'       ? 'selected' : '' ?>>Été</option>
                <option value="automne"   <?= $saison === 'automne'   ? 'selected' : '' ?>>Automne</option>
                <option value="hiver"     <?= $saison === 'hiver'     ? 'selected' : '' ?>>Hiver</option>
            </select>
            <span id="saison-hint" class="form-hint">Utilisée pour filtrer les recettes et produits de saison.</span>
        </div>

        <!-- Nouveau mot de passe -->
        <div class="form-group">
            <label for="new_password" class="form-label">Nouveau mot de passe</label>
            <input
                type="password"
                id="new_password"
                name="new_password"
                class="input"
                autocomplete="new-password"
                placeholder="Laisser vide pour ne pas changer"
                aria-describedby="pwd-hint"
            >
            <span id="pwd-hint" class="form-hint">Optionnel — remplir uniquement pour modifier le mot de passe.</span>
        </div>

        <button type="submit" class="btn btn--primary btn--full">
            Sauvegarder les paramètres
        </button>
    </form>

</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
