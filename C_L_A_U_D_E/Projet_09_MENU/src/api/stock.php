<?php
/**
 * MealCoach — API Stock
 * GET              → Stock::getAll()
 * POST action=ajouter → {ok: true}
 * POST action=retirer → {ok: true}
 * POST action=set     → {ok: true}
 */

require_once BASE_PATH . '/src/models/Stock.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode(Stock::getAll());
    exit;
}

if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? '';

    if ($action === 'ajouter') {
        $produitId  = (int)   ($body['produit_id'] ?? 0);
        $quantite   = (float) ($body['quantite']   ?? 0);
        $unite      = (string)($body['unite']      ?? '');
        $peremption = $body['peremption'] ?? null;
        Stock::ajouter($produitId, $quantite, $unite, $peremption);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'retirer') {
        $produitId = (int)   ($body['produit_id'] ?? 0);
        $quantite  = (float) ($body['quantite']   ?? 0);
        Stock::retirer($produitId, $quantite);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'set') {
        $id       = (int)   ($body['id']       ?? 0);
        $quantite = (float) ($body['quantite'] ?? 0);
        Stock::setQuantite($id, $quantite);
        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Action inconnue']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Méthode non autorisée']);
