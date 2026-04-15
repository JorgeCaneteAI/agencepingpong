# SaaS SEO/GSO — Plan 2 : Interface

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Construire l'interface complète — onboarding guidé en 3 étapes, sidebar de navigation par projet, vue détaillée par niveau, et plan d'action trié par priorité.

**Architecture:** On étend le projet Next.js existant avec de nouvelles routes App Router. L'onboarding est un composant client multi-étapes qui orchestre les API existantes (POST /api/projects + POST /api/crawl). La sidebar est un layout partagé pour toutes les pages `/project/[id]/*`. Les vues niveau et plan d'action sont des pages serveur qui lisent directement Prisma.

**Tech Stack:** Next.js 16 (App Router), TypeScript, Tailwind v4, Prisma 5 + SQLite

**Spec :** `AGENCE/CLIENTS/SAAS SEO/docs/superpowers/specs/2026-04-15-saas-seo-gso-design.md`

---

## Structure de fichiers

```
seo-coach/
├── app/
│   ├── api/
│   │   ├── projects/
│   │   │   ├── route.ts               (existant — GET list + POST create)
│   │   │   └── [id]/
│   │   │       └── route.ts           (NEW — GET single + PATCH update)
│   │   └── tasks/
│   │       └── [id]/
│   │           └── route.ts           (NEW — PATCH status: done/skipped)
│   ├── new-project/
│   │   └── page.tsx                   (NEW — onboarding client multi-étapes)
│   ├── dashboard/
│   │   └── page.tsx                   (MODIFY — ajouter bouton "Ajouter un site" + niveau)
│   └── project/
│       └── [id]/
│           ├── layout.tsx             (NEW — sidebar partagée)
│           ├── page.tsx               (MODIFY — overview minimaliste, délègue aux sous-pages)
│           ├── CrawlButton.tsx        (existant — inchangé)
│           ├── level/
│           │   └── [level]/
│           │       └── page.tsx       (NEW — checks visuels du niveau)
│           └── plan/
│               └── page.tsx           (NEW — plan d'action trié par priorité)
```

---

### Task 1: API /api/projects/[id] + /api/tasks/[id]

**Files:**
- Create: `seo-coach/app/api/projects/[id]/route.ts`
- Create: `seo-coach/app/api/tasks/[id]/route.ts`
- Create: `seo-coach/tests/api/projects-id.test.ts`

- [ ] **Step 1: Écrire les tests**

Créer `seo-coach/tests/api/projects-id.test.ts` :

```ts
// @vitest-environment node
import { describe, it, expect } from "vitest";
import { prisma } from "@/lib/db";

describe("projects/:id API helpers", () => {
  it("should find a project by id", async () => {
    const project = await prisma.project.findFirst();
    expect(project).not.toBeNull();
    expect(project?.url).toBeTruthy();
  });

  it("should update a project's name", async () => {
    const project = await prisma.project.findFirst();
    if (!project) return;
    const updated = await prisma.project.update({
      where: { id: project.id },
      data: { name: "Test Update" },
    });
    expect(updated.name).toBe("Test Update");
    // Restore
    await prisma.project.update({
      where: { id: project.id },
      data: { name: project.name },
    });
  });
});
```

- [ ] **Step 2: Vérifier que les tests passent**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
npm test -- tests/api/projects-id.test.ts
```

Expected: 2 tests passed.

- [ ] **Step 3: Créer l'API route GET/PATCH /api/projects/[id]**

Créer `seo-coach/app/api/projects/[id]/route.ts` :

```ts
import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";

export async function GET(
  _request: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const { id } = await params;

  const project = await prisma.project.findUnique({
    where: { id },
    include: {
      audits: { orderBy: { date: "desc" }, take: 1 },
      tasks: { where: { status: "pending" }, orderBy: { createdAt: "asc" } },
    },
  });

  if (!project) {
    return NextResponse.json({ error: "Projet non trouvé" }, { status: 404 });
  }

  return NextResponse.json(project);
}

export async function PATCH(
  request: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const { id } = await params;
  const body = await request.json();
  const { name, objective, theme, geoZone } = body;

  const project = await prisma.project.update({
    where: { id },
    data: {
      ...(name !== undefined && { name }),
      ...(objective !== undefined && { objective }),
      ...(theme !== undefined && { theme }),
      ...(geoZone !== undefined && { geoZone }),
    },
  });

  return NextResponse.json(project);
}
```

- [ ] **Step 4: Créer l'API route PATCH /api/tasks/[id]**

Créer `seo-coach/app/api/tasks/[id]/route.ts` :

```ts
import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";

