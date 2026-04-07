<?php
/**
 * MealCoach — Model Stock
 */

require_once __DIR__ . '/../db.php';

class Stock
{
    /**
     * Retourne tout le stock avec les infos produit (nom, categorie, unite_mesure).
     */
    public static function getAll(): array
    {
        return fetchAll(
            'SELECT s.*, p.nom, p.categorie, p.unite_mesure
             FROM stock s
             JOIN produits p ON p.id = s.produit_id
             ORDER BY p.categorie, p.nom'
        );
    }

    /**
     * Retourne les lignes de stock pour un produit donné.
     */
    public static function getByProduit(int $produitId): array|false
    {
        return fetchOne(
            'SELECT s.*, p.nom, p.categorie, p.unite_mesure
             FROM stock s
             JOIN produits p ON p.id = s.produit_id
             WHERE s.produit_id = :pid',
            [':pid' => $produitId]
        );
    }

    /**
     * Ajoute une quantité au stock (incrémente si existe, insère sinon). Retourne l'id.
     */
    public static function ajouter(int $produitId, float $quantite, string $unite, ?string $peremption): int
    {
        $existing = fetchOne(
            'SELECT id, quantite FROM stock WHERE produit_id = :pid',
            [':pid' => $produitId]
        );

        if ($existing) {
            $newQty = $existing['quantite'] + $quantite;
            update(
                'stock',
                ['quantite' => $newQty, 'unite' => $unite, 'date_peremption' => $peremption, 'updated_at' => date('Y-m-d H:i:s')],
                'id = :id',
                [':id' => $existing['id']]
            );
            return (int) $existing['id'];
        }

        return (int) insert('stock', [
            'produit_id'      => $produitId,
            'quantite'        => $quantite,
            'unite'           => $unite,
            'date_peremption' => $peremption,
        ]);
    }

    /**
     * Retire une quantité du stock. Supprime la ligne si quantité <= 0.
     */
    public static function retirer(int $produitId, float $quantite): void
    {
        $existing = fetchOne(
            'SELECT id, quantite FROM stock WHERE produit_id = :pid',
            [':pid' => $produitId]
        );

        if (!$existing) {
            return;
        }

        $newQty = $existing['quantite'] - $quantite;

        if ($newQty <= 0) {
            query('DELETE FROM stock WHERE id = :id', [':id' => $existing['id']]);
        } else {
            update(
                'stock',
                ['quantite' => $newQty, 'updated_at' => date('Y-m-d H:i:s')],
                'id = :id',
                [':id' => $existing['id']]
            );
        }
    }

    /**
     * Définit la quantité d'une ligne de stock. Supprime si <= 0.
     */
    public static function setQuantite(int $id, float $quantite): void
    {
        if ($quantite <= 0) {
            query('DELETE FROM stock WHERE id = :id', [':id' => $id]);
        } else {
            update(
                'stock',
                ['quantite' => $quantite, 'updated_at' => date('Y-m-d H:i:s')],
                'id = :id',
                [':id' => $id]
            );
        }
    }

    /**
     * Retourne les produits dont la date de péremption approche dans $joursAvant jours.
     */
    public static function alertesPeremption(int $joursAvant = 3): array
    {
        return fetchAll(
            "SELECT s.*, p.nom, p.categorie, p.unite_mesure
             FROM stock s
             JOIN produits p ON p.id = s.produit_id
             WHERE s.date_peremption IS NOT NULL
               AND s.date_peremption <= date('now', '+' || :jours || ' days')
             ORDER BY s.date_peremption",
            [':jours' => $joursAvant]
        );
    }

    /**
     * Vérifie si un produit est en stock (quantite > 0).
     */
    public static function estEnStock(int $produitId): bool
    {
        $row = fetchOne(
            'SELECT quantite FROM stock WHERE produit_id = :pid',
            [':pid' => $produitId]
        );
        return $row !== false && $row['quantite'] > 0;
    }
}
