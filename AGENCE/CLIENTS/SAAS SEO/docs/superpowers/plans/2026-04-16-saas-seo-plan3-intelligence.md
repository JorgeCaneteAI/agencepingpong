# SaaS SEO/GSO — Plan 3 : Intelligence

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ajouter un coach IA conversationnel qui répond aux questions SEO dans le contexte du projet de l'utilisateur, en utilisant le SDK Anthropic et la base de connaissances SEO/GSO.

**Architecture:** Un module `lib/claude/` encapsule le client Anthropic et la construction du prompt système (contexte projet + audit + base de connaissances). L'API route `POST /api/coach/chat` orchestre la récupération du contexte Prisma et l'appel Claude. Le composant client `ChatInterface.tsx` gère l'historique de conversation en state local et appelle l'API à chaque message.

**Tech Stack:** Next.js 16 (App Router), TypeScript, `@anthropic-ai/sdk`, Prisma 5 + SQLite, Tailwind v4

**Spec :** `AGENCE/CLIENTS/SAAS SEO/docs/superpowers/specs/2026-04-15-saas-seo-gso-design.md`

---

## Structure de fichiers

```
seo-coach/
├── .env                                   (MODIFY — ajouter ANTHROPIC_API_KEY)
├── lib/
│   ├── claude/
│   │   ├── client.ts                      (NEW — singleton Anthropic SDK)
│   │   └── prompts.ts                     (NEW — buildSystemPrompt + loadFormation)
│   └── knowledge/
│       └── formation.md                   (NEW — copie de la base de connaissances)
├── app/
│   └── api/
│       └── coach/
│           └── chat/
│               └── route.ts               (NEW — POST /api/coach/chat)
└── app/
    └── project/
        └── [id]/
            ├── layout.tsx                 (MODIFY — activer le lien Coach IA)
            └── coach/
                ├── page.tsx               (NEW — server component, charge contexte)
                └── ChatInterface.tsx      (NEW — "use client", gère la conversation)
tests/
└── claude/
    └── prompts.test.ts                    (NEW — tests buildSystemPrompt)
```

---

### Task 1: Anthropic SDK + lib/claude/client.ts

**Files:**
- Modify: `seo-coach/.env`
- Create: `seo-coach/lib/claude/client.ts`

- [ ] **Step 1: Installer le SDK Anthropic**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
npm install @anthropic-ai/sdk
```

Expected: `@anthropic-ai/sdk` apparaît dans `package.json` `dependencies`.

- [ ] **Step 2: Ajouter ANTHROPIC_API_KEY dans .env**

Ouvrir `seo-coach/.env` et ajouter à la fin :

```
ANTHROPIC_API_KEY=sk-ant-REMPLACE_PAR_TA_VRAIE_CLE
```

> **Note :** La vraie clé se trouve dans le compte Anthropic Console (console.anthropic.com). Ne jamais committer ce fichier — `.env` est déjà dans `.gitignore`.

- [ ] **Step 3: Créer le client Anthropic singleton**

Créer `seo-coach/lib/claude/client.ts` :

```ts
import Anthropic from "@anthropic-ai/sdk";

const globalForAnthropic = globalThis as unknown as {
  anthropic: Anthropic | undefined;
};

export const anthropic =
  globalForAnthropic.anthropic ??
  new Anthropic({ apiKey: process.env.ANTHROPIC_API_KEY });

if (process.env.NODE_ENV !== "production") {
  globalForAnthropic.anthropic = anthropic;
}
```

- [ ] **Step 4: Vérifier que le build TypeScript compile**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
npm run build 2>&1 | grep -E "(error|Error)" | head -10
```

Expected: aucune erreur TypeScript.

- [ ] **Step 5: Commit**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
git add lib/claude/client.ts package.json package-lock.json
git commit -m "feat(claude): installation SDK Anthropic + client singleton"
```

---

### Task 2: Base de connaissances + constructeur de prompt

**Files:**
- Create: `seo-coach/lib/knowledge/formation.md`
- Create: `seo-coach/lib/claude/prompts.ts`
- Create: `seo-coach/tests/claude/prompts.test.ts`

- [ ] **Step 1: Copier la base de connaissances dans l'app**

```bash
cp "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/formation-seo-google-search-central.md" \
   "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach/lib/knowledge/formation.md"