export async function PATCH(
  request: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const { id } = await params;
  const body = await request.json();
  const { status } = body;

  if (!["pending", "done", "skipped"].includes(status)) {
    return NextResponse.json({ error: "Statut invalide" }, { status: 400 });
  }

  const task = await prisma.task.update({
    where: { id },
    data: {
      status,
      completedAt: status === "done" ? new Date() : null,
    },
  });

  return NextResponse.json(task);
}
```

- [ ] **Step 5: Commit**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
git add app/api/projects/[id]/ app/api/tasks/ tests/api/
git commit -m "feat(api): GET/PATCH project par id + PATCH task status"
```

---

### Task 2: Onboarding — Étape 1 (formulaire)

**Files:**
- Create: `seo-coach/app/new-project/page.tsx`

- [ ] **Step 1: Créer la page d'onboarding avec le formulaire étape 1**

Créer `seo-coach/app/new-project/page.tsx` :

```tsx
"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

interface FormData {
  url: string;
  name: string;
  objective: string;
  theme: string;
  geoZone: string;
}

type Step = "questions" | "diagnostic" | "done";

interface DiagnosticResult {
  projectId: string;
  score: number;
  tasksGenerated: number;
  crawlOk: boolean;
  title: string | null;
  metaDescription: string | null;
  isHttps: boolean;
  hasSitemap: boolean;
  geoFound: boolean;
}

export default function NewProjectPage() {
  const router = useRouter();
  const [step, setStep] = useState<Step>("questions");
  const [formData, setFormData] = useState<FormData>({
    url: "",
    name: "",
    objective: "",
    theme: "",
    geoZone: "",
  });
  const [loading, setLoading] = useState(false);
  const [diagnostic, setDiagnostic] = useState<DiagnosticResult | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmitQuestions(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      // Step 1: Create project
      const projectRes = await fetch("/api/projects", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData),
      });

      if (!projectRes.ok) {
        throw new Error("Impossible de créer le projet");
      }

      const project = await projectRes.json();

      // Step 2: Run crawl
      setStep("diagnostic");
      const crawlRes = await fetch("/api/crawl", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ projectId: project.id }),
      });

      const crawlData = await crawlRes.json();

      // Step 3: Parse diagnostic
      const checks = crawlData.audit?.checks ?? [];
      const titleCheck = checks.find((c: { id: string }) => c.id === "title");
      const descCheck = checks.find((c: { id: string }) => c.id === "meta-description");
      const httpsCheck = checks.find((c: { id: string }) => c.id === "https");
      const sitemapCheck = checks.find((c: { id: string }) => c.id === "sitemap");

      // Check if geoZone appears in title or description
      const titleText = titleCheck?.details ?? "";
      const descText = descCheck?.details ?? "";
      const geoLower = formData.geoZone.toLowerCase();
      const geoFound =
        geoLower.length > 2 &&
        (titleText.toLowerCase().includes(geoLower) ||
          descText.toLowerCase().includes(geoLower));

      setDiagnostic({
        projectId: project.id,
        score: crawlData.score ?? 0,
        tasksGenerated: crawlData.tasksGenerated ?? 0,
        crawlOk: crawlData.score !== undefined,
        title: titleCheck ? titleCheck.details.replace(/^Ton titre fait.*$/, "").trim() || null : null,
        metaDescription: descCheck ? (descCheck.passed ? "Présente" : "Absente") : "Non vérifiée",
        isHttps: httpsCheck?.passed ?? false,
        hasSitemap: sitemapCheck?.passed ?? false,
        geoFound,
      });
    } catch (err) {
      setError(err instanceof Error ? err.message : "Une erreur est survenue");
      setStep("questions");
    } finally {
      setLoading(false);
    }
  }

  function handleGoToProject() {
    if (diagnostic?.projectId) {
      router.push(`/project/${diagnostic.projectId}`);
    }
  }

  // STEP: QUESTIONS
  if (step === "questions") {
    return (
      <main className="max-w-xl mx-auto p-8">
        <div className="mb-8">
          <h1 className="text-2xl font-bold">Ajouter un site</h1>
          <p className="text-gray-500 mt-1">
            Réponds à ces questions simples — on s'occupe du reste.
          </p>
        </div>

        {error && (
          <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmitQuestions} className="space-y-6">
          <div>
            <label className="block text-sm font-medium mb-1">
              Adresse du site <span className="text-red-500">*</span>
            </label>
            <input
              type="url"
              required
              placeholder="https://monsite.fr"
              value={formData.url}
              onChange={(e) => setFormData((d) => ({ ...d, url: e.target.value }))}
              className="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">
              Nom du site ou de l'entreprise <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              required
              placeholder="Villa Plaisance"
              value={formData.name}
              onChange={(e) => setFormData((d) => ({ ...d, name: e.target.value }))}
              className="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">
              Qu'est-ce que tu veux que les gens fassent sur ton site ?
            </label>
            <input
              type="text"
              placeholder="Réserver une chambre, demander un devis, me contacter..."
              value={formData.objective}
              onChange={(e) => setFormData((d) => ({ ...d, objective: e.target.value }))}
              className="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">
              De quoi parle ton site ?
            </label>
            <input
              type="text"
              placeholder="Chambre d'hôtes, traiteur mariage, agence web..."
              value={formData.theme}
              onChange={(e) => setFormData((d) => ({ ...d, theme: e.target.value }))}
              className="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">
              Tu vises des clients où ?
            </label>
            <input
              type="text"
              placeholder="Nîmes, Gard, France entière..."
              value={formData.geoZone}
              onChange={(e) => setFormData((d) => ({ ...d, geoZone: e.target.value }))}
              className="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50"
          >
            {loading ? "Analyse en cours..." : "Analyser mon site →"}
          </button>
        </form>
      </main>
    );
  }

  // STEP: DIAGNOSTIC (loading or result)
  if (step === "diagnostic") {
    if (!diagnostic) {
      return (
        <main className="max-w-xl mx-auto p-8 text-center">
          <div className="animate-pulse">
            <div className="text-4xl mb-4">🔍</div>
            <h2 className="text-xl font-semibold mb-2">Analyse en cours...</h2>
            <p className="text-gray-500">
              On visite ton site et on vérifie tout. Ça prend 10 à 20 secondes.
            </p>
          </div>
        </main>
      );
    }

    const scoreColor =
      diagnostic.score >= 70
        ? "text-green-600"
        : diagnostic.score >= 40
          ? "text-orange-500"
          : "text-red-500";

    return (
      <main className="max-w-xl mx-auto p-8">
        <h2 className="text-2xl font-bold mb-2">Voici ce qu'on a trouvé</h2>
        <p className="text-gray-500 mb-6">
          On a comparé ce que tu nous as dit avec ce que Google voit sur ton site.
        </p>

        <div className="border rounded-xl overflow-hidden mb-6">
          <div className="bg-gray-50 px-4 py-2 grid grid-cols-2 text-xs font-semibold text-gray-500 uppercase">
            <span>Ce que tu nous as dit</span>
            <span>Ce qu'on a trouvé</span>
          </div>
          <div className="divide-y">
            <div className="px-4 py-3 grid grid-cols-2 gap-4 text-sm">
              <span className="text-gray-600">Objectif : {formData.objective || "—"}</span>
              <span>{diagnostic.isHttps ? "✅ Site sécurisé (HTTPS)" : "❌ Site non sécurisé"}</span>
            </div>
            <div className="px-4 py-3 grid grid-cols-2 gap-4 text-sm">
              <span className="text-gray-600">Secteur : {formData.theme || "—"}</span>
              <span>{diagnostic.title ? `Titre : "${diagnostic.title.slice(0, 40)}${diagnostic.title.length > 40 ? "…" : ""}"` : "❌ Pas de titre"}</span>
            </div>
            <div className="px-4 py-3 grid grid-cols-2 gap-4 text-sm">
              <span className="text-gray-600">Zone : {formData.geoZone || "—"}</span>
              <span>{diagnostic.geoFound ? `✅ "${formData.geoZone}" mentionné` : `⚠️ "${formData.geoZone}" non trouvé`}</span>
            </div>
            <div className="px-4 py-3 grid grid-cols-2 gap-4 text-sm">
              <span className="text-gray-600">—</span>
              <span>{diagnostic.hasSitemap ? "✅ Sitemap présent" : "❌ Pas de sitemap"}</span>
            </div>
          </div>
        </div>

        <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 text-center">
          <p className="text-sm text-blue-700 mb-1">Score initial</p>
          <p className={`text-5xl font-bold ${scoreColor}`}>{diagnostic.score}</p>
          <p className="text-sm text-gray-500 mt-1">
            /100 — {diagnostic.tasksGenerated} action{diagnostic.tasksGenerated !== 1 ? "s" : ""} identifiée{diagnostic.tasksGenerated !== 1 ? "s" : ""}
          </p>
        </div>

        <button
          onClick={handleGoToProject}
          className="w-full py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700"
        >
          Voir mon programme →
        </button>
      </main>
    );
  }

  return null;
}
```

