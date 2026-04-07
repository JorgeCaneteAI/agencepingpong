<?php
/**
 * MealCoach — Model Suivi
 */

require_once __DIR__ . '/../db.php';

class Suivi
{
    /**
     * Retourne le suivi d'un jour par sa date.
     */
    public static function getJour(string $date): array|false
    {
        return fetchOne(
            'SELECT * FROM suivi_jours WHERE date = :date',
            [':date' => $date]
        );
    }

    /**
     * Retourne le suivi d'un jour, le crée si inexistant.
     */
    public static function getOuCreerJour(string $date): array
    {
        $row = self::getJour($date);
        if ($row) {
            return $row;
        }

        $id = (int) insert('suivi_jours', ['date' => $date]);
        return self::getJour($date) ?: ['id' => $id, 'date' => $date];
    }

    /**
     * Met à jour les données d'un jour (crée si inexistant).
     */
    public static function majJour(string $date, array $data): array
    {
        $row = self::getOuCreerJour($date);

        $allowed = ['poids', 'humeur', 'energie', 'sommeil', 'note'];
        $update  = array_intersect_key($data, array_flip($allowed));

        if (!empty($update)) {
            update('suivi_jours', $update, 'id = :id', [':id' => $row['id']]);
        }

        return self::getJour($date) ?: $row;
    }

    /**
     * Retourne les repas suivis pour un jour donné (par suivi_jour_id).
     */
    public static function getRepas(int $suiviJourId): array
    {
        return fetchAll(
            'SELECT * FROM suivi_repas WHERE suivi_jour_id = :sjid ORDER BY type_repas',
            [':sjid' => $suiviJourId]
        );
    }

    /**
     * Upsert d'un repas suivi (INSERT OR REPLACE).
     * statut : 'prevu' | 'pris' | 'saute' | 'craquage' | 'modifie'
     */
    public static function majRepas(int $suiviJourId, string $typeRepas, string $statut, ?string $detail): void
    {
        $existing = fetchOne(
            'SELECT id FROM suivi_repas WHERE suivi_jour_id = :sjid AND type_repas = :tr',
            [':sjid' => $suiviJourId, ':tr' => $typeRepas]
        );

        $data = [
            'suivi_jour_id' => $suiviJourId,
            'type_repas'    => $typeRepas,
            'statut'        => $statut,
            'heure'         => date('H:i'),
        ];

        if ($statut === 'craquage') {
            $data['craquage_detail'] = $detail;
        } elseif ($statut === 'modifie') {
            $data['modification'] = $detail;
        }

        if ($existing) {
            update('suivi_repas', $data, 'id = :id', [':id' => $existing['id']]);
        } else {
            insert('suivi_repas', $data);
        }
    }

    /**
     * Retourne l'historique des N derniers jours de suivi.
     */
    public static function getHistorique(int $jours = 30): array
    {
        return fetchAll(
            "SELECT * FROM suivi_jours
             WHERE date >= date('now', '-' || :j || ' days')
             ORDER BY date DESC",
            [':j' => $jours]
        );
    }

    /**
     * Retourne les stats des repas entre deux dates, groupées par statut.
     */
    public static function statsRepas(string $dateDebut, string $dateFin): array
    {
        return fetchAll(
            'SELECT sr.statut, COUNT(*) AS nombre
             FROM suivi_repas sr
             JOIN suivi_jours sj ON sj.id = sr.suivi_jour_id
             WHERE sj.date BETWEEN :debut AND :fin
             GROUP BY sr.statut
             ORDER BY nombre DESC',
            [':debut' => $dateDebut, ':fin' => $dateFin]
        );
    }
}