```

Expected: le fichier existe à `seo-coach/lib/knowledge/formation.md`.

- [ ] **Step 2: Écrire les tests du constructeur de prompt**

Créer `seo-coach/tests/claude/prompts.test.ts` :

```ts
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
    // HTTPS est passé — ne doit pas apparaître dans "points à améliorer"
    const improvementSection = result.split("## Points à améliorer")[1] ?? "";
    expect(improvementSection).not.toContain("Ton site est sécurisé");
  });

  it("contient le contenu de la base de connaissances", () => {
    const result = buildSystemPrompt(fakeProject, null, []);
    // La formation commence par un titre markdown
    expect(result).toContain("##");
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
```

- [ ] **Step 3: Vérifier que les tests échouent (fonction pas encore créée)**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
npm test -- tests/claude/prompts.test.ts 2>&1 | tail -10
```

Expected: erreur "Cannot find module '@/lib/claude/prompts'" ou similaire.

- [ ] **Step 4: Créer lib/claude/prompts.ts**

Créer `seo-coach/lib/claude/prompts.ts` :

```ts
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
```

- [ ] **Step 5: Vérifier que les tests passent**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
npm test -- tests/claude/prompts.test.ts 2>&1 | tail -10
```

Expected: 6 tests passed.

- [ ] **Step 6: Vérifier que tous les tests passent**

```bash
npm test 2>&1 | tail -5
```

Expected: 34 tests passed (28 + 6 nouveaux).

- [ ] **Step 7: Commit**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
git add lib/knowledge/formation.md lib/claude/prompts.ts tests/claude/prompts.test.ts
git commit -m "feat(claude): base de connaissances + constructeur de prompt système"
```

---

### Task 3: API POST /api/coach/chat

**Files:**
- Create: `seo-coach/app/api/coach/chat/route.ts`
- Create: `seo-coach/tests/api/coach-chat.test.ts`

- [ ] **Step 1: Écrire les tests**

Créer `seo-coach/tests/api/coach-chat.test.ts` :

```ts
// @vitest-environment node
import { describe, it, expect } from "vitest";
import { prisma } from "@/lib/db";

describe("coach/chat API helpers", () => {
  it("le projet de test existe en base", async () => {
    const project = await prisma.project.findFirst();
    expect(project).not.toBeNull();
    expect(project?.name).toBeTruthy();
  });

  it("le projet a bien les champs nécessaires au prompt", async () => {
    const project = await prisma.project.findFirst();
    if (!project) return;
    expect(typeof project.score).toBe("number");
    expect(typeof project.currentLevel).toBe("number");
    expect(project.url).toBeTruthy();
  });
});
```

- [ ] **Step 2: Vérifier que les tests passent**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
npm test -- tests/api/coach-chat.test.ts 2>&1 | tail -8
```

Expected: 2 tests passed.

- [ ] **Step 3: Créer la route API**

Créer `seo-coach/app/api/coach/chat/route.ts` :

```ts
import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";
import { anthropic } from "@/lib/claude/client";
import { buildSystemPrompt } from "@/lib/claude/prompts";

interface Message {
  role: "user" | "assistant";
  content: string;
}

export async function POST(request: Request) {
  let body: unknown;
  try {
    body = await request.json();
  } catch {
    return NextResponse.json({ error: "Corps de requête invalide" }, { status: 400 });
  }

  const { projectId, message, history = [] } = body as {
    projectId?: string;
    message?: string;
    history?: Message[];
  };

  if (!projectId || !message) {
    return NextResponse.json(
      { error: "projectId et message sont requis" },
      { status: 400 }
    );
  }

  const project = await prisma.project.findUnique({
    where: { id: projectId },
    include: {
      audits: { orderBy: { date: "desc" }, take: 1 },
      tasks: { where: { status: "pending" }, orderBy: { createdAt: "asc" } },
    },
  });

  if (!project) {
    return NextResponse.json({ error: "Projet non trouvé" }, { status: 404 });
  }

  const systemPrompt = buildSystemPrompt(
    project,
    project.audits[0] ?? null,
    project.tasks
  );

  const messages: Message[] = [...history, { role: "user", content: message }];

  try {
    const response = await anthropic.messages.create({
      model: "claude-haiku-4-5-20251001",
      max_tokens: 1024,
      system: systemPrompt,
      messages,
    });

    const reply =
      response.content[0].type === "text" ? response.content[0].text : "";

    return NextResponse.json({ reply });
  } catch (error) {
    console.error("[coach/chat] Anthropic error:", error);
    return NextResponse.json(
      { error: "Le coach est temporairement indisponible" },
      { status: 503 }
    );
  }
}
```

- [ ] **Step 4: Vérifier que le build compile**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
npm run build 2>&1 | grep -E "(error|Error|Route)" | head -15
```

Expected: route `/api/coach/chat` présente, aucune erreur TypeScript.

- [ ] **Step 5: Vérifier tous les tests**

```bash
npm test 2>&1 | tail -5
```

Expected: 36 tests passed.

- [ ] **Step 6: Commit**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
git add app/api/coach/ tests/api/coach-chat.test.ts
git commit -m "feat(api): POST /api/coach/chat — coach IA avec contexte projet + audit"
```

---

### Task 4: Interface coach — page serveur + ChatInterface client

**Files:**
- Create: `seo-coach/app/project/[id]/coach/page.tsx`
- Create: `seo-coach/app/project/[id]/coach/ChatInterface.tsx`

- [ ] **Step 1: Créer le composant chat client**

Créer `seo-coach/app/project/[id]/coach/ChatInterface.tsx` :

```tsx
"use client";

import { useState, useRef, useEffect } from "react";

interface Message {
  role: "user" | "assistant";
  content: string;
}

const SUGGESTIONS = [
  "Pourquoi mon score est-il bas ?",
  "Rédige une meta description pour mon site",
  "Qu'est-ce qu'un sitemap ?",
  "Comment améliorer ma visibilité sur ChatGPT ?",
];

export default function ChatInterface({ projectId }: { projectId: string }) {
  const [messages, setMessages] = useState<Message[]>([]);
  const [input, setInput] = useState("");
  const [loading, setLoading] = useState(false);
  const bottomRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  async function sendMessage(text: string) {
    if (!text.trim() || loading) return;

    const userMessage: Message = { role: "user", content: text };
    const newMessages = [...messages, userMessage];
    setMessages(newMessages);
    setInput("");
    setLoading(true);

    try {
      const res = await fetch("/api/coach/chat", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          projectId,
          message: text,
          history: messages,
        }),
      });

      if (!res.ok) {
        throw new Error("Erreur du coach");
      }

      const data = await res.json();
      setMessages([...newMessages, { role: "assistant", content: data.reply }]);
    } catch {
      setMessages([
        ...newMessages,
        {
          role: "assistant",
          content:
            "Désolé, une erreur s'est produite. Réessaie dans quelques instants.",
        },
      ]);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="flex flex-col flex-1 overflow-hidden">
      {/* Zone messages */}
      <div className="flex-1 overflow-y-auto p-4 space-y-4">
        {messages.length === 0 && (
          <div className="text-center py-8">
            <p className="text-3xl mb-3">🤖</p>
            <p className="text-gray-500 text-sm mb-6">
              Pose-moi une question sur le SEO de ton site.
            </p>
            <div className="grid grid-cols-2 gap-2 max-w-md mx-auto">
              {SUGGESTIONS.map((s) => (
                <button
                  key={s}
                  onClick={() => sendMessage(s)}
                  className="text-left p-3 border rounded-xl text-sm hover:bg-gray-50 transition-colors"
                >
                  {s}
                </button>
              ))}
            </div>
          </div>
        )}

        {messages.map((msg, i) => (
          <div
            key={i}
            className={`flex ${msg.role === "user" ? "justify-end" : "justify-start"}`}
          >
            <div
              className={`max-w-[80%] rounded-2xl px-4 py-3 text-sm whitespace-pre-wrap ${
                msg.role === "user"
                  ? "bg-blue-600 text-white rounded-br-sm"
                  : "bg-gray-100 text-gray-800 rounded-bl-sm"
              }`}
            >
              {msg.content}
            </div>
          </div>
        ))}

        {loading && (
          <div className="flex justify-start">
            <div className="bg-gray-100 rounded-2xl rounded-bl-sm px-4 py-3">
              <span className="inline-flex gap-1 text-gray-400">
                <span className="animate-bounce" style={{ animationDelay: "0ms" }}>•</span>
                <span className="animate-bounce" style={{ animationDelay: "150ms" }}>•</span>
                <span className="animate-bounce" style={{ animationDelay: "300ms" }}>•</span>
              </span>
            </div>
          </div>
        )}

        <div ref={bottomRef} />
      </div>

      {/* Zone saisie */}
      <div className="border-t p-4 bg-white">
        <form
          onSubmit={(e) => {
            e.preventDefault();
            sendMessage(input);
          }}
          className="flex gap-2"
        >
          <input
            type="text"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            placeholder="Pose ta question..."
            disabled={loading}
            className="flex-1 border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
          />
          <button
            type="submit"
            disabled={!input.trim() || loading}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
          >
            Envoyer
          </button>
        </form>
      </div>
    </div>
  );
}
```

- [ ] **Step 2: Créer la page serveur du coach**

Créer `seo-coach/app/project/[id]/coach/page.tsx` :

```tsx
import { notFound } from "next/navigation";
import { prisma } from "@/lib/db";
import ChatInterface from "./ChatInterface";

