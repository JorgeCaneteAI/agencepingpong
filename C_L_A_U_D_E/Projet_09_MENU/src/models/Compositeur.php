<?php
/**
 * MealCoach — Model Compositeur
 */

require_once __DIR__ . '/../db.php';

class Compositeur
{
    /**
     * Détermine le moment nutritionnel à partir du type de repas.
     */
    private static function momentPourRepas(string $typeRepas): string
    {
        $petitDej = ['petit_dejeuner', 'petit_dej', 'petitdej', 'matin'];
        return in_array(strtolower($typeRepas), $petitDej, true) ? 'petit_dej' : 'repas';
    }

    /**
     * Retourne les équivalences pour un type de repas, groupées par catégorie.
     */
    public static function getEquivalences(string $typeRepas): array
    {
        $moment = self::momentPourRepas($typeRepas);

        $rows = fetchAll(
            "SELECT * FROM equivalences
             WHERE moment = :moment OR moment = 'tous'
             ORDER BY categorie, id",
            [':moment' => $moment]
        );

        $grouped = [];
        foreach ($rows as $row) {
            $cat = $row['categorie'] ?? 'autres';
            $grouped[$cat][] = $row;
        }

        return $grouped;
    }

    /**
     * Retourne les règles nutritionnelles pour un type de repas.
     */
    public static function getRegles(string $typeRepas): array
    {
        return fetchAll(
            'SELECT * FROM regles WHERE type_repas = :tr ORDER BY categorie',
            [':tr' => $typeRepas]
        );
    }

    /**
     * Valide des sélections par rapport aux règles du type de repas.
     * $selections : ['categorie' => count]
     * Retourne ['valid' => bool, 'errors' => [['categorie' => ..., 'message' => ..., 'type' => ...]]]
     */
    public static function valider(string $typeRepas, array $selections): array
    {
        $regles = self::getRegles($typeRepas);
        $errors = [];

        foreach ($regles as $regle) {
            $cat   = $regle['categorie'];
            $count = (int) ($selections[$cat] ?? 0);
            $min   = (int) ($regle['quantite_min'] ?? 0);
            $max   = $regle['quantite_max'] !== null ? (int) $regle['quantite_max'] : null;

            if ($count < $min) {
                $errors[] = [
                    'categorie' => $cat,
                    'message'   => "Minimum $min élément(s) requis pour « $cat » (vous en avez $count).",
                    'type'      => 'missing',
                ];
            } elseif ($max !== null && $count > $max) {
                $errors[] = [
                    'categorie' => $cat,
                    'message'   => "Maximum $max élément(s) autorisé(s) pour « $cat » (vous en avez $count).",
                    'type'      => 'excess',
                ];
            }
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Valide et sauvegarde un repas composé.
     * $items : [['produit_id' => ..., 'equivalence_id' => ..., 'quantite' => ..., 'unite' => ..., 'categorie' => ...], ...]
     * Retourne l'id du repas composé.
     */
    public static function sauvegarder(string $typeRepas, array $items, ?string $nom, bool $favori): int
    {
        // Compter les catégories pour validation
        $selections = [];
        foreach ($items as $item) {
            $cat = $item['categorie'] ?? 'autres';
            $selections[$cat] = ($selections[$cat] ?? 0) + 1;
        }

        $validation = self::valider($typeRepas, $selections);

        $repasId = (int) insert('repas_composes', [
            'nom'       => $nom,
            'type_repas'=> $typeRepas,
            'date'      => date('Y-m-d'),
            'favori'    => $favori ? 1 : 0,
            'valide'    => $validation['valid'] ? 1 : 0,
        ]);

        foreach ($items as $item) {
            insert('repas_compose_items', [
                'repas_id'      => $repasId,
                'equivalence_id'=> $item['equivalence_id'] ?? null,
                'produit_id'    => $item['produit_id']     ?? null,
                'quantite'      => $item['quantite']       ?? null,
                'unite'         => $item['unite']          ?? null,
                'categorie'     => $item['categorie']      ?? null,
            ]);
        }

        return $repasId;
    }

    /**
     * Retourne les repas composés favoris.
     */
    public static function getFavoris(): array
    {
        return fetchAll(
            'SELECT * FROM repas_composes WHERE favori = 1 ORDER BY created_at DESC'
        );
    }
}
