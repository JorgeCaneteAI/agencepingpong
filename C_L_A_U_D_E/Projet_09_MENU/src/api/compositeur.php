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

    if ($action === 'remplacer') {
        $repasId  = (int)($body['repas_id'] ?? 0);
        $nomPlat  = (string)($body['nom_plat'] ?? '');
        $contenu  = (string)($body['contenu'] ?? '');

        if (!$repasId || empty($nomPlat)) {
            http_response_code(400);
            echo json_encode(['error' => 'repas_id et nom_plat requis']);
            exit;
        }

        // Vérifier que le repas existe
        $existing = fetchOne('SELECT id FROM menu_repas WHERE id = :id', [':id' => $repasId]);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'Repas introuvable']);
            exit;
        }

        update('menu_repas', [
            'nom_plat' => $nomPlat,
            'contenu'  => $contenu,
            'source'   => 'compositeur',
        ], 'id = :id', [':id' => $repasId]);

        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'swap_composant') {
        $repasId   = (int)($body['repas_id'] ?? 0);
        $compIndex = (int)($body['comp_index'] ?? 0);
        $newValue  = (string)($body['new_value'] ?? '');

        if (!$repasId || empty($newValue)) {
            http_response_code(400);
            echo json_encode(['error' => 'Paramètres manquants']);
            exit;
        }

        $repas = fetchOne('SELECT id, contenu FROM menu_repas WHERE id = :id', [':id' => $repasId]);
        if (!$repas) {
            http_response_code(404);
            echo json_encode(['error' => 'Repas introuvable']);
            exit;
        }

        // Parser le contenu en segments "Cat : valeur"
        $segments = preg_split('/ - /', $repas['contenu']);
        if (isset($segments[$compIndex])) {
            $seg = $segments[$compIndex];
            $colonPos = mb_strpos($seg, ' : ');
            if ($colonPos !== false) {
                $cat = mb_substr($seg, 0, $colonPos + 3); // "Catégorie : "
                $segments[$compIndex] = $cat . $newValue;
            } else {
                $segments[$compIndex] = $newValue;
            }
            $newContenu = implode(' - ', $segments);
            update('menu_repas', ['contenu' => $newContenu], 'id = :id', [':id' => $repasId]);
            echo json_encode(['ok' => true, 'contenu' => $newContenu]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Index composant invalide']);
        }
        exit;
    }

    if ($action === 'planifier_semaine') {
        $planning  = (array)($body['planning'] ?? []);
        $dateDebut = (string)($body['date_debut'] ?? '');
        $dateFin   = (string)($body['date_fin'] ?? '');

        if (empty($planning) || empty($dateDebut)) {
            http_response_code(400);
            echo json_encode(['error' => 'Planning et date_debut requis']);
            exit;
        }

        // Trouver ou créer la semaine
        $existing = fetchOne(
            'SELECT id FROM semaines WHERE date_debut = :d',
            [':d' => $dateDebut]
        );
        if ($existing) {
            $semaineId = (int)$existing['id'];
            // Nettoyer les anciens repas de cette semaine
            $oldJours = fetchAll('SELECT id FROM menu_jours WHERE semaine_id = :sid', [':sid' => $semaineId]);
            foreach ($oldJours as $oj) {
                query('DELETE FROM menu_repas WHERE menu_jour_id = :mjid', [':mjid' => $oj['id']]);
            }
            query('DELETE FROM menu_jours WHERE semaine_id = :sid', [':sid' => $semaineId]);
        } else {
            // Calculer le numéro de semaine
            $maxNum = fetchOne('SELECT MAX(numero) as m FROM semaines');
            $numero = ($maxNum && $maxNum['m']) ? (int)$maxNum['m'] + 1 : 1;
            $semaineId = (int)insert('semaines', [
                'numero'     => $numero,
                'date_debut' => $dateDebut,
                'date_fin'   => $dateFin,
                'statut'     => 'active',
            ]);
            // Désactiver les autres semaines
            query('UPDATE semaines SET statut = :s WHERE id != :id', [':s' => 'terminee', ':id' => $semaineId]);
        }

        // Créer les 7 jours
        $jourIds = [];
        for ($j = 0; $j < 7; $j++) {
            $jourIds[$j] = (int)insert('menu_jours', [
                'semaine_id' => $semaineId,
                'jour'       => $j,
                'date'       => date('Y-m-d', strtotime($dateDebut . ' +' . $j . ' days')),
            ]);
        }

        // Insérer les repas planifiés
        foreach ($planning as $key => $data) {
            // key = "0-dejeuner", "3-diner", etc.
            $parts = explode('-', $key, 2);
            $jour = (int)$parts[0];
            $type = $parts[1] ?? 'dejeuner';

            if (!isset($jourIds[$jour])) continue;

            // Récupérer la recette complète
            $recette = fetchOne('SELECT * FROM recettes WHERE id = :id', [':id' => (int)($data['id'] ?? 0)]);
            if (!$recette) continue;

            insert('menu_repas', [
                'menu_jour_id' => $jourIds[$jour],
                'type_repas'   => $type,
                'nom_plat'     => $recette['nom'],
                'contenu'      => $recette['contenu'],
                'source'       => 'planificateur',
                'recette_id'   => $recette['id'],
            ]);
        }

        echo json_encode(['ok' => true, 'semaine_id' => $semaineId]);
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