export default async function CoachPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  const project = await prisma.project.findUnique({
    where: { id },
    select: { id: true, name: true, score: true, currentLevel: true },
  });

  if (!project) notFound();

  return (
    <div className="flex flex-col h-[calc(100vh-0px)]">
      <div className="border-b p-4 bg-white shrink-0">
        <h1 className="font-semibold">Coach IA</h1>
        <p className="text-xs text-gray-500 mt-0.5">
          Score actuel : {project.score}/100 · Niveau {project.currentLevel}
        </p>
      </div>
      <ChatInterface projectId={id} />
    </div>
  );
}
```

- [ ] **Step 3: Vérifier que le build compile**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
npm run build 2>&1 | grep -E "(error|Error|Route)" | head -15
```

Expected: route `/project/[id]/coach` présente, aucune erreur TypeScript.

- [ ] **Step 4: Commit**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
git add app/project/[id]/coach/
git commit -m "feat(ui): interface coach IA — chat avec suggestions et état de chargement"
```

---

### Task 5: Activer le lien Coach IA dans la sidebar

**Files:**
- Modify: `seo-coach/app/project/[id]/layout.tsx`

- [ ] **Step 1: Remplacer le placeholder par un vrai lien**

Dans `seo-coach/app/project/[id]/layout.tsx`, trouver et remplacer le bloc Coach IA (actuellement désactivé avec `opacity-40 cursor-not-allowed` et `aria-hidden="true"`) :

Trouver :
```tsx
<div aria-hidden="true" className="opacity-40 cursor-not-allowed">
  <div className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm">
    <span>🤖</span>
    <span>Coach IA</span>
    <span className="ml-auto text-xs bg-gray-200 px-1.5 py-0.5 rounded">Bientôt</span>
  </div>
