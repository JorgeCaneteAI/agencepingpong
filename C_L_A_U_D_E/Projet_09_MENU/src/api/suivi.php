<?php
/**
 * MealCoach — API Suivi
 * GET  ?date=YYYY-MM-DD  → {jour, repas}
 * POST action=maj_jour   → {ok: true}
 * POST action=maj_repas  → {ok: true}
 */

require_once BASE_PATH . '/src/models/Suivi.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $date = $_GET['date'] ?? date('Y-m-d');
    $jour = Suivi::getOuCreerJour($date);
    $repas = Suivi::getRepas((int) $jour['id']);
    echo json_encode(['jour' => $jour, 'repas' => $repas]);
    exit;
}

if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? '';

    if ($action === 'maj_jour') {
        $date = $body['date'] ?? date('Y-m-d');
        $data = array_intersect_key($body, array_flip(['poids', 'humeur', 'energie', 'sommeil', 'note']));
        Suivi::majJour($date, $data);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'maj_repas') {
        $date      = $body['date']      ?? date('Y-m-d');
        $typeRepas = $body['type_repas'] ?? '';
        $statut    = $body['statut']    ?? 'prevu';
        $detail    = $body['detail']    ?? null;
        $jour = Suivi::getOuCreerJour($date);
        Suivi::majRepas((int) $jour['id'], $typeRepas, $statut, $detail);
        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Action inconnue']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Méthode non autorisée']);
