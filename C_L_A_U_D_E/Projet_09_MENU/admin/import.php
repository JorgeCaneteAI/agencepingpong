<?php
/**
 * MealCoach — Admin : Import menu
 * Upload ou coller un fichier .md, prévisualiser et valider l'import.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/parser.php';
require_once __DIR__ . '/../src/models/Courses.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

$errors   = [];
$success  = '';
$preview  = null;
$rawContent = '';

// ── Traitement du formulaire ──────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Vérification CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $errors[] = 'Token CSRF invalide. Rechargez la page et réessayez.';
    }

    if (empty($errors)) {
        $action = $_POST['action'] ?? '';

        // Récupérer le contenu : fichier uploadé ou textarea
        if (!empty($_FILES['fichier_md']['tmp_name'])) {
            $rawContent = file_get_contents($_FILES['fichier_md']['tmp_name']);
        } elseif (!empty($_POST['contenu_md'])) {
            $rawContent = trim($_POST['contenu_md']);
        } else {
            $errors[] = 'Aucun contenu fourni. Uploadez un fichier .md ou collez le contenu.';
        }

        if (!empty($rawContent) && empty($errors)) {
            $parser = new MenuParser();
            $data   = $parser->parse($rawContent);

            if ($action === 'preview') {
                // Mode aperçu uniquement
                $preview = $data;

            } elseif ($action === 'validate') {
                // ── Validation & import ──────────────────────────────────────
                $db = getDb();

                try {
                    $db->beginTransaction();

                    // 1. Archiver la semaine active en cours
                    query(
                        "UPDATE semaines SET statut = 'archive' WHERE statut = 'active'"
                    );

                    // 2. Insérer la nouvelle semaine
                    $meta      = $data['metadata'];
                    $numero    = $meta['numero']    ?? null;
                    $saison    = $meta['saison']    ?? null;
                    $datesRaw  = $meta['dates_raw'] ?? null;
                    $budget    = $data['budget']    ?? null;

                    // Calculer date_debut / date_fin depuis dates_raw si possible
                    $dateDebut = null;
                    $dateFin   = null;
                    if ($datesRaw) {
                        // Exemple : "Du 7 au 13 avril 2026"
                        if (preg_match('/(\d{1,2})\s+au\s+(\d{1,2})\s+(\w+)\s+(\d{4})/ui', $datesRaw, $dm)) {
                            $moisMap = [
                                'janvier' => '01', 'février' => '02', 'mars' => '03',
                                'avril'   => '04', 'mai'     => '05', 'juin' => '06',
                                'juillet' => '07', 'août'    => '08', 'septembre' => '09',
                                'octobre' => '10', 'novembre'=> '11', 'décembre'  => '12',
                            ];
                            $mois = $moisMap[mb_strtolower($dm[3])] ?? '01';
                            $dateDebut = sprintf('%s-%s-%02d', $dm[4], $mois, (int) $dm[1]);
                            $dateFin   = sprintf('%s-%s-%02d', $dm[4], $mois, (int) $dm[2]);
                        }
                    }

                    // Nom de fichier .md sauvegardé
                    $filename = 'semaine'
                        . ($numero ? $numero : date('YmdHis'))
                        . '-' . ($saison ? mb_strtolower($saison) : 'import')
                        . '-' . date('Ymd')
                        . '.md';

                    $semaineId = (int) insert('semaines', [
                        'fichier'       => $filename,
                        'numero'        => $numero,
                        'date_debut'    => $dateDebut,
                        'date_fin'      => $dateFin,
                        'saison'        => $saison,
                        'budget_estime' => $budget,
                        'statut'        => 'active',
                        'contenu_brut'  => $rawContent,
                    ]);

                    // 3. Insérer les jours + repas
                    foreach ($data['jours'] as $jourData) {
                        $jourIndex = $jourData['jour'];

                        $dateJour = null;
                        if ($dateDebut) {
                            $dateJour = date('Y-m-d', strtotime($dateDebut . ' +' . $jourIndex . ' days'));
                        }

                        $menuJourId = (int) insert('menu_jours', [
                            'semaine_id' => $semaineId,
                            'jour'       => $jourIndex,
                            'date'       => $dateJour,
                        ]);

                        foreach ($jourData['repas'] as $typeRepas => $repas) {
                            if ($repas === null) {
                                continue;
                            }
                            insert('menu_repas', [
                                'menu_jour_id' => $menuJourId,
                                'type_repas'   => $typeRepas,
                                'nom_plat'     => $repas['nom_plat']  ?? '',
                                'contenu'      => $repas['contenu']   ?? '',
                                'source'       => 'import',
                            ]);
                        }
                    }

                    // 4. Insérer les tâches batch
                    foreach ($data['batch'] as $tache) {
                        insert('batch_taches', [
                            'semaine_id' => $semaineId,
                            'ordre'      => $tache['ordre']      ?? 0,
                            'heure'      => $tache['heure']      ?? '',
                            'action'     => $tache['action']     ?? '',
                            'equipement' => $tache['equipement'] ?? '',
                            'duree'      => $tache['duree']      ?? 10,
                            'resultat'   => $tache['resultat']   ?? null,
                        ]);
                    }

                    // 5. Générer la liste de courses
                    // Convertir le format courses pour Courses::creerDepuisParsing
                    $coursesFormatted = [];
                    foreach ($data['courses'] as $item) {
                        $coursesFormatted[] = [
                            'nom'      => $item['nom_brut']        ?? '',
                            'quantite' => $item['quantite']        ?? 1,
                            'unite'    => $item['unite']           ?? '',
                            'rayon'    => $item['categorie_rayon'] ?? 'divers',
                        ];
                    }
                    Courses::creerDepuisParsing($semaineId, $coursesFormatted);

                    // 6. Sauvegarder le fichier .md dans content/menus/
                    $contentMenusDir = CONTENT_PATH . '/menus';
                    if (!is_dir($contentMenusDir)) {
                        mkdir($contentMenusDir, 0750, true);
                    }
                    file_put_contents($contentMenusDir . '/' . $filename, $rawContent);

                    $db->commit();

                    $success = 'Import réussi ! Semaine ' . ($numero ?? '') . ' maintenant active.';
                    $rawContent = '';

                } catch (Exception $e) {
                    $db->rollBack();
                    $errors[] = 'Erreur lors de l\'import : ' . $e->getMessage();
                }
            }
        }
    }
}

// ── Rendu ─────────────────────────────────────────────────────────────────────
$pageTitle = 'Import menu';
$activeNav = 'admin-import';
ob_start();
?>

<h1 class="page-title">Import menu</h1>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger" role="alert">
    <?php foreach ($errors as $e): ?>
        <p><?= htmlspecialchars($e) ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success" role="alert">
    <p><?= htmlspecialchars($success) ?></p>
    <a href="<?= BASE_URL ?>/admin" class="btn btn-outline btn-sm mt-8">Retour au dashboard</a>
</div>
<?php endif; ?>

<?php if ($preview): ?>
<!-- Aperçu du fichier parsé -->
<div class="card mb-16">
    <div class="card-title">Aperçu du fichier</div>

    <div class="stat-row">
        <span class="stat-label">Semaine</span>
        <span class="stat-value"><?= htmlspecialchars((string) ($preview['metadata']['numero'] ?? '—')) ?></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Saison</span>
        <span class="stat-value"><?= htmlspecialchars(ucfirst((string) ($preview['metadata']['saison'] ?? '—'))) ?></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Dates</span>
        <span class="stat-value"><?= htmlspecialchars((string) ($preview['metadata']['dates_raw'] ?? '—')) ?></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Jours parsés</span>
        <span class="stat-value"><?= count($preview['jours']) ?> / 7</span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Tâches batch</span>
        <span class="stat-value"><?= count($preview['batch']) ?></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Articles courses</span>
        <span class="stat-value"><?= count($preview['courses']) ?></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Budget estimé</span>
        <span class="stat-value">
            <?= $preview['budget'] !== null ? number_format((float) $preview['budget'], 0, ',', '') . ' €' : '—' ?>
        </span>
    </div>
</div>

<form method="post" action="">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="validate">
    <input type="hidden" name="contenu_md" value="<?= htmlspecialchars($rawContent) ?>">
    <button type="submit" class="btn btn-primary btn-full">
        Valider et importer
    </button>
    <a href="<?= BASE_URL ?>/admin/import" class="btn btn-outline btn-full mt-8">Recommencer</a>
</form>

<?php else: ?>
<!-- Formulaire d'import -->
<div class="card">
    <div class="card-title">Choisir un fichier .md</div>

    <form method="post" action="" enctype="multipart/form-data" id="form-import">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="preview" id="form-action">

        <div class="form-group">
            <label for="fichier_md" class="form-label">Fichier .md</label>
            <input type="file" id="fichier_md" name="fichier_md"
                   accept=".md,text/markdown,text/plain"
                   class="form-input">
        </div>

        <div class="form-group">
            <label for="contenu_md" class="form-label">
                — ou coller le contenu Markdown —
            </label>
            <textarea id="contenu_md" name="contenu_md"
                      class="form-input"
                      rows="10"
                      placeholder="# SEMAINE 1 — PRINTEMPS — Du 7 au 13 avril 2026&#10;..."><?= htmlspecialchars($rawContent) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-full">
            Prévisualiser
        </button>
    </form>
</div>

<script>
// Si un fichier est sélectionné, on lit son contenu et on pré-remplit le textarea
document.getElementById('fichier_md').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (e) {
        document.getElementById('contenu_md').value = e.target.result;
    };
    reader.readAsText(file);
});
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
