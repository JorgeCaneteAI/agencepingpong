<?php
/**
 * MealCoach — Rappel SMS J-2 via API Free Mobile
 *
 * Ce script est execute par un cron quotidien a 19h.
 * Il envoie un SMS rappelant les repas de dans 2 jours.
 *
 * Cron o2switch :
 *   0 19 * * * php /home/USER/public_html/menus/src/cron/rappel-sms.php
 *
 * Configuration requise dans la table `settings` :
 *   free_mobile_user  = identifiant Free Mobile
 *   free_mobile_pass  = cle API SMS
 */

// ── Bootstrap ────────────────────────────────────────────────
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/db.php';

// ── Config SMS ───────────────────────────────────────────────
$freeUser = getSetting('free_mobile_user', '');
$freePass = getSetting('free_mobile_pass', '');

if (empty($freeUser) || empty($freePass)) {
    echo "[SKIP] Pas de config SMS Free Mobile.\n";
    exit(0);
}

// ── Semaine active ───────────────────────────────────────────
$semaine = fetchOne(
    "SELECT * FROM semaines WHERE statut = 'active' ORDER BY date_debut DESC LIMIT 1"
);

if (!$semaine) {
    echo "[SKIP] Aucune semaine active.\n";
    exit(0);
}

// ── Jour J+2 ─────────────────────────────────────────────────
$j2Date = date('Y-m-d', strtotime('+2 days'));
$j2Jour = ((int) date('N', strtotime('+2 days'))) - 1; // 0=lundi

$nomsJours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
$nomJour = $nomsJours[$j2Jour] ?? 'bientot';

// ── Repas de J+2 ────────────────────────────────────────────
$menuJour = fetchOne(
    'SELECT * FROM menu_jours WHERE semaine_id = :sid AND jour = :jour',
    [':sid' => $semaine['id'], ':jour' => $j2Jour]
);

if (!$menuJour) {
    echo "[SKIP] Pas de menu pour $nomJour (jour $j2Jour).\n";
    exit(0);
}

$repas = fetchAll(
    'SELECT nom_plat, type_repas FROM menu_repas WHERE menu_jour_id = :mjid
     ORDER BY CASE type_repas
        WHEN \'petit_dej\' THEN 1
        WHEN \'dejeuner\'  THEN 2
        WHEN \'encas\'     THEN 3
        WHEN \'diner\'     THEN 4
        WHEN \'dessert\'   THEN 5
        ELSE 6 END',
    [':mjid' => $menuJour['id']]
);

if (empty($repas)) {
    echo "[SKIP] Aucun repas pour $nomJour.\n";
    exit(0);
}

// ── Construire le message ────────────────────────────────────
$plats = [];
foreach ($repas as $r) {
    $plats[] = $r['nom_plat'];
}
$listePlats = implode(', ', $plats);

$message = "MealCoach: Apres-demain ($nomJour) tu manges : $listePlats. Tu as tout ?";

// Limiter a 160 caracteres (SMS standard)
if (mb_strlen($message) > 160) {
    $message = mb_substr($message, 0, 157) . '...';
}

// ── Envoyer le SMS ───────────────────────────────────────────
$url = 'https://smsapi.free-mobile.fr/sendmsg?' . http_build_query([
    'user' => $freeUser,
    'pass' => $freePass,
    'msg'  => $message,
]);

$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'ignore_errors' => true,
    ],
    'ssl' => [
        'verify_peer' => true,
    ],
]);

$response = @file_get_contents($url, false, $context);
$httpCode = 0;
if (isset($http_response_header[0])) {
    preg_match('/\d{3}/', $http_response_header[0], $matches);
    $httpCode = (int) ($matches[0] ?? 0);
}

if ($httpCode === 200) {
    echo "[OK] SMS envoye pour $nomJour : $message\n";
} else {
    echo "[ERREUR] HTTP $httpCode — Echec envoi SMS.\n";
    echo "Message : $message\n";
}
