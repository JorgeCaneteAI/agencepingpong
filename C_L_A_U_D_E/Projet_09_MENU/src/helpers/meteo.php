<?php
/**
 * MealCoach — Helper Météo
 * Utilise Open-Meteo (gratuit, sans clé API)
 * Cache les résultats en BDD (table settings) pour éviter les appels répétés
 *
 * Localisation par défaut : Nîmes (43.83, 4.36)
 */

require_once __DIR__ . '/../db.php';

// ── Configuration ───────────────────────────────────────────────
define('METEO_LAT', 43.83);
define('METEO_LON', 4.36);
define('METEO_CACHE_KEY', 'meteo_cache');
define('METEO_CACHE_TTL', 6 * 3600); // 6 heures

// ── Seuils de température ───────────────────────────────────────
define('SEUIL_CHAUD', 26);  // Au-dessus → plats froids recommandés
define('SEUIL_FROID', 10);  // En-dessous → plats chauds recommandés

// ── Mots-clés plats chauds (inadaptés quand il fait chaud) ─────
define('PLATS_CHAUDS', [
    'soupe', 'veloute', 'potage', 'bouillon', 'consomme',
    'gratin', 'gratine', 'raclette', 'fondue', 'tartiflette',
    'pot-au-feu', 'potee', 'blanquette', 'bourguignon', 'cassoulet',
    'daube', 'ragout', 'cocotte', 'braise', 'mijote',
    'choucroute', 'hachis parmentier', 'parmentier',
    'chou farci', 'garbure', 'aligot',
    'chocolat chaud', 'tisane chaude', 'the chaud',
]);

// ── Mots-clés plats froids (inadaptés quand il fait froid) ─────
define('PLATS_FROIDS', [
    'gaspacho', 'salade composee', 'salade nicoise', 'salade cesar',
    'carpaccio', 'ceviche', 'tartare',
    'melon', 'pasteque', 'glace', 'sorbet', 'granita',
    'smoothie glace', 'jus glace',
    'taboulé', 'taboule',
]);

// ── Suggestions de remplacement par saison ──────────────────────
define('SUGGESTIONS_ETE', [
    'petit_dej' => ['Smoothie bowl fruits rouges', 'Yaourt grec + granola + fruits frais', 'Tartine avocat-tomate'],
    'dejeuner'  => ['Salade nicoise', 'Bowl quinoa-legumes croquants', 'Wrap poulet-crudites', 'Taboule maison'],
    'encas'     => ['Fruits frais de saison', 'Gaspacho froid', 'Smoothie melon-menthe'],
    'diner'     => ['Salade tiede chevre-miel', 'Poisson grille + ratatouille', 'Carpaccio courgettes-parmesan'],
    'soiree'    => ['Infusion glacee', 'Compote froide + amandes', 'Yaourt + miel'],
]);

define('SUGGESTIONS_HIVER', [
    'petit_dej' => ['Porridge chaud pomme-cannelle', 'Pain perdu + fruits poeles', 'Oeufs brouilles + tartines'],
    'dejeuner'  => ['Soupe de legumes + tartine', 'Gratin de legumes', 'Blanquette de poulet', 'Lentilles mijotees'],
    'encas'     => ['Chocolat chaud maison', 'Pomme au four', 'Tisane + 2 carres chocolat noir'],
    'diner'     => ['Veloute potimarron', 'Pot-au-feu leger', 'Quiche poireaux + salade'],
    'soiree'    => ['Tisane camomille', 'Lait chaud miel-vanille', 'Compote tiede'],
]);

/**
 * Récupère les prévisions météo pour les 7 prochains jours.
 * Retourne un tableau indexé par date 'Y-m-d' => ['temp_max' => float, 'temp_min' => float, 'icon' => string]
 * Utilise le cache si disponible et frais.
 */
function getMeteoSemaine(): array
{
    // Vérifier le cache
    $cached = getSetting(METEO_CACHE_KEY);
    if ($cached) {
        $data = json_decode($cached, true);
        if ($data && isset($data['_ts']) && (time() - $data['_ts']) < METEO_CACHE_TTL) {
            unset($data['_ts']);
            return $data;
        }
    }

    // Appel API Open-Meteo
    $url = sprintf(
        'https://api.open-meteo.com/v1/forecast?latitude=%s&longitude=%s'
        . '&daily=temperature_2m_max,temperature_2m_min,weathercode'
        . '&timezone=Europe%%2FParis&forecast_days=7',
        METEO_LAT,
        METEO_LON
    );

    $context = stream_context_create([
        'http' => ['timeout' => 5, 'ignore_errors' => true],
        'ssl'  => ['verify_peer' => true],
    ]);

    $json = @file_get_contents($url, false, $context);
    if (!$json) {
        return [];
    }

    $api = json_decode($json, true);
    if (!$api || !isset($api['daily']['time'])) {
        return [];
    }

    // Transformer en tableau lisible
    $result = [];
    $dates    = $api['daily']['time'];
    $maxTemps = $api['daily']['temperature_2m_max'];
    $minTemps = $api['daily']['temperature_2m_min'];
    $codes    = $api['daily']['weathercode'];

    foreach ($dates as $i => $date) {
        $result[$date] = [
            'temp_max' => round($maxTemps[$i], 1),
            'temp_min' => round($minTemps[$i], 1),
            'icon'     => weatherCodeToEmoji($codes[$i] ?? 0),
            'label'    => weatherCodeToLabel($codes[$i] ?? 0),
        ];
    }

    // Sauvegarder en cache
    $toCache = $result;
    $toCache['_ts'] = time();
    setSetting(METEO_CACHE_KEY, json_encode($toCache));

    return $result;
}

