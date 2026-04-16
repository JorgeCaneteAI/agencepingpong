export interface ScoreBreakdown {
  total: number; // 0-100
  level1: number; // 0-20 — Les fondations
  level2: number; // 0-20 — Les mots-clés
  level3: number; // 0-20 — Le contenu
  level4: number; // 0-20 — L'autorité
  level5: number; // 0-20 — GSO
}

export interface CheckResult {
  id: string;
  label: string; // Libellé en français simple
  level: number; // 1-5
  passed: boolean;
  score: number; // Points attribués
  maxScore: number; // Points max
  details: string; // Explication du résultat
  fix?: string; // Instruction pour corriger (si échoué)
}
