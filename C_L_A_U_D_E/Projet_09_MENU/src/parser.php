<?php
/**
 * MealCoach — MenuParser
 * Parses standardised weekly meal-plan .md files into structured arrays.
 */

if (!defined('VENDOR_PATH')) {
    define('VENDOR_PATH', __DIR__ . '/../vendor');
}

require_once VENDOR_PATH . '/Parsedown.php';

class MenuParser
{
    // -------------------------------------------------------------------------
    // Public entry point
    // -------------------------------------------------------------------------

    /**
     * Parse a full .md meal-plan file content and return structured data.
     *
     * @param  string $content Raw markdown content
     * @return array{
     *   metadata: array,
     *   batch: list<array>,
     *   jours: list<array>,
     *   courses: list<array>,
     *   budget: float|null
     * }
     */
    public function parse(string $content): array
    {
        return [
            'metadata' => $this->parseMetadata($content),
            'batch'    => $this->parseBatch($content),
            'jours'    => $this->parseJours($content),
            'courses'  => $this->parseCourses($content),
            'budget'   => $this->parseBudget($content),
        ];
    }

    // -------------------------------------------------------------------------
    // Private parsers
    // -------------------------------------------------------------------------

    /**
     * Extract header metadata:
     *   # SEMAINE 1 — PRINTEMPS — Du 7 au 13 avril 2026
     * Also tries to grab the budget estimate from the metadata block if present.
     */
    private function parseMetadata(string $content): array
    {
        $meta = [
            'numero'         => null,
            'saison'         => null,
            'dates_raw'      => null,
            'budget_estime'  => null,
        ];

        // Match: # SEMAINE 1 — PRINTEMPS — <dates or text>
        if (preg_match(
            '/^#\s+SEMAINE\s+(\d+)\s*[—–\-]+\s*(.+?)\s*[—–\-]+\s*(.+)$/mi',
            $content,
            $m
        )) {
            $meta['numero']    = (int) $m[1];
            $meta['saison']    = trim($m[2]);
            $meta['dates_raw'] = trim($m[3]);
        }

        // Budget inside a ## Métadonnées block (optional)
        if (preg_match(
            '/budget[^:\n]*:\s*[\d\s,\.–\-]+€/ui',
            $content,
            $bm
        )) {
            $meta['budget_estime'] = $this->extractBudgetValue($bm[0]);
        }

        return $meta;
    }

    /**
     * Parse the BATCH COOKING table.
     * Columns expected: Heure | Action | Équipement | Résultat / Donne quoi
     */
    private function parseBatch(string $content): array
    {
        $batch = [];

        // Isolate the BATCH COOKING section (up to the next ## heading)
        if (!preg_match(
            '/##\s+.*?BATCH\s+COOKING.*?\n(.*?)(?=\n##\s|\Z)/si',
            $content,
            $section
        )) {
            return $batch;
        }

        $sectionText = $section[1];
        $ordre = 0;

        // Each table data row: | heure | action | equipement | resultat |
        // We skip header and separator rows (rows containing only dashes/pipes)
        $lines = explode("\n", $sectionText);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!str_starts_with($line, '|')) {
                continue;
            }
            // Skip separator lines like |---|---|
            if (preg_match('/^\|[\s\-\|]+\|$/', $line)) {
                continue;
            }

            // Split by | and trim cells
            $cells = array_map('trim', explode('|', $line));
            // Remove empty first/last elements caused by leading/trailing |
            $cells = array_values(array_filter($cells, fn($c) => $c !== ''));

            if (count($cells) < 3) {
                continue;
            }

            // Skip header row (first column is "Heure" or "heure")
            if (mb_strtolower($cells[0]) === 'heure') {
                continue;
            }

            $heure      = $cells[0] ?? '';
            $action     = $cells[1] ?? '';
            $equipement = $cells[2] ?? '';
            $resultat   = $cells[3] ?? null;

            // Only rows where first cell looks like a time (e.g. 9h00, 10h05)
            if (!preg_match('/^\d{1,2}h\d{0,2}$/', $heure)) {
                continue;
            }