</div>
```

Remplacer par :
```tsx
<Link
  href={`/project/${id}/coach`}
  className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm hover:bg-white hover:shadow-sm transition-all"
>
  <span>🤖</span>
  <span>Coach IA</span>
</Link>
```

- [ ] **Step 2: Vérifier que le build compile**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
npm run build 2>&1 | grep -E "(error|Error)" | head -10
```

Expected: aucune erreur.

- [ ] **Step 3: Vérifier tous les tests**

```bash
npm test 2>&1 | tail -5
```

Expected: 36 tests passed.

- [ ] **Step 4: Commit**

```bash
cd "/Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS SEO/.claude/worktrees/jolly-roentgen/seo-coach"
git add app/project/[id]/layout.tsx
git commit -m "feat(ui): activer le lien Coach IA dans la sidebar"
```

---

## Récapitulatif

À la fin du Plan 3, l'application permet de :

1. ✅ Parler au coach IA depuis la sidebar de n'importe quel projet
2. ✅ Recevoir des réponses contextualisées (score, checks échoués, plan d'action)
3. ✅ Poser des questions en français simple sur le SEO
4. ✅ Utiliser des suggestions prédéfinies pour démarrer la conversation

**Prochaine étape : Plan 4 — Déploiement** (Vercel, variables d'env, domaine, monitoring)
