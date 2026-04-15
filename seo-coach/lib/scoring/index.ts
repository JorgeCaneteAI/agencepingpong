import type { CheckResult, ScoreBreakdown } from "./types";

export function calculateScore(checks: CheckResult[]): ScoreBreakdown {
  const breakdown: ScoreBreakdown = {
    total: 0,
    level1: 0,
    level2: 0,
    level3: 0,
    level4: 0,
    level5: 0,
  };

  for (const check of checks) {
    const key = `level${check.level}` as keyof Omit<ScoreBreakdown, "total">;
    if (key in breakdown) {
      breakdown[key] += check.score;
    }
  }

  // Cap each level at 20
  breakdown.level1 = Math.min(breakdown.level1, 20);
  breakdown.level2 = Math.min(breakdown.level2, 20);
  breakdown.level3 = Math.min(breakdown.level3, 20);
  breakdown.level4 = Math.min(breakdown.level4, 20);
  breakdown.level5 = Math.min(breakdown.level5, 20);

  breakdown.total =
    breakdown.level1 +
    breakdown.level2 +
    breakdown.level3 +
    breakdown.level4 +
    breakdown.level5;

  return breakdown;
}

export type { ScoreBreakdown, CheckResult } from "./types";