            $ordre++;
            $batch[] = [
                'ordre'      => $ordre,
                'heure'      => $heure,
                'action'     => $action,
                'equipement' => $equipement,
                'duree'      => $this->estimerDuree($action . ' ' . $equipement),
                'resultat'   => $resultat ?: null,
            ];
        }

        return $batch;
    }

    /**
     * Parse each day block (LUNDI → DIMANCHE) and its meals.
     */
    private function parseJours(string $content): array
    {
        $jourNames = [
            0 => 'LUNDI',
            1 => 'MARDI',
            2 => 'MERCREDI',
            3 => 'JEUDI',
            4 => 'VENDREDI',
            5 => 'SAMEDI',
            6 => 'DIMANCHE',
        ];

        $jours = [];

        foreach ($jourNames as $index => $nom) {
            // Extract the day block: ## LUNDI ... up to next ## or end
            if (!preg_match(
                '/^##\s+' . preg_quote($nom, '/') . '\s*\n(.*?)(?=^##\s|\Z)/msi',
                $content,
                $dayMatch
            )) {
                continue;
            }

            $dayContent = $dayMatch[1];

            $jours[] = [
                'jour'  => $index,
                'nom'   => $nom,
                'repas' => $this->parseMeals($dayContent),
            ];
        }

        return $jours;
    }

    /**
     * Extract the five meal slots from a day block using emoji markers.
     *
     * Emoji → key mapping:
     *   🌅  petit_dej
     *   ☀️  dejeuner
     *   🍎  encas
     *   🌙  diner
     *   🍮  dessert
     */
    private function parseMeals(string $dayContent): array
    {
        $emojiMap = [
            '🌅' => 'petit_dej',
            '☀️' => 'dejeuner',
            '🍎' => 'encas',
            '🌙' => 'diner',
            '🍮' => 'dessert',
        ];

        $meals = [];

        foreach ($emojiMap as $emoji => $key) {
            // Match the meal section: ### <emoji> <titre> ... up to next ### or end
            $escapedEmoji = preg_quote($emoji, '/');
            if (!preg_match(
                '/^###\s+' . $escapedEmoji . '[^\n]*\n(.*?)(?=^###\s|\Z)/msi',
                $dayContent,
                $mealMatch
            )) {
                $meals[$key] = null;
                continue;
            }

            $mealBlock = trim($mealMatch[1]);

            $meals[$key] = [
                'type_repas' => $key,
                'nom_plat'   => $this->extractNomPlat($mealBlock),
                'contenu'    => $mealBlock,
            ];
        }

        return $meals;
    }

    /**
     * Parse the shopping list section.
     * Sub-categories: Protéines, Laitages, Légumes, Fruits, Féculents, Épicerie, Épices
     */
    private function parseCourses(string $content): array
    {
        $courses = [];

        // Isolate LISTE DE COURSES section
        if (!preg_match(
            '/##\s+.*?(?:LISTE\s+DE\s+COURSES|COURSES).*?\n(.*?)(?=\n##\s|\Z)/si',
            $content,
            $section
        )) {
            return $courses;
        }

        $sectionText = $section[1];

        // Split into sub-category blocks (### Heading)
        $blocks = preg_split('/^###\s+/m', $sectionText, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($blocks as $block) {
            $lines = explode("\n", trim($block));
            if (empty($lines)) {
                continue;
            }

            // First line is the category heading (may include budget hint like "Protéines (~15€)")
            $categoryLine = array_shift($lines);
            $categorie    = $this->cleanCategoryName($categoryLine);

            foreach ($lines as $line) {
                $line = trim($line);
                // Only list items
                if (!preg_match('/^[-*]\s+(.+)$/', $line, $lm)) {
                    continue;
                }

                $rawText = trim($lm[1]);
                $parsed  = $this->parseCourseItem($rawText, $categorie);
                if ($parsed) {
                    $courses[] = $parsed;
                }
            }
        }

        return $courses;
    }

    /**
     * Extract the budget total line.
     * Handles: "Budget estimé total : 43 – 48€"  or  "Budget estimé : 48 – 52€"
     */
    private function parseBudget(string $content): ?float
    {
        // Look for a budget summary line (not inside a sub-list)
        if (preg_match(
            '/^(?:##\s+)?💶?\s*Budget\s+estim[ée][^:\n]*:\s*([\d\s,\.–\-]+)€/miu',
            $content,
            $m
        )) {
            return $this->extractBudgetValue($m[1] . '€');
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Item-level helpers
    // -------------------------------------------------------------------------

    /**
     * Parse a single shopping-list item line into structured fields.
     *
     * Handles patterns such as:
     *   "Œufs × 12"
     *   "Œufs × 18 (~3.50€)"
     *   "500g de carottes (~1€)"
     *   "Lentilles vertes (500g)"
     *   "1 tablette chocolat"
     *   "Carottes (1kg)"
     */
    private function parseCourseItem(string $raw, string $categorie): ?array
    {
        if (empty($raw)) {
            return null;
        }

        $nomBrut     = $raw;
        $quantite    = null;
        $unite       = '';
        $prixEstime  = null;

        // Extract price (~3.50€) or (~3€)
        if (preg_match('/\(~?([\d\.,]+)\s*€\)/u', $raw, $pm)) {
            $prixEstime = (float) str_replace(',', '.', $pm[1]);
            $raw        = trim(str_replace($pm[0], '', $raw));
        }

        // Pattern: "Œufs × 18" or "× 18 Œufs"
        if (preg_match('/^(.+?)\s*[×x]\s*(\d+(?:[,\.]\d+)?)\s*(.*)$/u', $raw, $xm)) {
            $nomBrut  = trim($xm[1] . ($xm[3] ? ' ' . $xm[3] : ''));
            $quantite = (float) str_replace(',', '.', $xm[2]);
            $unite    = 'unité';
        }
        // Pattern: "500g de carottes" or "Lentilles vertes (500g)"
        elseif (preg_match('/^(\d+(?:[,\.]\d+)?)\s*(g|kg|cl|ml|l)\s+(?:de\s+)?(.+)$/ui', $raw, $gm)) {
            $quantite = (float) str_replace(',', '.', $gm[1]);
            $unite    = mb_strtolower($gm[2]);
            $nomBrut  = trim($gm[3]);
        }
        elseif (preg_match('/^(.+?)\s+\((\d+(?:[,\.]\d+)?)\s*(g|kg|cl|ml|l)\)$/ui', $raw, $gm2)) {
            $nomBrut  = trim($gm2[1]);
            $quantite = (float) str_replace(',', '.', $gm2[2]);
            $unite    = mb_strtolower($gm2[3]);
        }
        // Pattern: "1 tablette chocolat" or "3 Wasa léger"
        elseif (preg_match('/^(\d+(?:[,\.]\d+)?)\s+(.+)$/u', $raw, $nm)) {
            $quantite = (float) str_replace(',', '.', $nm[1]);
            $unite    = 'unité';
            $nomBrut  = trim($nm[2]);
        }

        // Clean up trailing price hints in nom_brut like "(~15€)"
        $nomBrut = preg_replace('/\s*\(~?[\d\.,]+€\)/u', '', $nomBrut);
        $nomBrut = trim($nomBrut);

        return [
            'nom_brut'        => $nomBrut,
            'quantite'        => $quantite,
            'unite'           => $unite,
            'categorie_rayon' => $categorie,
            'prix_estime'     => $prixEstime,
        ];
    }

    /**
     * Return the first non-empty, non-bullet line of a meal block as the dish name.
     * Bold markdown (**text**) is stripped.
     */
    private function extractNomPlat(string $content): string
    {
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '-') || str_starts_with($line, '*')) {
                continue;
            }
            // Strip bold markers
            $line = preg_replace('/\*\*(.+?)\*\*/u', '$1', $line);
            // Strip italic markers
            $line = preg_replace('/\*(.+?)\*/u', '$1', $line);
            // Strip leading markdown hash symbols
            $line = ltrim($line, '#');
            $line = trim($line);
            if ($line !== '') {
                return $line;
            }
        }

        return '';
    }

    /**
     * Estimate duration in minutes from an action/equipment description.
     * Falls back to 10 minutes.
     */
    private function estimerDuree(string $action): int
    {
        // Explicit "X min" or "X minutes"
        if (preg_match('/(\d+)\s*min/i', $action, $m)) {
            return (int) $m[1];
        }

        // Explicit "Xh" standalone (e.g. "1h30" → 90, "1h" → 60)
        if (preg_match('/(\d+)h(\d{0,2})/i', $action, $m)) {
            $h   = (int) $m[1];
            $min = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0;
            return $h * 60 + $min;
        }

        return 10;
    }

    // -------------------------------------------------------------------------
    // Utility
    // -------------------------------------------------------------------------

    /**
     * Strip emoji and budget hints from a category heading.
     * E.g. "🥦 Légumes — ABONDANTS (~11€)" → "Légumes"
     */
    private function cleanCategoryName(string $heading): string
    {
        // Remove emoji (broad unicode range)
        $heading = preg_replace('/[\x{1F000}-\x{1FFFF}]|[\x{2600}-\x{27BF}]/u', '', $heading);
        // Remove budget hints (~11€)
        $heading = preg_replace('/\(~?[\d\s,\.–\-]+€\)/u', '', $heading);
        // Remove em-dash and trailing adjectives: "— ABONDANTS"
        $heading = preg_replace('/\s*[—–]\s*.*/u', '', $heading);
        return trim($heading);
    }

    /**
     * Extract a float from a budget string like "43 – 48€" or "48 – 52€".
     * Returns the average of min and max, or single value.
     */
    private function extractBudgetValue(string $text): ?float
    {
        // Remove currency symbol and non-numeric noise
        $text = preg_replace('/[€\s]/u', '', $text);

        // Range: "43–48" → average
        if (preg_match('/([\d,\.]+)[–\-]([\d,\.]+)/', $text, $m)) {
            $low  = (float) str_replace(',', '.', $m[1]);
            $high = (float) str_replace(',', '.', $m[2]);
            return round(($low + $high) / 2, 2);
        }

        // Single value
        if (preg_match('/([\d,\.]+)/', $text, $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }

        return null;
    }
}
