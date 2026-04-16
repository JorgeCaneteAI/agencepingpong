import fs from "fs";
import path from "path";
import type { Project, Audit, Task } from "@prisma/client";

interface CheckResult {
  id: string;
  label: string;
  passed: boolean;
  score: number;
  maxScore: number;
  details: string;
  fix?: string;
}

function parseChecks(raw: string): CheckResult[] {
  try {
    const parsed: unknown = JSON.parse(raw);
    return Array.isArray(parsed) ? (parsed as CheckResult[]) : [];
  } catch {
    return [];
  }
}

export function loadFormation(): string {
  const filePath = path.join(process.cwd(), "lib/knowledge/formation.md");
  return fs.readFileSync(filePath, "utf-8");
}

export function buildSystemPrompt(
  project: Project,
  audit: Audit | null,
  pendingTasks: Task[]
): string {
  const formation = loadFormation();

  const checks = audit ? parseChecks(audit.technicalChecks) : [];
  const failedChecks = checks.filter((c) => !c.passed);

  const projectContext = `## Projet analysé
- Nom : ${project.name}
- URL : ${project.url}
- Objectif : ${project.objective || "Non renseigné"}
- Secteur : ${project.theme || "Non renseigné"}
- Zone géographique : ${project.geoZone || "Non renseignée"}
- Score SEO actuel : ${project.score}/100
- Niveau actuel : ${project.currentLevel}/5`;

  const auditContext =
    failedChecks.length > 0
      ? `\n\n## Points à améliorer (dernier audit)\n${failedChecks
          .map(
            (c) =>
              `- ${c.label} : ${c.details}${c.fix ? ` → ${c.fix}` : ""}`
          )
          .join("\n")}`
      : "";

  const tasksContext =
    pendingTasks.length > 0
      ? `\n\n## Plan d'action actuel (${pendingTasks.length} action${pendingTasks.length !== 1 ? "s" : ""} en attente)\n${pendingTasks
          .slice(0, 5)
          .map((t) => `- ${t.title}`)
          .join("\n")}${pendingTasks.length > 5 ? `\n...et ${pendingTasks.length - 5} autre${pendingTasks.length - 5 !== 1 ? "s" : ""} action${pendingTasks.length - 5 !== 1 ? "s" : ""}.` : ""}`
      : "";

  return `Tu es un coach SEO expert et pédagogue. Tu aides des propriétaires de sites web non-techniciens à améliorer leur référencement sur Google et leur visibilité sur les IA (GSO).

Tu parles toujours en français simple et accessible, en évitant le jargon technique. Quand tu dois utiliser un terme technique, tu l'expliques immédiatement entre parenthèses. Tu es encourageant, bienveillant et concis.

${projectContext}${auditContext}${tasksContext}

## Base de connaissances SEO/GSO
${formation}`;
}
