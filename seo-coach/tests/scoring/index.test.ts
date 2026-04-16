import { describe, it, expect } from "vitest";
import { calculateScore } from "@/lib/scoring";
import type { CheckResult } from "@/lib/scoring/types";

describe("calculateScore", () => {
  it("should calculate total score from checks", () => {
    const checks: CheckResult[] = [
      { id: "a", label: "A", level: 1, passed: true, score: 3, maxScore: 3, details: "" },
      { id: "b", label: "B", level: 1, passed: true, score: 2, maxScore: 2, details: "" },
      { id: "c", label: "C", level: 1, passed: false, score: 0, maxScore: 3, details: "" },
    ];

    const breakdown = calculateScore(checks);
    expect(breakdown.level1).toBe(5);
    expect(breakdown.total).toBe(5);
  });

  it("should distribute scores across levels", () => {
    const checks: CheckResult[] = [
      { id: "a", label: "A", level: 1, passed: true, score: 15, maxScore: 20, details: "" },
      { id: "b", label: "B", level: 2, passed: true, score: 10, maxScore: 20, details: "" },
      { id: "c", label: "C", level: 3, passed: true, score: 5, maxScore: 20, details: "" },
    ];

    const breakdown = calculateScore(checks);
    expect(breakdown.level1).toBe(15);
    expect(breakdown.level2).toBe(10);
    expect(breakdown.level3).toBe(5);
    expect(breakdown.total).toBe(30);
  });

  it("should cap each level at 20", () => {
    const checks: CheckResult[] = [
      { id: "a", label: "A", level: 1, passed: true, score: 25, maxScore: 25, details: "" },
    ];

    const breakdown = calculateScore(checks);
    expect(breakdown.level1).toBe(20);
    expect(breakdown.total).toBe(20);
  });
});
