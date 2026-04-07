<?php
/**
 * MealCoach — Model Produit
 */

require_once __DIR__ . '/../db.php';

class Produit
{
    /**
     * Retourne tous les produits, avec ou sans les exclus.
     */
    public static function getAll(bool $inclureExclus = false): array
    {
        $sql = 'SELECT * FROM produits';
        if (!$inclureExclus) {
            $sql .= ' WHERE exclu = 0';
        }
        $sql .= ' ORDER BY categorie, nom';
        return fetchAll($sql);
    }

    /**
     * Retourne les produits d'une catégorie (non exclus).
     */
    public static function getByCategorie(string $categorie): array
    {
        return fetchAll(
            'SELECT * FROM produits WHERE categorie = :cat AND exclu = 0 ORDER BY nom',
            [':cat' => $categorie]
        );
    }

    /**
     * Retourne un produit par son id.
     */
    public static function getById(int $id): array|false
    {
        return fetchOne('SELECT * FROM produits WHERE id = :id', [':id' => $id]);
    }

    /**
     * Recherche des produits dont le nom contient $q.
     */
    public static function search(string $q): array
    {
        return fetchAll(
            'SELECT * FROM produits WHERE nom LIKE :q ORDER BY categorie, nom',
            [':q' => '%' . $q . '%']
        );
    }

    /**
     * Crée un nouveau produit. Retourne l'id.
     */
    public static function create(array $data): int
    {
        $allowed = ['nom', 'categorie', 'sous_categorie', 'unite_mesure', 'prix_unitaire',
                    'unite_achat', 'saisons', 'tryptophane', 'exclu', 'note'];
        $row = array_intersect_key($data, array_flip($allowed));
        return (int) insert('produits', $row);
    }

    /**
     * Met à jour un produit par son id.
     */
    public static function update(int $id, array $data): int
    {
        $allowed = ['nom', 'categorie', 'sous_categorie', 'unite_mesure', 'prix_unitaire',
                    'unite_achat', 'saisons', 'tryptophane', 'exclu', 'note'];
        $row = array_intersect_key($data, array_flip($allowed));
        $row['updated_at'] = date('Y-m-d H:i:s');
        return update('produits', $row, 'id = :id', [':id' => $id]);
    }

    /**
     * Exclut un produit (exclu=1).
     */
    public static function exclure(int $id): void
    {
        update('produits', ['exclu' => 1, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $id]);
    }

    /**
     * Inclut un produit (exclu=0).
     */
    public static function inclure(int $id): void
    {
        update('produits', ['exclu' => 0, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $id]);
    }

    /**
     * Exclut un produit par nom (auto-création si inexistant). Retourne l'id.
     */
    public static function exclureParNom(string $nom, string $categorie = 'epicerie'): int
    {
        $existing = fetchOne(
            'SELECT id FROM produits WHERE nom = :nom',
            [':nom' => $nom]
        );

        if ($existing) {
            $id = (int) $existing['id'];
            self::exclure($id);
            return $id;
        }

        return self::create([
            'nom'       => $nom,
            'categorie' => $categorie,
            'exclu'     => 1,
        ]);
    }

    /**
     * Retourne la liste distincte des catégories.
     */
    public static function getCategories(): array
    {
        return fetchAll('SELECT DISTINCT categorie FROM produits ORDER BY categorie');
    }
}
