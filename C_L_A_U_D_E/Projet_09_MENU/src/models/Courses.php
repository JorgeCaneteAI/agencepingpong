<?php
/**
 * MealCoach — Model Courses
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/Stock.php';

class Courses
{
    /**
     * Retourne la liste de courses d'une semaine.
     */
    public static function getListeBySemaine(int $semaineId): array|false
    {
        return fetchOne(
            'SELECT * FROM listes_courses WHERE semaine_id = :sid ORDER BY id DESC',
            [':sid' => $semaineId]
        );
    }

    /**
     * Retourne les items d'une liste de courses avec infos produit, triés par rayon.
     */
    public static function getItemsBySemaine(int $semaineId): array
    {
        $liste = self::getListeBySemaine($semaineId);
        if (!$liste) {
            return [];
        }

        return fetchAll(
            'SELECT li.*, p.nom AS produit_nom, p.categorie, p.unite_mesure
             FROM liste_items li
             LEFT JOIN produits p ON p.id = li.produit_id
             WHERE li.liste_id = :lid
             ORDER BY li.categorie_rayon, COALESCE(p.nom, li.nom_brut)',
            [':lid' => $liste['id']]
        );
    }

    /**
     * Crée une liste de courses à partir de données parsées.
     * $coursesData : [['nom' => ..., 'quantite' => ..., 'unite' => ..., 'rayon' => ...], ...]
     * Retourne le liste_id.
     */
    public static function creerDepuisParsing(int $semaineId, array $coursesData): int
    {
        $listeId = (int) insert('listes_courses', [
            'semaine_id' => $semaineId,
            'cout_estime' => 0,
        ]);

        $coutTotal = 0.0;

        foreach ($coursesData as $item) {
            $nom      = $item['nom']      ?? '';
            $quantite = $item['quantite'] ?? 1;
            $unite    = $item['unite']    ?? '';
            $rayon    = $item['rayon']    ?? 'divers';

            // Tenter de matcher un produit par nom
            $produit = fetchOne(
                'SELECT id, prix_unitaire FROM produits WHERE nom LIKE :nom',
                [':nom' => '%' . $nom . '%']
            );

            $produitId  = $produit ? (int) $produit['id'] : null;
            $enStock    = $produitId !== null && Stock::estEnStock($produitId) ? 1 : 0;
            $prixUnitaire = $produit ? (float) ($produit['prix_unitaire'] ?? 0) : 0;
            $prixEstime   = $prixUnitaire > 0 ? $prixUnitaire * $quantite : null;

            if ($prixEstime !== null) {
                $coutTotal += $prixEstime;
            }

            insert('liste_items', [
                'liste_id'       => $listeId,
                'produit_id'     => $produitId,
                'nom_brut'       => $nom,
                'quantite'       => $quantite,
                'unite'          => $unite,
                'categorie_rayon'=> $rayon,
                'prix_estime'    => $prixEstime,
                'en_stock'       => $enStock,
                'achete'         => 0,
                'ajout_manuel'   => 0,
            ]);
        }

        // Mettre à jour le coût estimé total de la liste
        update('listes_courses', ['cout_estime' => $coutTotal], 'id = :id', [':id' => $listeId]);

        return $listeId;
    }

    /**
     * Bascule l'état achete d'un item. Retourne le nouvel état (bool).
     */
    public static function toggleAchete(int $itemId): bool
    {
        $row = fetchOne('SELECT achete FROM liste_items WHERE id = :id', [':id' => $itemId]);
        if (!$row) {
            return false;
        }
        $newVal = $row['achete'] ? 0 : 1;
        update('liste_items', ['achete' => $newVal], 'id = :id', [':id' => $itemId]);
        return (bool) $newVal;
    }

    /**
     * Ajoute un item manuellement à une liste.
     */
    public static function ajouterItem(int $listeId, string $nom, float $quantite, string $unite, string $rayon): int
    {
        return (int) insert('liste_items', [
            'liste_id'        => $listeId,
            'nom_brut'        => $nom,
            'quantite'        => $quantite,
            'unite'           => $unite,
            'categorie_rayon' => $rayon,
            'achete'          => 0,
            'ajout_manuel'    => 1,
        ]);
    }

    /**
     * Retourne les stats d'une liste : total items, achetes, cout estimé.
     */
    public static function statsListe(int $listeId): array
    {
        $total = fetchOne(
            'SELECT COUNT(*) AS cnt FROM liste_items WHERE liste_id = :lid',
            [':lid' => $listeId]
        );
        $achetes = fetchOne(
            'SELECT COUNT(*) AS cnt FROM liste_items WHERE liste_id = :lid AND achete = 1',
            [':lid' => $listeId]
        );
        $cout = fetchOne(
            'SELECT SUM(prix_estime) AS total FROM liste_items WHERE liste_id = :lid',
            [':lid' => $listeId]
        );

        return [
            'total'       => (int)   ($total['cnt']   ?? 0),
            'achetes'     => (int)   ($achetes['cnt'] ?? 0),
            'cout_estime' => (float) ($cout['total']  ?? 0),
        ];
    }
}
