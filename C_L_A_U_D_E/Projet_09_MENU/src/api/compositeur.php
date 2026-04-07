<?php
/**
 * MealCoach — API Compositeur
 * GET  ?type_repas=dejeuner     → {equivalences, regles}
 * POST action=valider           → validation result
 * POST action=sauvegarder       → {ok: true, id: int}
 * POST action=favoris           → array of favoris
 */

require_once BASE_PATH . '/src/models/Compositeur.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $typeRepas    = $_GET['type_repas'] ?? 'dejeuner';
    $equivalences = Compositeur::getEquivalences($typeRepas);
    $regles       = Compositeur::getRegles($typeRepas);
    echo json_encode(['equivalences' => $equivalences, 'regles' => $regles]);
    exit;
}

if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? '';

    if ($action === 'valider') {
        $typeRepas  = (string)($body['type_repas']  ?? 'dejeuner');
        $selections = (array) ($body['selections']  ?? []);
        $result = Compositeur::valider($typeRepas, $selections);
        echo json_encode($result);
        exit;
    }

    if ($action === 'sauvegarder') {
        $typeRepas = (string)($body['type_repas'] ?? 'dejeuner');
        $items     = (array) ($body['items']      ?? []);
        $nom       = $body['nom']    ?? null;
        $favori    = !empty($body['favori']);
        $id = Compositeur::sauvegarder($typeRepas, $items, $nom, $favori);
        echo json_encode(['ok' => true, 'id' => $id]);
        exit;
    }

    if ($action === 'favoris') {
        echo json_encode(Compositeur::getFavoris());
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Action inconnue']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Méthode non autorisée']);