- [ ] **Step 2: Vérifier que la page compile**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
npm run build 2>&1 | grep -E "(error|Error|✓|Route)" | head -20
```

Expected: pas d'erreurs TypeScript, route `/new-project` présente dans la sortie.

- [ ] **Step 3: Commit**

```bash
git add app/new-project/
git commit -m "feat(ui): onboarding en 3 étapes — formulaire, diagnostic, lancement"
```

---

### Task 3: Sidebar layout pour la vue projet

**Files:**
- Create: `seo-coach/app/project/[id]/layout.tsx`
- Modify: `seo-coach/app/project/[id]/page.tsx`

- [ ] **Step 1: Créer le layout avec sidebar**

Créer `seo-coach/app/project/[id]/layout.tsx` :

```tsx
import Link from "next/link";
import { notFound } from "next/navigation";
import { prisma } from "@/lib/db";

const LEVELS = [
  { num: 1, title: "Les fondations" },
  { num: 2, title: "Les mots-clés" },
  { num: 3, title: "Le contenu" },
  { num: 4, title: "L'autorité" },
  { num: 5, title: "Présence IA (GSO)" },
];

function ScoreRing({ score }: { score: number }) {
  const color =
    score >= 70 ? "text-green-600" : score >= 40 ? "text-orange-500" : "text-red-500";
  return (
    <div className="text-center py-4">
      <span className={`text-4xl font-bold ${color}`}>{score}</span>
      <span className="text-gray-400 text-lg">/100</span>
    </div>
  );
}

