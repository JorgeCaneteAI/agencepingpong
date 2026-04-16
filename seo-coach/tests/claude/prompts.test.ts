// @vitest-environment node
import { describe, it, expect } from "vitest";
import { buildSystemPrompt } from "@/lib/claude/prompts";
import type { Project, Audit, Task } from "@prisma/client";

const fakeProject: Project = {
  id: "proj_1",
  url: "https://villaplaisance.fr",
  name: "Villa Plaisance",
  objective: "Réservations de chambres",
  theme: "Chambre d'hôtes",
  geoZone: "Provence",
  initialDiagnostic: "{}",
  score: 42,
  currentLevel: 1,
  createdAt: new Date(),
  updatedAt: new Date(),
};

const fakeAuditWithFailures: Audit = {
  id: "audit_1",
  projectId: "proj_1",
  date: new Date(),
  scoreBreakdown: JSON.stringify({ total: 42, level1: 14, level2: 0, level3: 0, level4: 0, level5: 0 }),
  technicalChecks: JSON.stringify([
    {
      id: "title",
      label: "Le titre de ta page",
      passed: false,
      score: 0,
      maxScore: 3,
      details: "Pas de titre trouvé",
      fix: "Ajoute une balise <title> dans le <head>",
    },
    {
      id: "https",
      label: "Ton site est sécurisé (HTTPS)",
      passed: true,
      score: 3,
      maxScore: 3,
      details: "Le site utilise HTTPS",
    },
  ]),
  contentAnalysis: "{}",
  gsoAnalysis: "{}",
};

describe("buildSystemPrompt", () => {
  it("contient le nom et l'URL du projet", () => {
    const result = buildSystemPrompt(fakeProject, null, []);
    expect(result).toContain("Villa Plaisance");
    expect(result).toContain("https://villaplaisance.fr");
  });

  it("contient l'objectif et la zone géographique", () => {
    const result = buildSystemPrompt(fakeProject, null, []);
    expect(result).toContain("Réservations de chambres");
    expect(result).toContain("Provence");
  });

  it("contient les checks échoués avec leur fix", () => {
    const result = buildSystemPrompt(fakeProject, fakeAuditWithFailures, []);
    expect(result).toContain("Le titre de ta page");
    expect(result).toContain("Ajoute une balise <title>");
  });

  it("n'inclut pas les checks réussis dans les points à améliorer", () => {
    const result = buildSystemPrompt(fakeProject, fakeAuditWithFailures, []);
    const improvementSection = result.split("## Points à améliorer")[1] ?? "";
    expect(improvementSection).not.toContain("Ton site est sécurisé");
  });

  it("contient le contenu de la base de connaissances", () => {
    const result = buildSystemPrompt(fakeProject, null, []);
    // "Principe fondamental" est un titre de section propre à la formation SEO/GSO
    expect(result).toContain("Principe fondamental");
  });

  it("inclut les tâches en attente si présentes", () => {
    const fakeTasks: Task[] = [
      {
        id: "task_1",
        projectId: "proj_1",
        title: "Ajouter un titre à la page d'accueil",
        description: "",
        level: 1,
        impact: "high",
        difficulty: "easy",
        status: "pending",
        completedAt: null,
        createdAt: new Date(),
      },
    ];
    const result = buildSystemPrompt(fakeProject, null, fakeTasks);
    expect(result).toContain("Ajouter un titre à la page d'accueil");
  });
});
