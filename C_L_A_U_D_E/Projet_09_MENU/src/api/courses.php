<?php
/**
 * MealCoach — API Courses
 * GET  ?semaine_id=N      → {items, stats}
 * POST action=toggle      → {ok: true, achete: bool}
 * POST action=ajouter     → {ok: true, id: int}
 */

require_once BASE_PATH . '/src/models/Courses.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $semaineId = (int) ($_GET['semaine_id'] ?? 0);
    $items     = Courses::getItemsBySemaine($semaineId);
    $liste     = Courses::getListeBySemaine($semaineId);
    $stats     = $liste ? Courses::statsListe((int) $liste['id']) : ['total' => 0, 'achetes' => 0, 'cout_estime' => 0];
    echo json_encode(['items' => $items, 'stats' => $stats]);
    exit;
}

if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? '';

    if ($action === 'toggle') {
        $id    = (int) ($body['id'] ?? 0);
        $achete = Courses::toggleAchete($id);
        echo json_encode(['ok' => true, 'achete' => $achete]);
        exit;
    }

    if ($action === 'ajouter') {
        $listeId  = (int)   ($body['liste_id']  ?? 0);
        $nom      = (string)($body['nom']       ?? '');
        $quantite = (float) ($body['quantite']  ?? 1);
        $unite    = (string)($body['unite']     ?? '');
        $rayon    = (string)($body['rayon']     ?? 'divers');
        $id = Courses::ajouterItem($listeId, $nom, $quantite, $unite, $rayon);
        echo json_encode(['ok' => true, 'id' => $id]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Action inconnue']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Méthode non autorisée']);