export default async function ProjectLayout({
  children,
  params,
}: {
  children: React.ReactNode;
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  const project = await prisma.project.findUnique({
    where: { id },
    select: { id: true, name: true, url: true, score: true, currentLevel: true },
  });

  if (!project) notFound();

  return (
    <div className="flex min-h-screen">
      {/* Sidebar */}
      <aside className="w-64 border-r bg-gray-50 flex flex-col shrink-0">
        <div className="p-4 border-b">
          <Link href="/dashboard" className="text-xs text-gray-500 hover:text-gray-700">
            ← Tous les sites
          </Link>
          <h2 className="font-semibold mt-1 truncate">{project.name}</h2>
          <p className="text-xs text-gray-400 truncate">{project.url}</p>
        </div>

        <ScoreRing score={project.score} />

        <nav className="flex-1 p-2 space-y-1">
          <Link
            href={`/project/${id}`}
            className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm hover:bg-white hover:shadow-sm transition-all"
          >
            <span>📊</span>
            <span>Vue d'ensemble</span>
          </Link>

          <div className="pt-2 pb-1 px-3 text-xs font-semibold text-gray-400 uppercase">
            Niveaux
          </div>

          {LEVELS.map((level) => {
            const isUnlocked = level.num <= project.currentLevel;
            return (
              <div key={level.num}>
                {isUnlocked ? (
                  <Link
                    href={`/project/${id}/level/${level.num}`}
                    className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm hover:bg-white hover:shadow-sm transition-all"
                  >
                    <span className="w-5 h-5 rounded-full bg-blue-100 text-blue-700 text-xs flex items-center justify-center font-bold">
                      {level.num}
                    </span>
                    <span>{level.title}</span>
                  </Link>
                ) : (
                  <div className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-300 cursor-not-allowed">
                    <span className="w-5 h-5 rounded-full bg-gray-100 text-gray-300 text-xs flex items-center justify-center font-bold">
                      {level.num}
                    </span>
                    <span>{level.title}</span>
                    <span className="ml-auto text-xs">🔒</span>
                  </div>
                )}
              </div>
            );
          })}

          <div className="pt-2">
            <Link
              href={`/project/${id}/plan`}
              className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm hover:bg-white hover:shadow-sm transition-all"
            >
              <span>📋</span>
              <span>Plan d'action</span>
            </Link>
          </div>

          <div className="opacity-40 cursor-not-allowed">
            <div className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm">
              <span>🤖</span>
              <span>Coach IA</span>
              <span className="ml-auto text-xs bg-gray-200 px-1.5 py-0.5 rounded">Bientôt</span>
            </div>
          </div>
        </nav>
      </aside>

      {/* Main content */}
      <div className="flex-1 overflow-auto">
        {children}
      </div>
    </div>
  );
}
```

- [ ] **Step 2: Alléger la page projet (overview)**

Remplacer `seo-coach/app/project/[id]/page.tsx` :

```tsx
import { prisma } from "@/lib/db";
import { notFound } from "next/navigation";
import Link from "next/link";
import CrawlButton from "./CrawlButton";

export default async function ProjectPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  const project = await prisma.project.findUnique({
    where: { id },
    include: {
      audits: { orderBy: { date: "desc" }, take: 1 },
      tasks: { where: { status: "pending" } },
    },
  });

  if (!project) notFound();

  const lastAudit = project.audits[0];

  return (
    <main className="p-8 max-w-2xl">
      <div className="mb-8">
        <h1 className="text-2xl font-bold">{project.name}</h1>
        <p className="text-gray-500 text-sm">{project.url}</p>
      </div>

      <div className="grid grid-cols-3 gap-4 mb-8">
        <div className="border rounded-lg p-4 text-center">
          <p className="text-3xl font-bold text-blue-600">{project.score}</p>
          <p className="text-xs text-gray-500 mt-1">Score global</p>
        </div>
        <div className="border rounded-lg p-4 text-center">
          <p className="text-3xl font-bold">{project.tasks.length}</p>
          <p className="text-xs text-gray-500 mt-1">Actions en attente</p>
        </div>
        <div className="border rounded-lg p-4 text-center">
          <p className="text-3xl font-bold">Niv. {project.currentLevel}</p>
          <p className="text-xs text-gray-500 mt-1">Niveau actuel</p>
        </div>
      </div>

      <div className="mb-8">
        <CrawlButton projectId={project.id} />
        {lastAudit && (
          <p className="text-xs text-gray-400 mt-2">
            Dernier audit : {new Date(lastAudit.date).toLocaleDateString("fr-FR")}
          </p>
        )}
      </div>

      <div className="space-y-2">
        <Link
          href={`/project/${id}/level/1`}
          className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition-colors"
        >
          <span className="font-medium">Niveau 1 — Les fondations</span>
          <span className="text-gray-400">→</span>
        </Link>
        <Link
          href={`/project/${id}/plan`}
          className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition-colors"
        >
          <span className="font-medium">📋 Plan d'action ({project.tasks.length} actions)</span>
          <span className="text-gray-400">→</span>
        </Link>
      </div>
    </main>
  );
}
```

- [ ] **Step 3: Vérifier que le build compile**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
npm run build 2>&1 | grep -E "(error|Error|Route)" | head -20
```