/**
 * Récupère la météo d'un jour spécifique.
 */
function getMeteoJour(string $date): ?array
{
    $semaine = getMeteoSemaine();
    return $semaine[$date] ?? null;
}

/**
 * Vérifie la cohérence météo/plat pour un jour donné.
 * Retourne un tableau d'alertes [{plat, type_repas, raison, temp, suggestions}]
 */
function verifierCoherenceMeteo(array $repas, string $date): array
{
    $meteo = getMeteoJour($date);
    if (!$meteo) return [];

    $alertes = [];
    $tempMax = $meteo['temp_max'];

    foreach ($repas as $r) {
        $nomPlat    = mb_strtolower($r['nom_plat'] ?? '');
        $contenu    = mb_strtolower($r['contenu'] ?? '');
        $typeRepas  = $r['type_repas'] ?? '';
        $texteTotal = $nomPlat . ' ' . $contenu;

        // Trop chaud pour un plat chaud ?
        if ($tempMax >= SEUIL_CHAUD) {
            foreach (PLATS_CHAUDS as $motCle) {
                if (str_contains($texteTotal, $motCle)) {
                    $suggestions = SUGGESTIONS_ETE[$typeRepas] ?? SUGGESTIONS_ETE['dejeuner'];
                    $alertes[] = [
                        'plat'        => $r['nom_plat'],
                        'type_repas'  => $typeRepas,
                        'raison'      => 'chaud',
                        'temp'        => $tempMax,
                        'icon'        => $meteo['icon'],
                        'mot_cle'     => $motCle,
                        'suggestions' => $suggestions,
                    ];
                    break; // Une alerte par plat suffit
                }
            }
        }

        // Trop froid pour un plat froid ?
        if ($tempMax <= SEUIL_FROID) {
            foreach (PLATS_FROIDS as $motCle) {
                if (str_contains($texteTotal, $motCle)) {
                    $suggestions = SUGGESTIONS_HIVER[$typeRepas] ?? SUGGESTIONS_HIVER['dejeuner'];
                    $alertes[] = [
                        'plat'        => $r['nom_plat'],
                        'type_repas'  => $typeRepas,
                        'raison'      => 'froid',
                        'temp'        => $tempMax,
                        'icon'        => $meteo['icon'],
                        'mot_cle'     => $motCle,
                        'suggestions' => $suggestions,
                    ];
                    break;
                }
            }
        }
    }

    return $alertes;
}

/**
 * Convertit un code météo WMO en emoji.
 */
function weatherCodeToEmoji(int $code): string
{
    return match (true) {
        $code === 0                      => '☀️',
        $code <= 3                       => '⛅',
        in_array($code, [45, 48])        => '🌫️',
        in_array($code, [51, 53, 55])    => '🌦️',
        in_array($code, [61, 63, 65])    => '🌧️',
        in_array($code, [66, 67])        => '🌨️',
        in_array($code, [71, 73, 75, 77])=> '❄️',
        in_array($code, [80, 81, 82])    => '🌧️',
        in_array($code, [85, 86])        => '❄️',
        in_array($code, [95, 96, 99])    => '⛈️',
        default                          => '🌤️',
    };
}

/**
 * Convertit un code météo WMO en label FR.
 */
function weatherCodeToLabel(int $code): string
{
    return match (true) {
        $code === 0                      => 'Ensoleille',
        $code <= 3                       => 'Partiellement nuageux',
        in_array($code, [45, 48])        => 'Brouillard',
        in_array($code, [51, 53, 55])    => 'Bruine',
        in_array($code, [61, 63, 65])    => 'Pluie',
        in_array($code, [66, 67])        => 'Pluie verglacante',
        in_array($code, [71, 73, 75, 77])=> 'Neige',
        in_array($code, [80, 81, 82])    => 'Averses',
        in_array($code, [85, 86])        => 'Averses de neige',
        in_array($code, [95, 96, 99])    => 'Orage',
        default                          => 'Variable',
    };
}
