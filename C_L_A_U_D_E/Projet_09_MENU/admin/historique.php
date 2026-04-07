<?php
/**
 * MealCoach Admin — Historique & Stats
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../auth.php';

session_start();
requireLogin();

$pageTitle = 'Historique';
$activeNav = 'admin-historique';

// ─── Données ──────────────────────────────────────────────────────────────────

// Poids — 14 derniers jours
$poidsData = fetchAll(
    "SELECT date, poids FROM suivi_jours WHERE poids IS NOT NULL ORDER BY date DESC LIMIT 14"
);
// Inverser pour avoir l'ordre chronologique dans le graphe
$poidsData = array_reverse($poidsData);

// Calcul min/max pour l'échelle du graphe à barres
$poidsValues = array_column($poidsData, 'poids');
$poidsMin = !empty($poidsValues) ? (float) min($poidsValues) : 0;
$poidsMax = !empty($poidsValues) ? (float) max($poidsValues) : 1;
$poidsRange = ($poidsMax - $poidsMin) ?: 1;

// Bien-être — humeur, énergie, sommeil
$bienEtreData = fetchAll(
    "SELECT date, humeur, energie, sommeil
     FROM suivi_jours
     WHERE humeur IS NOT NULL OR energie IS NOT NULL OR sommeil IS NOT NULL
     ORDER BY date DESC
     LIMIT 14"
);

// Craquages
$craquages = fetchAll(
    "SELECT sj.date, sr.type_repas, sr.detail
     FROM suivi_repas sr
     JOIN suivi_jours sj ON sr.suivi_jour_id = sj.id
     WHERE sr.statut = 'craquage'
     ORDER BY sj.date DESC
     LIMIT 20"
);

ob_start();
?>
<div class="historique-page">

    <!-- Évolution du poids -->
    <section class="histo-section">
        <h2 class="section-title">Évolution du poids</h2>

        <?php if (empty($poidsData)): ?>
        <p class="empty-state">Aucune donnée de poids enregistrée.</p>
        <?php else: ?>
        <div class="bar-chart" role="img" aria-label="Graphe evolution du poids sur 14 jours">
            <?php foreach ($poidsData as $row): ?>
            <?php
                $poids = (float) $row['poids'];
                $pct   = round((($poids - $poidsMin) / $poidsRange) * 80 + 10, 1);
                // entre 10% et 90% de hauteur
                $label = date('d/m', strtotime($row['date']));
            ?>
            <div class="bar-col">
                <span class="bar-value"><?= number_format($poids, 1, ',', '') ?></span>
                <div class="bar-wrap">
                    <div class="bar-fill" style="height: <?= $pct ?>%;" aria-valuenow="<?= $poids ?>" role="meter"></div>
                </div>
                <span class="bar-label"><?= htmlspecialchars($label) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="poids-summary">
            <?php if (count($poidsValues) >= 2): ?>
            <?php
                $premier  = (float) $poidsValues[0];
                $dernier  = (float) $poidsValues[count($poidsValues) - 1];
                $delta    = $dernier - $premier;
                $sign     = $delta >= 0 ? '+' : '';
                $cssClass = $delta <= 0 ? 'delta--ok' : 'delta--ko';
            ?>
            <span class="poids-delta <?= $cssClass ?>">
                Variation : <?= $sign . number_format($delta, 1, ',', '') ?> kg
            </span>
            <?php endif; ?>
            <span class="poids-last">
                Dernier : <strong><?= number_format((float) end($poidsValues), 1, ',', '') ?> kg</strong>
            </span>
        </div>
        <?php endif; ?>
    </section>

    <!-- Bien-être -->
    <section class="histo-section">
        <h2 class="section-title">Bien-être (humeur · énergie · sommeil)</h2>

        <?php if (empty($bienEtreData)): ?>
        <p class="empty-state">Aucune donnée de bien-être enregistrée.</p>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="bienetre-table" aria-label="Données bien-être par jour">
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Humeur</th>
                        <th scope="col">Énergie</th>
                        <th scope="col">Sommeil</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bienEtreData as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($row['date']))) ?></td>
                        <td class="score score--<?= (int)($row['humeur'] ?? 0) ?>">
                            <?= $row['humeur'] !== null ? htmlspecialchars($row['humeur']) . '/5' : '—' ?>
                        </td>
                        <td class="score score--<?= (int)($row['energie'] ?? 0) ?>">
                            <?= $row['energie'] !== null ? htmlspecialchars($row['energie']) . '/5' : '—' ?>
                        </td>
                        <td class="score score--<?= (int)($row['sommeil'] ?? 0) ?>">
                            <?= $row['sommeil'] !== null ? htmlspecialchars($row['sommeil']) . 'h' : '—' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <!-- Craquages -->
    <section class="histo-section">
        <h2 class="section-title">Craquages récents</h2>

        <?php if (empty($craquages)): ?>
        <p class="empty-state">Aucun craquage enregistré.</p>
        <?php else: ?>
        <ul class="craquage-list" aria-label="Liste des craquages récents">
            <?php foreach ($craquages as $c): ?>
            <li class="craquage-item">
                <span class="craquage-item__date">
                    <?= htmlspecialchars(date('d/m/Y', strtotime($c['date']))) ?>
                </span>
                <span class="craquage-item__repas">
                    <?= htmlspecialchars($c['type_repas'] ?? '') ?>
                </span>
                <span class="craquage-item__detail">
                    <?= htmlspecialchars($c['detail'] ?? '') ?>
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>

</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