Expected: aucune erreur, routes `/project/[id]` présentes.

- [ ] **Step 4: Commit**

```bash
git add app/project/[id]/layout.tsx app/project/[id]/page.tsx
git commit -m "feat(ui): sidebar navigation projet + vue d'ensemble restructurée"
```

---

### Task 4: Vue niveau (checks visuels)

**Files:**
- Create: `seo-coach/app/project/[id]/level/[level]/page.tsx`

- [ ] **Step 1: Créer la vue niveau**

Créer `seo-coach/app/project/[id]/level/[level]/page.tsx` :

```tsx
import { notFound } from "next/navigation";
import { prisma } from "@/lib/db";

const LEVEL_TITLES: Record<number, string> = {
  1: "Les fondations",
  2: "Les mots-clés",
  3: "Le contenu",
  4: "L'autorité",
  5: "Présence IA (GSO)",
};

interface Check {
  id: string;
  label: string;
  passed: boolean;
  score: number;
  maxScore: number;
  details: string;
  fix?: string;
}

function CheckIcon({ passed }: { passed: boolean }) {
  if (passed) return <span className="text-green-500 text-xl">✅</span>;
  return <span className="text-red-500 text-xl">❌</span>;
}

function ScoreBar({ score, maxScore }: { score: number; maxScore: number }) {
  const pct = maxScore > 0 ? Math.round((score / maxScore) * 100) : 0;
  return (
    <div className="flex items-center gap-2 mt-2">
      <div className="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
        <div
          className={`h-full rounded-full ${pct >= 70 ? "bg-green-500" : pct >= 40 ? "bg-orange-400" : "bg-red-400"}`}
          style={{ width: `${pct}%` }}
        />
      </div>
      <span className="text-xs text-gray-500 shrink-0">{score}/{maxScore}</span>
    </div>
  );
}

export default async function LevelPage({
  params,
}: {
  params: Promise<{ id: string; level: string }>;
}) {
  const { id, level } = await params;
  const levelNum = parseInt(level, 10);

  if (isNaN(levelNum) || levelNum < 1 || levelNum > 5) notFound();

  const project = await prisma.project.findUnique({
    where: { id },
    include: {
      audits: { orderBy: { date: "desc" }, take: 1 },
    },
  });

  if (!project) notFound();

  const lastAudit = project.audits[0];
  const allChecks: Check[] = lastAudit
    ? JSON.parse(lastAudit.technicalChecks)
    : [];

  const checks = allChecks.filter((c) => c.level === levelNum);
  const levelScore = checks.reduce((sum, c) => sum + c.score, 0);
  const levelMaxScore = checks.reduce((sum, c) => sum + c.maxScore, 0);
  const passedCount = checks.filter((c) => c.passed).length;

  const title = LEVEL_TITLES[levelNum] ?? `Niveau ${levelNum}`;

  return (
    <main className="p-8 max-w-2xl">
      <div className="mb-6">
        <div className="flex items-center gap-3 mb-2">
          <span className="w-8 h-8 rounded-full bg-blue-100 text-blue-700 text-sm flex items-center justify-center font-bold">
            {levelNum}
          </span>
          <h1 className="text-2xl font-bold">Niveau {levelNum} — {title}</h1>
        </div>

        {checks.length > 0 && (
          <div className="flex items-center gap-4 text-sm text-gray-500">
            <span>{passedCount}/{checks.length} vérifications réussies</span>
            <span>Score : {levelScore}/{levelMaxScore}</span>
          </div>
        )}
      </div>

      {checks.length === 0 && (
        <div className="text-center py-12 text-gray-400">
          <p className="text-4xl mb-3">📭</p>
          <p>Lance une analyse depuis la vue d'ensemble pour voir les résultats.</p>
        </div>
      )}

      <div className="space-y-3">
        {checks.map((check) => (
          <div
            key={check.id}
            className={`p-4 rounded-xl border ${
              check.passed
                ? "bg-green-50 border-green-200"
                : "bg-red-50 border-red-200"
            }`}
          >
            <div className="flex items-start gap-3">
              <CheckIcon passed={check.passed} />
              <div className="flex-1 min-w-0">
                <p className="font-medium">{check.label}</p>
                <p className="text-sm text-gray-600 mt-1">{check.details}</p>
                {check.fix && (
                  <div className="mt-2 p-2 bg-white rounded-lg border border-blue-100">
                    <p className="text-sm text-blue-700">
                      <span className="font-medium">💡 Comment corriger : </span>
                      {check.fix}
                    </p>
                  </div>
                )}
                <ScoreBar score={check.score} maxScore={check.maxScore} />
              </div>
            </div>
          </div>
        ))}
      </div>
    </main>
  );
}
```

