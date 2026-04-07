<?php
/**
 * MealCoach — API Produits
 * GET  ?q=...&categorie=...&inclure_exclus=1  → array of produits
 * POST action=create          → {ok: true, id: int}
 * POST action=update          → {ok: true}
 * POST action=exclure         → {ok: true}
 * POST action=inclure         → {ok: true}
 * POST action=exclure_par_nom → {ok: true, id: int}
 */

require_once BASE_PATH . '/src/models/Produit.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $q            = $_GET['q']            ?? '';
    $categorie    = $_GET['categorie']    ?? '';
    $inclureExclus = !empty($_GET['inclure_exclus']);

    if ($q !== '') {
        $produits = Produit::search($q);
    } elseif ($categorie !== '') {
        $produits = $inclureExclus
            ? Produit::getAll(true)
            : Produit::getByCategorie($categorie);
        // Filter by categorie when inclureExclus requested
        if ($inclureExclus && $categorie !== '') {
            $produits = array_values(array_filter($produits, fn($p) => ($p['categorie'] ?? '') === $categorie));
        }
    } else {
        $produits = Produit::getAll($inclureExclus);
    }

    echo json_encode($produits);
    exit;
}

if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? '';

    if ($action === 'create') {
        $id = Produit::create($body);
        echo json_encode(['ok' => true, 'id' => $id]);
        exit;
    }

    if ($action === 'update') {
        $id = (int) ($body['id'] ?? 0);
        Produit::update($id, $body);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'exclure') {
        $id = (int) ($body['id'] ?? 0);
        Produit::exclure($id);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'inclure') {
        $id = (int) ($body['id'] ?? 0);
        Produit::inclure($id);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'exclure_par_nom') {
        $nom      = (string)($body['nom']       ?? '');
        $categorie = (string)($body['categorie'] ?? 'epicerie');
        $id = Produit::exclureParNom($nom, $categorie);
        echo json_encode(['ok' => true, 'id' => $id]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Action inconnue']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Méthode non autorisée']);