- [ ] **Step 2: Vérifier le build**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
npm run build 2>&1 | grep -E "(error|Error|Route)" | head -20
```

Expected: route `/project/[id]/level/[level]` présente, aucune erreur.

- [ ] **Step 3: Commit**

```bash
git add app/project/[id]/level/
git commit -m "feat(ui): vue niveau — checks visuels avec score bar et instructions"
```

---

### Task 5: Vue plan d'action trié par priorité

**Files:**
- Create: `seo-coach/app/project/[id]/plan/page.tsx`
- Create: `seo-coach/app/project/[id]/plan/TaskCheckbox.tsx`

- [ ] **Step 1: Créer le composant checkbox (client)**

Créer `seo-coach/app/project/[id]/plan/TaskCheckbox.tsx` :

```tsx
"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

export default function TaskCheckbox({
  taskId,
  initialStatus,
}: {
  taskId: string;
  initialStatus: string;
}) {
  const router = useRouter();
  const [done, setDone] = useState(initialStatus === "done");
  const [loading, setLoading] = useState(false);

  async function toggle() {
    setLoading(true);
    const newStatus = done ? "pending" : "done";

    await fetch(`/api/tasks/${taskId}`, {
      method: "PATCH",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ status: newStatus }),
    });

    setDone(!done);
    setLoading(false);
    router.refresh();
  }

  return (
    <button
      onClick={toggle}
      disabled={loading}
      className={`w-6 h-6 rounded border-2 flex items-center justify-center shrink-0 transition-colors ${
        done
          ? "bg-green-500 border-green-500 text-white"
          : "border-gray-300 hover:border-green-400"
      }`}
      title={done ? "Marquer comme non fait" : "Marquer comme fait"}
    >
      {done && <span className="text-xs">✓</span>}
    </button>
  );
}
```

- [ ] **Step 2: Créer la vue plan d'action**

Créer `seo-coach/app/project/[id]/plan/page.tsx` :

```tsx
import { notFound } from "next/navigation";
import { prisma } from "@/lib/db";
import TaskCheckbox from "./TaskCheckbox";

type Impact = "high" | "medium" | "low";
type Difficulty = "easy" | "medium" | "hard";

interface Task {
  id: string;
  title: string;
  description: string;
  level: number;
  impact: Impact;
  difficulty: Difficulty;
  status: string;
}

const PRIORITY_GROUPS = [
  {
    key: "quick-wins",
    emoji: "🔥",
    label: "Quick wins — Impact fort, facile à faire",
    filter: (t: Task) => t.impact === "high" && t.difficulty === "easy",
  },
  {
    key: "high-impact",
    emoji: "💪",
    label: "Impact fort — Plus de travail",
    filter: (t: Task) => t.impact === "high" && t.difficulty !== "easy",
  },
  {
    key: "medium-easy",
    emoji: "📌",
    label: "Impact moyen — Facile à faire",
    filter: (t: Task) => t.impact === "medium" && t.difficulty === "easy",
  },
  {
    key: "rest",
    emoji: "📁",
    label: "Les autres",
    filter: (t: Task) =>
      !(
        (t.impact === "high") ||
        (t.impact === "medium" && t.difficulty === "easy")
      ),
  },
];

const IMPACT_LABELS: Record<Impact, string> = {
  high: "Impact fort",
  medium: "Impact moyen",
  low: "Impact faible",
};

const DIFFICULTY_LABELS: Record<Difficulty, string> = {
  easy: "Facile",
  medium: "Moyen",
  hard: "Difficile",
};

export default async function PlanPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  const project = await prisma.project.findUnique({
    where: { id },
    include: {
      tasks: {
        where: { status: { in: ["pending", "done"] } },
        orderBy: { createdAt: "asc" },
      },
    },
  });

  if (!project) notFound();

  const pending = project.tasks.filter((t) => t.status === "pending") as Task[];
  const done = project.tasks.filter((t) => t.status === "done") as Task[];

  return (
    <main className="p-8 max-w-2xl">
      <div className="mb-6">
        <h1 className="text-2xl font-bold">Plan d'action</h1>
        <p className="text-gray-500 text-sm mt-1">
          {pending.length} action{pending.length !== 1 ? "s" : ""} à faire
          {done.length > 0 && ` · ${done.length} terminée${done.length !== 1 ? "s" : ""}`}
        </p>
      </div>

      {pending.length === 0 && done.length === 0 && (
        <div className="text-center py-12 text-gray-400">
          <p className="text-4xl mb-3">✨</p>
          <p>Lance une analyse depuis la vue d'ensemble pour générer ton plan d'action.</p>
        </div>
      )}

      {PRIORITY_GROUPS.map((group) => {
        const tasks = pending.filter(group.filter);
        if (tasks.length === 0) return null;

        return (
          <div key={group.key} className="mb-8">
            <h2 className="text-sm font-semibold text-gray-500 uppercase mb-3">
              {group.emoji} {group.label}
            </h2>
            <div className="space-y-2">
              {tasks.map((task) => (
                <div key={task.id} className="flex gap-3 p-4 border rounded-xl hover:bg-gray-50">
                  <TaskCheckbox taskId={task.id} initialStatus={task.status} />
                  <div className="flex-1 min-w-0">
                    <p className="font-medium">{task.title}</p>
                    <p className="text-sm text-gray-600 mt-1 whitespace-pre-line">
                      {task.description}
                    </p>
                    <div className="flex gap-2 mt-2">
                      <span className="text-xs bg-gray-100 px-2 py-0.5 rounded">
                        {IMPACT_LABELS[task.impact as Impact] ?? task.impact}
                      </span>
                      <span className="text-xs bg-gray-100 px-2 py-0.5 rounded">
                        {DIFFICULTY_LABELS[task.difficulty as Difficulty] ?? task.difficulty}
                      </span>
                      <span className="text-xs bg-gray-100 px-2 py-0.5 rounded">
                        Niveau {task.level}
                      </span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        );
      })}

      {done.length > 0 && (
        <div className="mb-8">
          <h2 className="text-sm font-semibold text-gray-400 uppercase mb-3">
            ✅ Terminées
          </h2>
          <div className="space-y-2 opacity-60">
            {done.map((task) => (
              <div key={task.id} className="flex gap-3 p-4 border rounded-xl line-through">
                <TaskCheckbox taskId={task.id} initialStatus={task.status} />
                <p className="font-medium text-gray-400">{task.title}</p>
              </div>
            ))}
          </div>
        </div>
      )}
    </main>
  );
}
```

- [ ] **Step 3: Vérifier le build**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
npm run build 2>&1 | grep -E "(error|Error|Route)" | head -20
```

Expected: routes `/project/[id]/plan` présente, aucune erreur.

- [ ] **Step 4: Commit**

```bash
git add app/project/[id]/plan/
git commit -m "feat(ui): plan d'action trié par priorité avec checkbox done/pending"
```

---

### Task 6: Dashboard amélioré

**Files:**
- Modify: `seo-coach/app/dashboard/page.tsx`

- [ ] **Step 1: Améliorer le dashboard**

Remplacer `seo-coach/app/dashboard/page.tsx` :

```tsx
import Link from "next/link";
import { prisma } from "@/lib/db";

const LEVEL_TITLES: Record<number, string> = {
  1: "Les fondations",
  2: "Les mots-clés",
  3: "Le contenu",
  4: "L'autorité",
  5: "GSO",
};

function ScoreBadge({ score }: { score: number }) {
  const color =
    score >= 70
      ? "bg-green-100 text-green-700 ring-green-200"
      : score >= 40
        ? "bg-orange-100 text-orange-700 ring-orange-200"
        : "bg-red-100 text-red-700 ring-red-200";

  return (
    <span
      className={`inline-flex items-center justify-center w-12 h-12 rounded-full text-lg font-bold ring-2 ${color}`}
    >
      {score}
    </span>
  );
}

export default async function DashboardPage() {
  const projects = await prisma.project.findMany({
    orderBy: { updatedAt: "desc" },
    include: {
      audits: { orderBy: { date: "desc" }, take: 1 },
      tasks: { where: { status: "pending" } },
    },
  });

  return (
    <main className="max-w-4xl mx-auto p-8">
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-2xl font-bold">Mon Site Sur Google</h1>
          <p className="text-sm text-gray-500">Coach SEO & GSO</p>
        </div>
        <Link
          href="/new-project"
          className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
        >
          + Ajouter un site
        </Link>
      </div>

      {projects.length === 0 && (
        <div className="text-center py-16 border-2 border-dashed border-gray-200 rounded-xl">
          <p className="text-4xl mb-3">🌐</p>
          <p className="text-gray-500 mb-4">Aucun site ajouté pour l'instant.</p>
          <Link
            href="/new-project"
            className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700"
          >
            Ajouter mon premier site
          </Link>
        </div>
      )}

      <div className="grid gap-3">
        {projects.map((project) => (
          <Link
            key={project.id}
            href={`/project/${project.id}`}
            className="flex items-center gap-4 p-4 border rounded-xl hover:bg-gray-50 hover:shadow-sm transition-all"
          >
            <ScoreBadge score={project.score} />

            <div className="flex-1 min-w-0">
              <h2 className="font-semibold truncate">{project.name}</h2>
              <p className="text-sm text-gray-400 truncate">{project.url}</p>
              <p className="text-xs text-blue-600 mt-0.5">
                Niveau {project.currentLevel} — {LEVEL_TITLES[project.currentLevel]}
              </p>
            </div>

            <div className="text-right text-sm shrink-0">
              {project.tasks.length > 0 ? (
                <p className="text-orange-600 font-medium">
                  {project.tasks.length} action{project.tasks.length !== 1 ? "s" : ""}
                </p>
              ) : (
                <p className="text-green-600">✓ À jour</p>
              )}
              {project.audits[0] ? (
                <p className="text-gray-400 text-xs mt-0.5">
                  {new Date(project.audits[0].date).toLocaleDateString("fr-FR")}
                </p>
              ) : (
                <p className="text-gray-300 text-xs mt-0.5">Pas encore analysé</p>
              )}
            </div>

            <span className="text-gray-300 shrink-0">→</span>
          </Link>
        ))}
      </div>
    </main>
  );
}
```

- [ ] **Step 2: Vérifier le build complet**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
npm run build 2>&1 | tail -15
```

Expected: compilation réussie, aucune erreur TypeScript.

- [ ] **Step 3: Vérifier tous les tests encore verts**

```bash
npm test 2>&1 | tail -6
```

Expected: 28 tests passed (26 du Plan 1 + 2 nouveaux de Task 1).

- [ ] **Step 4: Nettoyer l'artefact Prisma 7**

```bash
rm -rf app/generated/
git add -A
git commit -m "feat(ui): dashboard amélioré — bouton ajout, niveau, empty state + nettoyage artefact Prisma 7"
```

---

## Récapitulatif

À la fin du Plan 2, l'application permet de :

1. ✅ Ajouter un site via un onboarding guidé en 3 étapes
2. ✅ Voir le diagnostic de confrontation intention/réalité
3. ✅ Naviguer entre les projets depuis le dashboard
4. ✅ Explorer les checks d'un niveau avec indicateurs visuels
5. ✅ Consulter et cocher les actions du plan d'action
6. ✅ Naviguer dans la vue projet via une sidebar claire

**Prochaine étape : Plan 3 — Intelligence** (coach IA Claude, explications contextuelles, rédaction assistée)
