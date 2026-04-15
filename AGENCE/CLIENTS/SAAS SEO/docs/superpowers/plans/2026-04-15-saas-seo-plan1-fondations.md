# SaaS SEO/GSO — Plan 1 : Fondations

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Scaffolder l'app Next.js, créer le schéma DB, construire le crawler maison et le moteur de score — pour qu'on puisse entrer une URL et obtenir un audit technique + score.

**Architecture:** App Next.js 14 (App Router) avec SQLite via Prisma. Le crawler est un module Node autonome qui scanne une URL et retourne un rapport structuré. Le moteur de score transforme ce rapport en note 0-100 répartie sur 5 niveaux. Une API route `/api/crawl` orchestre le tout.

**Tech Stack:** Next.js 14, TypeScript, Prisma, SQLite, Cheerio (parsing HTML), Tailwind CSS

**Spec :** `docs/superpowers/specs/2026-04-15-saas-seo-gso-design.md`

---

## Structure de fichiers

```
app/                          # Next.js App Router
├── layout.tsx                # Layout racine (Tailwind, police, metadata)
├── page.tsx                  # Page d'accueil (redirige vers dashboard)
├── api/
│   ├── projects/
│   │   └── route.ts          # CRUD projets
│   ├── crawl/
│   │   └── route.ts          # Lancer un crawl sur un projet
│   └── audit/
│       └── [projectId]/
│           └── route.ts      # Récupérer le dernier audit d'un projet
├── dashboard/
│   └── page.tsx              # Dashboard (liste des projets) — placeholder UI
└── project/
    └── [id]/
        └── page.tsx          # Vue projet — placeholder UI

lib/
├── crawler/
│   ├── index.ts              # Fonction principale crawlSite(url)
│   ├── fetcher.ts            # Fetch HTTP avec timeout et user-agent
│   ├── parsers/
│   │   ├── meta.ts           # Extraire title, meta description, canonical, robots
│   │   ├── headings.ts       # Extraire la hiérarchie H1-H6
│   │   ├── images.ts         # Extraire images (src, alt, taille)
│   │   ├── links.ts          # Extraire liens internes/externes, détecter les cassés
│   │   ├── security.ts       # Vérifier HTTPS/SSL
│   │   ├── structured-data.ts # Extraire JSON-LD / données structurées
│   │   └── sitemap.ts        # Vérifier sitemap.xml et robots.txt
│   └── types.ts              # Types TypeScript du crawl
├── scoring/
│   ├── index.ts              # Fonction principale calculateScore(auditData)
│   ├── rules.ts              # Règles de scoring par niveau
│   └── types.ts              # Types du scoring
└── db.ts                     # Instance Prisma

prisma/
├── schema.prisma             # Schéma de la BDD
└── seed.ts                   # Seed avec les 4 sites de test

tests/
├── crawler/
│   ├── fetcher.test.ts
│   ├── parsers/
│   │   ├── meta.test.ts
│   │   ├── headings.test.ts
│   │   ├── images.test.ts
│   │   ├── links.test.ts
│   │   ├── security.test.ts
│   │   ├── structured-data.test.ts
│   │   └── sitemap.test.ts
│   └── index.test.ts
├── scoring/
│   ├── rules.test.ts
│   └── index.test.ts
└── api/
    ├── projects.test.ts
    └── crawl.test.ts
```

---

### Task 1: Scaffolding Next.js + Tailwind + TypeScript

**Files:**
- Create: `package.json`, `tsconfig.json`, `tailwind.config.ts`, `postcss.config.mjs`, `next.config.ts`
- Create: `app/layout.tsx`, `app/page.tsx`, `app/globals.css`

- [ ] **Step 1: Créer le projet Next.js**

```bash
cd /Users/jorgecanete/Documents/AGENCE/CLIENTS/SAAS\ SEO
npx create-next-app@latest seo-coach --typescript --tailwind --eslint --app --src-dir=false --import-alias="@/*" --use-npm
```

Répondre "No" à Turbopack si demandé.

- [ ] **Step 2: Vérifier que le projet démarre**

```bash
cd seo-coach
npm run dev
```

Expected: Le serveur démarre sur `http://localhost:3000` sans erreur.

- [ ] **Step 3: Nettoyer la page d'accueil**

Remplacer le contenu de `app/page.tsx` :

```tsx
export default function Home() {
  return (
    <main className="min-h-screen flex items-center justify-center">
      <h1 className="text-3xl font-bold">Mon Site Sur Google</h1>
      <p className="text-gray-500 mt-2">Coach SEO & GSO — en construction</p>
    </main>
  );
}
```

- [ ] **Step 4: Installer les dépendances du projet**

```bash
npm install prisma @prisma/client cheerio
npm install -D vitest @testing-library/react @testing-library/jest-dom jsdom
```

- [ ] **Step 5: Configurer Vitest**

Créer `vitest.config.ts` :

```ts
import { defineConfig } from "vitest/config";
import path from "path";

export default defineConfig({
  test: {
    globals: true,
    environment: "jsdom",
    include: ["tests/**/*.test.ts", "tests/**/*.test.tsx"],
  },
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "."),
    },
  },
});
```

Ajouter dans `package.json` > `scripts` :

```json
"test": "vitest run",
"test:watch": "vitest"
```

- [ ] **Step 6: Vérifier que Vitest fonctionne**

Créer `tests/smoke.test.ts` :

```ts
import { describe, it, expect } from "vitest";

describe("smoke test", () => {
  it("should pass", () => {
    expect(1 + 1).toBe(2);
  });
});
```

```bash
npm test
```

Expected: 1 test passed.

- [ ] **Step 7: Commit**

```bash
git add .
git commit -m "feat(seo-coach): scaffolding Next.js 14 + Tailwind + Vitest"
```

---

### Task 2: Schéma Prisma + SQLite

**Files:**
- Create: `prisma/schema.prisma`
- Create: `lib/db.ts`
- Create: `prisma/seed.ts`

- [ ] **Step 1: Initialiser Prisma avec SQLite**

```bash
npx prisma init --datasource-provider sqlite
```

- [ ] **Step 2: Écrire le schéma**

Remplacer `prisma/schema.prisma` :

```prisma
generator client {
  provider = "prisma-client-js"
}

datasource db {
  provider = "sqlite"
  url      = "file:./dev.db"
}

model Project {
  id                String   @id @default(cuid())
  url               String
  name              String
  objective         String   @default("")
  theme             String   @default("")
  geoZone           String   @default("")
  initialDiagnostic String   @default("{}") // JSON string
  score             Int      @default(0)
  currentLevel      Int      @default(1)
  createdAt         DateTime @default(now())
  updatedAt         DateTime @updatedAt
  audits            Audit[]
  tasks             Task[]
  keywords          Keyword[]
  competitors       Competitor[]
}

model Audit {
  id              String   @id @default(cuid())
  projectId       String
  project         Project  @relation(fields: [projectId], references: [id], onDelete: Cascade)
  date            DateTime @default(now())
  scoreBreakdown  String   @default("{}") // JSON: { level1: 15, level2: 8, ... }
  technicalChecks String   @default("{}") // JSON: résultats crawl
  contentAnalysis String   @default("{}") // JSON: analyse contenu
  gsoAnalysis     String   @default("{}") // JSON: visibilité IA
}

model Task {
  id          String    @id @default(cuid())
  projectId   String
  project     Project   @relation(fields: [projectId], references: [id], onDelete: Cascade)
  title       String
  description String    @default("")
  level       Int       // 1-5
  impact      String    @default("medium") // high, medium, low
  difficulty  String    @default("medium") // easy, medium, hard
  status      String    @default("pending") // pending, done, skipped
  completedAt DateTime?
  createdAt   DateTime  @default(now())
}

model Keyword {
  id          String   @id @default(cuid())
  projectId   String
  project     Project  @relation(fields: [projectId], references: [id], onDelete: Cascade)
  term        String
  volume      Int?
  position    Int?
  lastChecked DateTime @default(now())
}

model Competitor {
  id           String   @id @default(cuid())
  projectId    String
  project      Project  @relation(fields: [projectId], references: [id], onDelete: Cascade)
  url          String
  name         String   @default("")
  lastAnalyzed DateTime @default(now())
}
```

- [ ] **Step 3: Générer le client Prisma et créer la DB**

```bash
npx prisma db push
```

Expected: "Your database is now in sync with your Prisma schema."

- [ ] **Step 4: Créer l'instance Prisma singleton**

Créer `lib/db.ts` :

```ts
import { PrismaClient } from "@prisma/client";

const globalForPrisma = globalThis as unknown as {
  prisma: PrismaClient | undefined;
};

export const prisma = globalForPrisma.prisma ?? new PrismaClient();

if (process.env.NODE_ENV !== "production") {
  globalForPrisma.prisma = prisma;
}
```

- [ ] **Step 5: Créer le seed avec les 4 sites de test**

Créer `prisma/seed.ts` :

```ts
import { PrismaClient } from "@prisma/client";

const prisma = new PrismaClient();

async function main() {
  const sites = [
    {
      url: "https://villaplaisance.fr",
      name: "Villa Plaisance",
      objective: "Réservations chambres d'hôtes",
      theme: "Hébergement / tourisme",
      geoZone: "Uzès, Gard",
    },
    {
      url: "https://yelloevent.fr",
      name: "YelloEvent",
      objective: "Demandes de devis traiteur/mariage",
      theme: "Traiteur / décoration / mariage",
      geoZone: "Nîmes, Gard",
    },
    {
      url: "https://canete.fr",
      name: "Canete Conciergerie",
      objective: "Contact propriétaires Airbnb",
      theme: "Conciergerie / gestion de propriété",
      geoZone: "Uzès, Gard",
    },
    {
      url: "https://agencepingpong.fr",
      name: "Agence Ping Pong",
      objective: "Acquisition clients web",
      theme: "Agence web / développement",
      geoZone: "France entière",
    },
  ];

  for (const site of sites) {
    const existing = await prisma.project.findFirst({
      where: { url: site.url },
    });

    if (existing) {
      await prisma.project.update({
        where: { id: existing.id },
        data: site,
      });
    } else {
      await prisma.project.create({ data: site });
    }
  }

  console.log("Seed completed: 4 projects created");
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(() => prisma.$disconnect());
```

Ajouter dans `package.json` :

```json
"prisma": {
  "seed": "npx tsx prisma/seed.ts"
}
```

```bash
npm install -D tsx
npx prisma db seed
```

Expected: "Seed completed: 4 projects created"

- [ ] **Step 6: Commit**

```bash
git add .
git commit -m "feat(seo-coach): schéma Prisma SQLite + seed 4 sites de test"
```

---

### Task 3: Crawler — Fetcher HTTP

**Files:**
- Create: `lib/crawler/types.ts`
- Create: `lib/crawler/fetcher.ts`
- Create: `tests/crawler/fetcher.test.ts`

- [ ] **Step 1: Définir les types du crawler**

Créer `lib/crawler/types.ts` :

```ts
export interface CrawlResult {
  url: string;
  fetchedAt: string;
  httpStatus: number;
  responseTimeMs: number;
  html: string;
  meta: MetaData;
  headings: HeadingData;
  images: ImageData[];
  links: LinkData;
  security: SecurityData;
  structuredData: StructuredDataItem[];
  sitemap: SitemapData;
  robots: RobotsData;
}

export interface MetaData {
  title: string | null;
  titleLength: number;
  metaDescription: string | null;
  metaDescriptionLength: number;
  canonical: string | null;
  metaRobots: string | null;
  ogTitle: string | null;
  ogDescription: string | null;
  ogImage: string | null;
  lang: string | null;
}

export interface HeadingData {
  h1: string[];
  h2: string[];
  h3: string[];
  h4: string[];
  h5: string[];
  h6: string[];
  h1Count: number;
  hasMultipleH1: boolean;
  hierarchyValid: boolean;
}

export interface ImageData {
  src: string;
  alt: string | null;
  hasAlt: boolean;
}

export interface LinkData {
  internal: { href: string; text: string; status?: number }[];
  external: { href: string; text: string; status?: number }[];
  broken: { href: string; text: string; status: number }[];
  internalCount: number;
  externalCount: number;
  brokenCount: number;
}

export interface SecurityData {
  isHttps: boolean;
  hasValidCert: boolean;
}

export interface StructuredDataItem {
  type: string;
  raw: Record<string, unknown>;
}

export interface SitemapData {
  exists: boolean;
  url: string | null;
  urlCount: number;
}

export interface RobotsData {
  exists: boolean;
  content: string | null;
  blocksImportantPaths: boolean;
}

export interface FetchResult {
  html: string;
  httpStatus: number;
  responseTimeMs: number;
  isHttps: boolean;
  finalUrl: string;
}
```

- [ ] **Step 2: Écrire le test du fetcher**

Créer `tests/crawler/fetcher.test.ts` :

```ts
import { describe, it, expect, vi } from "vitest";
import { fetchPage } from "@/lib/crawler/fetcher";

describe("fetchPage", () => {
  it("should return html, status and response time for a valid URL", async () => {
    const result = await fetchPage("https://example.com");
    expect(result.httpStatus).toBe(200);
    expect(result.html).toContain("Example Domain");
    expect(result.responseTimeMs).toBeGreaterThan(0);
    expect(result.isHttps).toBe(true);
  });

  it("should handle non-existent domains gracefully", async () => {
    const result = await fetchPage("https://this-domain-does-not-exist-xyz-123.com");
    expect(result.httpStatus).toBe(0);
    expect(result.html).toBe("");
  });

  it("should respect timeout", async () => {
    const result = await fetchPage("https://httpstat.us/200?sleep=10000", 2000);
    expect(result.httpStatus).toBe(0);
  });
});
```

- [ ] **Step 3: Vérifier que le test échoue**

```bash
npm test -- tests/crawler/fetcher.test.ts
```

Expected: FAIL — module not found.

- [ ] **Step 4: Implémenter le fetcher**

Créer `lib/crawler/fetcher.ts` :

```ts
import type { FetchResult } from "./types";

const DEFAULT_TIMEOUT = 15000;
const USER_AGENT =
  "MonSiteSurGoogle/1.0 (SEO Audit Bot; +https://agencepingpong.fr)";

export async function fetchPage(
  url: string,
  timeout: number = DEFAULT_TIMEOUT
): Promise<FetchResult> {
  const start = Date.now();

  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);

    const response = await fetch(url, {
      headers: { "User-Agent": USER_AGENT },
      signal: controller.signal,
      redirect: "follow",
    });

    clearTimeout(timeoutId);

    const html = await response.text();
    const responseTimeMs = Date.now() - start;
    const isHttps = new URL(url).protocol === "https:";

    return {
      html,
      httpStatus: response.status,
      responseTimeMs,
      isHttps,
      finalUrl: response.url,
    };
  } catch (error) {
    return {
      html: "",
      httpStatus: 0,
      responseTimeMs: Date.now() - start,
      isHttps: false,
      finalUrl: url,
    };
  }
}
```

- [ ] **Step 5: Vérifier que les tests passent**

```bash
npm test -- tests/crawler/fetcher.test.ts
```

Expected: 3 tests passed (le test timeout peut être lent, ~2s).

- [ ] **Step 6: Commit**

```bash
git add lib/crawler/types.ts lib/crawler/fetcher.ts tests/crawler/fetcher.test.ts
git commit -m "feat(crawler): types + fetcher HTTP avec timeout et user-agent"
```

---

### Task 4: Crawler — Parsers (meta, headings, images)

**Files:**
- Create: `lib/crawler/parsers/meta.ts`
- Create: `lib/crawler/parsers/headings.ts`
- Create: `lib/crawler/parsers/images.ts`
- Create: `tests/crawler/parsers/meta.test.ts`
- Create: `tests/crawler/parsers/headings.test.ts`
- Create: `tests/crawler/parsers/images.test.ts`

- [ ] **Step 1: Écrire les tests du parser meta**

Créer `tests/crawler/parsers/meta.test.ts` :

```ts
import { describe, it, expect } from "vitest";
import { parseMeta } from "@/lib/crawler/parsers/meta";

const HTML_COMPLETE = `
<html lang="fr">
<head>
  <title>Mon Super Site - Traiteur Mariage Nîmes</title>
  <meta name="description" content="YelloEvent, votre traiteur pour mariage à Nîmes et dans le Gard." />
  <link rel="canonical" href="https://yelloevent.fr/" />
  <meta name="robots" content="index, follow" />
  <meta property="og:title" content="YelloEvent - Traiteur Mariage" />
  <meta property="og:description" content="Traiteur pour mariage" />
  <meta property="og:image" content="https://yelloevent.fr/og.jpg" />
</head>
<body></body>
</html>`;

const HTML_MISSING = `<html><head></head><body>Hello</body></html>`;

describe("parseMeta", () => {
  it("should extract all meta tags from complete HTML", () => {
    const result = parseMeta(HTML_COMPLETE);
    expect(result.title).toBe("Mon Super Site - Traiteur Mariage Nîmes");
    expect(result.titleLength).toBe(43);
    expect(result.metaDescription).toContain("YelloEvent");
    expect(result.canonical).toBe("https://yelloevent.fr/");
    expect(result.metaRobots).toBe("index, follow");
    expect(result.ogTitle).toBe("YelloEvent - Traiteur Mariage");
    expect(result.lang).toBe("fr");
  });

  it("should return nulls for missing meta tags", () => {
    const result = parseMeta(HTML_MISSING);
    expect(result.title).toBeNull();
    expect(result.metaDescription).toBeNull();
    expect(result.canonical).toBeNull();
    expect(result.titleLength).toBe(0);
  });
});
```

- [ ] **Step 2: Implémenter le parser meta**

Créer `lib/crawler/parsers/meta.ts` :

```ts
import * as cheerio from "cheerio";
import type { MetaData } from "../types";

export function parseMeta(html: string): MetaData {
  const $ = cheerio.load(html);

  const title = $("title").first().text().trim() || null;
  const metaDescription =
    $('meta[name="description"]').attr("content")?.trim() || null;
  const canonical = $('link[rel="canonical"]').attr("href")?.trim() || null;
  const metaRobots =
    $('meta[name="robots"]').attr("content")?.trim() || null;
  const ogTitle =
    $('meta[property="og:title"]').attr("content")?.trim() || null;
  const ogDescription =
    $('meta[property="og:description"]').attr("content")?.trim() || null;
  const ogImage =
    $('meta[property="og:image"]').attr("content")?.trim() || null;
  const lang = $("html").attr("lang")?.trim() || null;

  return {
    title,
    titleLength: title?.length ?? 0,
    metaDescription,
    metaDescriptionLength: metaDescription?.length ?? 0,
    canonical,
    metaRobots,
    ogTitle,
    ogDescription,
    ogImage,
    lang,
  };
}
```

- [ ] **Step 3: Écrire les tests du parser headings**

Créer `tests/crawler/parsers/headings.test.ts` :

```ts
import { describe, it, expect } from "vitest";
import { parseHeadings } from "@/lib/crawler/parsers/headings";

const HTML_VALID = `
<html><body>
  <h1>Titre Principal</h1>
  <h2>Section 1</h2>
  <h3>Sous-section 1.1</h3>
  <h2>Section 2</h2>
</body></html>`;

const HTML_MULTIPLE_H1 = `
<html><body>
  <h1>Premier H1</h1>
  <h1>Deuxième H1</h1>
  <h3>H3 sans H2 parent</h3>
</body></html>`;

const HTML_NO_HEADINGS = `<html><body><p>Pas de titres</p></body></html>`;

describe("parseHeadings", () => {
  it("should extract headings with valid hierarchy", () => {
    const result = parseHeadings(HTML_VALID);
    expect(result.h1).toEqual(["Titre Principal"]);
    expect(result.h1Count).toBe(1);
    expect(result.h2).toEqual(["Section 1", "Section 2"]);
    expect(result.hasMultipleH1).toBe(false);
    expect(result.hierarchyValid).toBe(true);
  });

  it("should detect multiple H1 and invalid hierarchy", () => {
    const result = parseHeadings(HTML_MULTIPLE_H1);
    expect(result.h1Count).toBe(2);
    expect(result.hasMultipleH1).toBe(true);
    expect(result.hierarchyValid).toBe(false);
  });

  it("should handle pages with no headings", () => {
    const result = parseHeadings(HTML_NO_HEADINGS);
    expect(result.h1Count).toBe(0);
    expect(result.h1).toEqual([]);
  });
});
```

- [ ] **Step 4: Implémenter le parser headings**

Créer `lib/crawler/parsers/headings.ts` :

```ts
import * as cheerio from "cheerio";
import type { HeadingData } from "../types";

export function parseHeadings(html: string): HeadingData {
  const $ = cheerio.load(html);

  const extract = (tag: string): string[] =>
    $(tag)
      .map((_, el) => $(el).text().trim())
      .get();

  const h1 = extract("h1");
  const h2 = extract("h2");
  const h3 = extract("h3");
  const h4 = extract("h4");
  const h5 = extract("h5");
  const h6 = extract("h6");

  // Hierarchy is valid if: exactly 1 H1, and no H3 without H2, etc.
  const hasMultipleH1 = h1.length > 1;
  const hierarchyValid =
    h1.length === 1 && !(h3.length > 0 && h2.length === 0);

  return {
    h1,
    h2,
    h3,
    h4,
    h5,
    h6,
    h1Count: h1.length,
    hasMultipleH1,
    hierarchyValid,
  };
}
```

- [ ] **Step 5: Écrire les tests du parser images**

Créer `tests/crawler/parsers/images.test.ts` :

```ts
import { describe, it, expect } from "vitest";
import { parseImages } from "@/lib/crawler/parsers/images";

const HTML = `
<html><body>
  <img src="/photo.jpg" alt="Photo de mariage" />
  <img src="/logo.png" />
  <img src="/hero.webp" alt="" />
</body></html>`;

describe("parseImages", () => {
  it("should extract images with alt status", () => {
    const result = parseImages(HTML);
    expect(result).toHaveLength(3);
    expect(result[0].alt).toBe("Photo de mariage");
    expect(result[0].hasAlt).toBe(true);
    expect(result[1].alt).toBeNull();
    expect(result[1].hasAlt).toBe(false);
    expect(result[2].alt).toBe("");
    expect(result[2].hasAlt).toBe(false);
  });
});
```

- [ ] **Step 6: Implémenter le parser images**

Créer `lib/crawler/parsers/images.ts` :

```ts
import * as cheerio from "cheerio";
import type { ImageData } from "../types";

export function parseImages(html: string): ImageData[] {
  const $ = cheerio.load(html);

  return $("img")
    .map((_, el) => {
      const src = $(el).attr("src") || "";
      const alt = $(el).attr("alt") ?? null;
      const hasAlt = alt !== null && alt.trim().length > 0;
      return { src, alt, hasAlt };
    })
    .get();
}
```

- [ ] **Step 7: Lancer tous les tests parsers**

```bash
npm test -- tests/crawler/parsers/
```

Expected: 7 tests passed.

- [ ] **Step 8: Commit**

```bash
git add lib/crawler/parsers/ tests/crawler/parsers/
git commit -m "feat(crawler): parsers meta, headings, images"
```

---

### Task 5: Crawler — Parsers (links, security, structured-data, sitemap)

**Files:**
- Create: `lib/crawler/parsers/links.ts`
- Create: `lib/crawler/parsers/security.ts`
- Create: `lib/crawler/parsers/structured-data.ts`
- Create: `lib/crawler/parsers/sitemap.ts`
- Create: `tests/crawler/parsers/links.test.ts`
- Create: `tests/crawler/parsers/security.test.ts`
- Create: `tests/crawler/parsers/structured-data.test.ts`
- Create: `tests/crawler/parsers/sitemap.test.ts`

- [ ] **Step 1: Écrire le test du parser links**

Créer `tests/crawler/parsers/links.test.ts` :

```ts
import { describe, it, expect } from "vitest";
import { parseLinks } from "@/lib/crawler/parsers/links";

const HTML = `
<html><body>
  <a href="/about">À propos</a>
  <a href="/services">Nos services</a>
  <a href="https://google.com">Google</a>
  <a href="https://facebook.com/page">Facebook</a>
</body></html>`;

describe("parseLinks", () => {
  it("should separate internal and external links", () => {
    const result = parseLinks(HTML, "https://monsite.fr");
    expect(result.internalCount).toBe(2);
    expect(result.externalCount).toBe(2);
    expect(result.internal[0].href).toBe("/about");
    expect(result.internal[0].text).toBe("À propos");
    expect(result.external[0].href).toBe("https://google.com");
  });

  it("should handle empty pages", () => {
    const result = parseLinks("<html><body></body></html>", "https://monsite.fr");
    expect(result.internalCount).toBe(0);
    expect(result.externalCount).toBe(0);
  });
});
```

- [ ] **Step 2: Implémenter le parser links**

Créer `lib/crawler/parsers/links.ts` :

```ts
import * as cheerio from "cheerio";
import type { LinkData } from "../types";

export function parseLinks(html: string, baseUrl: string): LinkData {
  const $ = cheerio.load(html);
  const baseHost = new URL(baseUrl).hostname;

  const internal: LinkData["internal"] = [];
  const external: LinkData["external"] = [];

  $("a[href]").each((_, el) => {
    const href = $(el).attr("href") || "";
    const text = $(el).text().trim();

    if (!href || href.startsWith("#") || href.startsWith("mailto:") || href.startsWith("tel:")) {
      return;
    }

    try {
      const resolved = new URL(href, baseUrl);
      if (resolved.hostname === baseHost) {
        internal.push({ href, text });
      } else {
        external.push({ href, text });
      }
    } catch {
      internal.push({ href, text });
    }
  });

  return {
    internal,
    external,
    broken: [], // Populated later by link checker
    internalCount: internal.length,
    externalCount: external.length,
    brokenCount: 0,
  };
}
```

- [ ] **Step 3: Écrire le test du parser security**

Créer `tests/crawler/parsers/security.test.ts` :

```ts
import { describe, it, expect } from "vitest";
import { parseSecurity } from "@/lib/crawler/parsers/security";

describe("parseSecurity", () => {
  it("should detect HTTPS", () => {
    const result = parseSecurity("https://example.com");
    expect(result.isHttps).toBe(true);
  });

  it("should detect HTTP (not secure)", () => {
    const result = parseSecurity("http://example.com");
    expect(result.isHttps).toBe(false);
  });
});
```

- [ ] **Step 4: Implémenter le parser security**

Créer `lib/crawler/parsers/security.ts` :

```ts
import type { SecurityData } from "../types";

export function parseSecurity(url: string): SecurityData {
  const isHttps = url.startsWith("https://");
  return {
    isHttps,
    hasValidCert: isHttps, // Simplified: if fetch succeeded over HTTPS, cert is valid
  };
}
```

- [ ] **Step 5: Écrire le test du parser structured-data**

Créer `tests/crawler/parsers/structured-data.test.ts` :

```ts
import { describe, it, expect } from "vitest";
import { parseStructuredData } from "@/lib/crawler/parsers/structured-data";

const HTML_WITH_JSONLD = `
<html><head>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "YelloEvent"
}
</script>
</head><body></body></html>`;

const HTML_WITHOUT = `<html><head></head><body></body></html>`;

describe("parseStructuredData", () => {
  it("should extract JSON-LD structured data", () => {
    const result = parseStructuredData(HTML_WITH_JSONLD);
    expect(result).toHaveLength(1);
    expect(result[0].type).toBe("LocalBusiness");
    expect(result[0].raw.name).toBe("YelloEvent");
  });

  it("should return empty array when no structured data", () => {
    const result = parseStructuredData(HTML_WITHOUT);
    expect(result).toEqual([]);
  });
});
```

- [ ] **Step 6: Implémenter le parser structured-data**

Créer `lib/crawler/parsers/structured-data.ts` :

```ts
import * as cheerio from "cheerio";
import type { StructuredDataItem } from "../types";

export function parseStructuredData(html: string): StructuredDataItem[] {
  const $ = cheerio.load(html);
  const items: StructuredDataItem[] = [];

  $('script[type="application/ld+json"]').each((_, el) => {
    try {
      const raw = JSON.parse($(el).html() || "");
      const type = raw["@type"] || "Unknown";
      items.push({ type, raw });
    } catch {
      // Invalid JSON-LD, skip
    }
  });

  return items;
}
```

- [ ] **Step 7: Écrire le test du parser sitemap**

Créer `tests/crawler/parsers/sitemap.test.ts` :

```ts
import { describe, it, expect } from "vitest";
import { checkSitemap, checkRobots } from "@/lib/crawler/parsers/sitemap";

describe("checkSitemap", () => {
  it("should detect sitemap on example.com (may not exist)", async () => {
    const result = await checkSitemap("https://example.com");
    expect(typeof result.exists).toBe("boolean");
    expect(result.url).toBe("https://example.com/sitemap.xml");
  });
});

describe("checkRobots", () => {
  it("should detect robots.txt on example.com", async () => {
    const result = await checkRobots("https://example.com");
    expect(typeof result.exists).toBe("boolean");
  });
});
```

- [ ] **Step 8: Implémenter le parser sitemap**

Créer `lib/crawler/parsers/sitemap.ts` :

```ts
import type { SitemapData, RobotsData } from "../types";

export async function checkSitemap(baseUrl: string): Promise<SitemapData> {
  const sitemapUrl = `${baseUrl.replace(/\/$/, "")}/sitemap.xml`;

  try {
    const response = await fetch(sitemapUrl, {
      signal: AbortSignal.timeout(5000),
    });

    if (!response.ok) {
      return { exists: false, url: sitemapUrl, urlCount: 0 };
    }

    const text = await response.text();
    const urlCount = (text.match(/<loc>/g) || []).length;

    return { exists: true, url: sitemapUrl, urlCount };
  } catch {
    return { exists: false, url: sitemapUrl, urlCount: 0 };
  }
}

export async function checkRobots(baseUrl: string): Promise<RobotsData> {
  const robotsUrl = `${baseUrl.replace(/\/$/, "")}/robots.txt`;

  try {
    const response = await fetch(robotsUrl, {
      signal: AbortSignal.timeout(5000),
    });

    if (!response.ok) {
      return { exists: false, content: null, blocksImportantPaths: false };
    }

    const content = await response.text();
    const blocksImportantPaths =
      content.includes("Disallow: /") &&
      !content.includes("Disallow: /admin") &&
      content.split("Disallow: /").length > 2;

    return { exists: true, content, blocksImportantPaths };
  } catch {
    return { exists: false, content: null, blocksImportantPaths: false };
  }
}
```

- [ ] **Step 9: Lancer tous les tests parsers**

```bash
npm test -- tests/crawler/parsers/
```

Expected: tous les tests passent.

- [ ] **Step 10: Commit**

```bash
git add lib/crawler/parsers/ tests/crawler/parsers/
git commit -m "feat(crawler): parsers links, security, structured-data, sitemap"
```

---

### Task 6: Crawler — Fonction principale crawlSite()

**Files:**
- Create: `lib/crawler/index.ts`
- Create: `tests/crawler/index.test.ts`

- [ ] **Step 1: Écrire le test d'intégration du crawler**

Créer `tests/crawler/index.test.ts` :

```ts
import { describe, it, expect } from "vitest";
import { crawlSite } from "@/lib/crawler";

describe("crawlSite", () => {
  it("should crawl example.com and return a complete CrawlResult", async () => {
    const result = await crawlSite("https://example.com");

    expect(result.url).toBe("https://example.com");
    expect(result.httpStatus).toBe(200);
    expect(result.responseTimeMs).toBeGreaterThan(0);
    expect(result.meta.title).toBeTruthy();
    expect(result.headings.h1Count).toBeGreaterThanOrEqual(0);
    expect(Array.isArray(result.images)).toBe(true);
    expect(result.security.isHttps).toBe(true);
    expect(typeof result.sitemap.exists).toBe("boolean");
    expect(typeof result.robots.exists).toBe("boolean");
  }, 30000); // Timeout long pour les requêtes réseau

  it("should handle unreachable sites gracefully", async () => {
    const result = await crawlSite("https://this-does-not-exist-xyz-999.com");

    expect(result.httpStatus).toBe(0);
    expect(result.meta.title).toBeNull();
  }, 20000);
});
```

- [ ] **Step 2: Vérifier que le test échoue**

```bash
npm test -- tests/crawler/index.test.ts
```

Expected: FAIL — module not found.

- [ ] **Step 3: Implémenter crawlSite()**

Créer `lib/crawler/index.ts` :

```ts
import type { CrawlResult } from "./types";
import { fetchPage } from "./fetcher";
import { parseMeta } from "./parsers/meta";
import { parseHeadings } from "./parsers/headings";
import { parseImages } from "./parsers/images";
import { parseLinks } from "./parsers/links";
import { parseSecurity } from "./parsers/security";
import { parseStructuredData } from "./parsers/structured-data";
import { checkSitemap, checkRobots } from "./parsers/sitemap";

export async function crawlSite(url: string): Promise<CrawlResult> {
  const normalizedUrl = url.replace(/\/$/, "");
  const fetchResult = await fetchPage(normalizedUrl);

  const [sitemap, robots] = await Promise.all([
    checkSitemap(normalizedUrl),
    checkRobots(normalizedUrl),
  ]);

  return {
    url: normalizedUrl,
    fetchedAt: new Date().toISOString(),
    httpStatus: fetchResult.httpStatus,
    responseTimeMs: fetchResult.responseTimeMs,
    html: fetchResult.html,
    meta: parseMeta(fetchResult.html),
    headings: parseHeadings(fetchResult.html),
    images: parseImages(fetchResult.html),
    links: parseLinks(fetchResult.html, normalizedUrl),
    security: parseSecurity(normalizedUrl),
    structuredData: parseStructuredData(fetchResult.html),
    sitemap,
    robots,
  };
}

export type { CrawlResult } from "./types";
```

- [ ] **Step 4: Lancer le test d'intégration**

```bash
npm test -- tests/crawler/index.test.ts
```

Expected: 2 tests passed.

- [ ] **Step 5: Commit**

```bash
git add lib/crawler/index.ts tests/crawler/index.test.ts
git commit -m "feat(crawler): fonction principale crawlSite() — intégration complète"
```

---

### Task 7: Moteur de score

**Files:**
- Create: `lib/scoring/types.ts`
- Create: `lib/scoring/rules.ts`
- Create: `lib/scoring/index.ts`
- Create: `tests/scoring/rules.test.ts`
- Create: `tests/scoring/index.test.ts`

- [ ] **Step 1: Définir les types du scoring**

Créer `lib/scoring/types.ts` :

```ts
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
```

- [ ] **Step 2: Écrire les tests des règles de scoring**

Créer `tests/scoring/rules.test.ts` :

```ts
import { describe, it, expect } from "vitest";
import { evaluateLevel1Rules } from "@/lib/scoring/rules";
import type { CrawlResult } from "@/lib/crawler/types";

function makeCrawlResult(overrides: Partial<CrawlResult> = {}): CrawlResult {
  return {
    url: "https://example.com",
    fetchedAt: new Date().toISOString(),
    httpStatus: 200,
    responseTimeMs: 500,
    html: "<html></html>",
    meta: {
      title: "Mon Site - Accueil",
      titleLength: 18,
      metaDescription: "Description de test pour le site.",
      metaDescriptionLength: 33,
      canonical: "https://example.com/",
      metaRobots: "index, follow",
      ogTitle: "Mon Site",
      ogDescription: "Description OG",
      ogImage: "https://example.com/og.jpg",
      lang: "fr",
    },
    headings: {
      h1: ["Bienvenue"],
      h2: ["Services", "Contact"],
      h3: [],
      h4: [],
      h5: [],
      h6: [],
      h1Count: 1,
      hasMultipleH1: false,
      hierarchyValid: true,
    },
    images: [
      { src: "/img.jpg", alt: "Photo", hasAlt: true },
    ],
    links: {
      internal: [{ href: "/about", text: "À propos" }],
      external: [],
      broken: [],
      internalCount: 1,
      externalCount: 0,
      brokenCount: 0,
    },
    security: { isHttps: true, hasValidCert: true },
    structuredData: [],
    sitemap: { exists: true, url: "https://example.com/sitemap.xml", urlCount: 5 },
    robots: { exists: true, content: "User-agent: *\nAllow: /", blocksImportantPaths: false },
    ...overrides,
  };
}

describe("evaluateLevel1Rules", () => {
  it("should give high score for a well-configured site", () => {
    const crawl = makeCrawlResult();
    const checks = evaluateLevel1Rules(crawl);
    const totalScore = checks.reduce((sum, c) => sum + c.score, 0);

    expect(totalScore).toBeGreaterThan(15);
    expect(checks.every((c) => c.level === 1)).toBe(true);
  });

  it("should penalize missing HTTPS", () => {
    const crawl = makeCrawlResult({
      security: { isHttps: false, hasValidCert: false },
    });
    const checks = evaluateLevel1Rules(crawl);
    const httpsCheck = checks.find((c) => c.id === "https");

    expect(httpsCheck?.passed).toBe(false);
    expect(httpsCheck?.score).toBe(0);
  });

  it("should penalize missing title", () => {
    const crawl = makeCrawlResult({
      meta: {
        title: null,
        titleLength: 0,
        metaDescription: "Desc",
        metaDescriptionLength: 4,
        canonical: null,
        metaRobots: null,
        ogTitle: null,
        ogDescription: null,
        ogImage: null,
        lang: null,
      },
    });
    const checks = evaluateLevel1Rules(crawl);
    const titleCheck = checks.find((c) => c.id === "title");

    expect(titleCheck?.passed).toBe(false);
  });
});
```

- [ ] **Step 3: Vérifier que les tests échouent**

```bash
npm test -- tests/scoring/rules.test.ts
```

Expected: FAIL — module not found.

- [ ] **Step 4: Implémenter les règles de scoring Niveau 1**

Créer `lib/scoring/rules.ts` :

```ts
import type { CrawlResult } from "../crawler/types";
import type { CheckResult } from "./types";

export function evaluateLevel1Rules(crawl: CrawlResult): CheckResult[] {
  const checks: CheckResult[] = [];

  // HTTPS
  checks.push({
    id: "https",
    label: "Ton site est sécurisé (HTTPS)",
    level: 1,
    passed: crawl.security.isHttps,
    score: crawl.security.isHttps ? 3 : 0,
    maxScore: 3,
    details: crawl.security.isHttps
      ? "Ton site utilise HTTPS — Google adore ça."
      : "Ton site n'est pas en HTTPS. Google pénalise les sites non sécurisés.",
    fix: crawl.security.isHttps
      ? undefined
      : "Active le certificat SSL chez ton hébergeur. C'est souvent gratuit (Let's Encrypt).",
  });

  // Title
  const titleOk =
    crawl.meta.title !== null &&
    crawl.meta.titleLength >= 30 &&
    crawl.meta.titleLength <= 65;
  checks.push({
    id: "title",
    label: "Tes pages ont un bon titre",
    level: 1,
    passed: titleOk,
    score: titleOk ? 3 : crawl.meta.title ? 1 : 0,
    maxScore: 3,
    details: crawl.meta.title
      ? `Ton titre fait ${crawl.meta.titleLength} caractères. L'idéal est entre 30 et 65.`
      : "Aucun titre trouvé sur ta page. C'est la première chose que Google lit !",
    fix: titleOk
      ? undefined
      : "Ajoute un titre descriptif avec ton mot-clé principal. Ex: 'Traiteur Mariage Nîmes — YelloEvent'",
  });

  // Meta description
  const descOk =
    crawl.meta.metaDescription !== null &&
    crawl.meta.metaDescriptionLength >= 120 &&
    crawl.meta.metaDescriptionLength <= 160;
  checks.push({
    id: "meta-description",
    label: "Le résumé qui apparaît dans Google",
    level: 1,
    passed: descOk,
    score: descOk ? 3 : crawl.meta.metaDescription ? 1 : 0,
    maxScore: 3,
    details: crawl.meta.metaDescription
      ? `Ta meta description fait ${crawl.meta.metaDescriptionLength} caractères. L'idéal est entre 120 et 160.`
      : "Pas de meta description. Google invente un résumé à ta place — souvent pas terrible.",
    fix: descOk
      ? undefined
      : "Écris un résumé accrocheur de 120-160 caractères qui donne envie de cliquer.",
  });

  // H1
  const h1Ok = crawl.headings.h1Count === 1;
  checks.push({
    id: "h1",
    label: "Ta page a un titre principal (H1)",
    level: 1,
    passed: h1Ok,
    score: h1Ok ? 2 : 0,
    maxScore: 2,
    details: h1Ok
      ? `Parfait : un seul H1 — "${crawl.headings.h1[0]}".`
      : crawl.headings.h1Count === 0
        ? "Aucun H1 trouvé. Le H1 est le titre principal de ta page."
        : `${crawl.headings.h1Count} H1 trouvés. Il ne doit y en avoir qu'un seul par page.`,
    fix: h1Ok
      ? undefined
      : "Ajoute un seul H1 par page avec ton sujet principal.",
  });

  // Sitemap
  checks.push({
    id: "sitemap",
    label: "Google peut trouver toutes tes pages (sitemap)",
    level: 1,
    passed: crawl.sitemap.exists,
    score: crawl.sitemap.exists ? 2 : 0,
    maxScore: 2,
    details: crawl.sitemap.exists
      ? `Sitemap trouvé avec ${crawl.sitemap.urlCount} URLs.`
      : "Pas de sitemap.xml. Google doit deviner quelles pages existent.",
    fix: crawl.sitemap.exists
      ? undefined
      : "Crée un fichier sitemap.xml à la racine de ton site avec la liste de tes pages.",
  });

  // Robots.txt
  checks.push({
    id: "robots",
    label: "Google sait quoi visiter (robots.txt)",
    level: 1,
    passed: crawl.robots.exists && !crawl.robots.blocksImportantPaths,
    score: crawl.robots.exists && !crawl.robots.blocksImportantPaths ? 2 : crawl.robots.exists ? 1 : 0,
    maxScore: 2,
    details: crawl.robots.exists
      ? crawl.robots.blocksImportantPaths
        ? "Attention : ton robots.txt bloque des pages importantes !"
        : "robots.txt en place et correct."
      : "Pas de robots.txt. Ce n'est pas bloquant mais c'est une bonne pratique.",
    fix: !crawl.robots.exists
      ? "Crée un fichier robots.txt à la racine de ton site."
      : crawl.robots.blocksImportantPaths
        ? "Vérifie ton robots.txt — il bloque des pages que Google devrait voir."
        : undefined,
  });

  // Response time
  const speedOk = crawl.responseTimeMs < 3000;
  checks.push({
    id: "speed",
    label: "Ton site répond vite",
    level: 1,
    passed: speedOk,
    score: speedOk ? 2 : crawl.responseTimeMs < 5000 ? 1 : 0,
    maxScore: 2,
    details: `Ton serveur répond en ${crawl.responseTimeMs}ms. ${speedOk ? "C'est rapide !" : "C'est trop lent — Google pénalise les sites lents."}`,
    fix: speedOk
      ? undefined
      : "Contacte ton hébergeur ou optimise ton site (images, cache, code).",
  });

  // Lang attribute
  const langOk = crawl.meta.lang !== null;
  checks.push({
    id: "lang",
    label: "Google sait dans quelle langue est ton site",
    level: 1,
    passed: langOk,
    score: langOk ? 1 : 0,
    maxScore: 1,
    details: langOk
      ? `Langue détectée : "${crawl.meta.lang}".`
      : "Pas d'attribut lang sur ta page. Google ne sait pas dans quelle langue est ton site.",
    fix: langOk
      ? undefined
      : 'Ajoute lang="fr" sur la balise <html> de ton site.',
  });

  // Canonical
  const canonicalOk = crawl.meta.canonical !== null;
  checks.push({
    id: "canonical",
    label: "Quelle est la vraie adresse de ta page",
    level: 1,
    passed: canonicalOk,
    score: canonicalOk ? 2 : 0,
    maxScore: 2,
    details: canonicalOk
      ? `Canonical défini : ${crawl.meta.canonical}`
      : "Pas de balise canonical. Google pourrait indexer des doublons de ta page.",
    fix: canonicalOk
      ? undefined
      : "Ajoute une balise canonical dans le <head> de chaque page.",
  });

  return checks;
}
```

- [ ] **Step 5: Lancer les tests**

```bash
npm test -- tests/scoring/rules.test.ts
```

Expected: 3 tests passed.

- [ ] **Step 6: Écrire le test du calcul de score global**

Créer `tests/scoring/index.test.ts` :

```ts
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
```

- [ ] **Step 7: Implémenter calculateScore()**

Créer `lib/scoring/index.ts` :

```ts
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
```

- [ ] **Step 8: Lancer tous les tests scoring**

```bash
npm test -- tests/scoring/
```

Expected: 6 tests passed.

- [ ] **Step 9: Commit**

```bash
git add lib/scoring/ tests/scoring/
git commit -m "feat(scoring): moteur de score — règles niveau 1 + calcul global"
```

---

### Task 8: API Routes — Projets + Crawl

**Files:**
- Create: `app/api/projects/route.ts`
- Create: `app/api/crawl/route.ts`
- Create: `app/api/audit/[projectId]/route.ts`

- [ ] **Step 1: Créer l'API route projets (GET + POST)**

Créer `app/api/projects/route.ts` :

```ts
import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";

export async function GET() {
  const projects = await prisma.project.findMany({
    orderBy: { updatedAt: "desc" },
    include: {
      audits: {
        orderBy: { date: "desc" },
        take: 1,
      },
      tasks: {
        where: { status: "pending" },
      },
    },
  });

  const result = projects.map((p) => ({
    id: p.id,
    url: p.url,
    name: p.name,
    score: p.score,
    currentLevel: p.currentLevel,
    pendingTasks: p.tasks.length,
    lastAudit: p.audits[0]?.date ?? null,
    updatedAt: p.updatedAt,
  }));

  return NextResponse.json(result);
}

export async function POST(request: Request) {
  const body = await request.json();
  const { url, name, objective, theme, geoZone } = body;

  if (!url || !name) {
    return NextResponse.json(
      { error: "URL et nom sont requis" },
      { status: 400 }
    );
  }

  const project = await prisma.project.create({
    data: {
      url: url.replace(/\/$/, ""),
      name,
      objective: objective || "",
      theme: theme || "",
      geoZone: geoZone || "",
    },
  });

  return NextResponse.json(project, { status: 201 });
}
```

- [ ] **Step 2: Créer l'API route crawl (POST)**

Créer `app/api/crawl/route.ts` :

```ts
import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";
import { crawlSite } from "@/lib/crawler";
import { evaluateLevel1Rules } from "@/lib/scoring/rules";
import { calculateScore } from "@/lib/scoring";

export async function POST(request: Request) {
  const body = await request.json();
  const { projectId } = body;

  if (!projectId) {
    return NextResponse.json(
      { error: "projectId est requis" },
      { status: 400 }
    );
  }

  const project = await prisma.project.findUnique({
    where: { id: projectId },
  });

  if (!project) {
    return NextResponse.json(
      { error: "Projet non trouvé" },
      { status: 404 }
    );
  }

  // Crawl the site
  const crawlResult = await crawlSite(project.url);

  // Evaluate rules (Level 1 for now)
  const checks = evaluateLevel1Rules(crawlResult);
  const scoreBreakdown = calculateScore(checks);

  // Save audit
  const audit = await prisma.audit.create({
    data: {
      projectId: project.id,
      scoreBreakdown: JSON.stringify(scoreBreakdown),
      technicalChecks: JSON.stringify(checks),
      contentAnalysis: JSON.stringify({
        headings: crawlResult.headings,
        images: crawlResult.images,
        links: crawlResult.links,
        structuredData: crawlResult.structuredData,
      }),
    },
  });

  // Update project score
  await prisma.project.update({
    where: { id: project.id },
    data: {
      score: scoreBreakdown.total,
    },
  });

  // Generate tasks from failed checks
  const failedChecks = checks.filter((c) => !c.passed && c.fix);

  // Clear old pending tasks for this project (level 1)
  await prisma.task.deleteMany({
    where: {
      projectId: project.id,
      level: 1,
      status: "pending",
    },
  });

  // Create new tasks
  for (const check of failedChecks) {
    await prisma.task.create({
      data: {
        projectId: project.id,
        title: check.label,
        description: `${check.details}\n\n**Comment corriger :** ${check.fix}`,
        level: check.level,
        impact: check.maxScore >= 3 ? "high" : check.maxScore >= 2 ? "medium" : "low",
        difficulty: "easy",
      },
    });
  }

  return NextResponse.json({
    audit: {
      id: audit.id,
      date: audit.date,
      scoreBreakdown,
      checks,
    },
    score: scoreBreakdown.total,
    tasksGenerated: failedChecks.length,
  });
}
```

- [ ] **Step 3: Créer l'API route audit (GET dernier audit)**

Créer `app/api/audit/[projectId]/route.ts` :

```ts
import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";

export async function GET(
  _request: Request,
  { params }: { params: Promise<{ projectId: string }> }
) {
  const { projectId } = await params;

  const audit = await prisma.audit.findFirst({
    where: { projectId },
    orderBy: { date: "desc" },
  });

  if (!audit) {
    return NextResponse.json(
      { error: "Aucun audit trouvé pour ce projet" },
      { status: 404 }
    );
  }

  return NextResponse.json({
    id: audit.id,
    date: audit.date,
    scoreBreakdown: JSON.parse(audit.scoreBreakdown),
    technicalChecks: JSON.parse(audit.technicalChecks),
    contentAnalysis: JSON.parse(audit.contentAnalysis),
  });
}
```

- [ ] **Step 4: Tester manuellement avec curl**

Démarrer le serveur :
```bash
npm run dev &
```

Lister les projets :
```bash
curl http://localhost:3000/api/projects | jq
```

Expected: tableau avec les 4 sites seedés.

Lancer un crawl (remplacer `PROJECT_ID` par un ID retourné) :
```bash
curl -X POST http://localhost:3000/api/crawl \
  -H "Content-Type: application/json" \
  -d '{"projectId": "PROJECT_ID"}' | jq
```

Expected: un objet avec `audit`, `score`, `tasksGenerated`.

- [ ] **Step 5: Commit**

```bash
git add app/api/
git commit -m "feat(api): routes projets, crawl et audit — pipeline complet"
```

---

### Task 9: Page dashboard minimale (placeholder UI)

**Files:**
- Create: `app/dashboard/page.tsx`
- Modify: `app/page.tsx`

- [ ] **Step 1: Créer la page dashboard**

Créer `app/dashboard/page.tsx` :

```tsx
import Link from "next/link";
import { prisma } from "@/lib/db";

function ScoreBadge({ score }: { score: number }) {
  const color =
    score >= 70
      ? "bg-green-100 text-green-800"
      : score >= 40
        ? "bg-orange-100 text-orange-800"
        : "bg-red-100 text-red-800";

  return (
    <span className={`inline-flex items-center justify-center w-12 h-12 rounded-full text-lg font-bold ${color}`}>
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
        <h1 className="text-2xl font-bold">Mon Site Sur Google</h1>
        <span className="text-sm text-gray-500">Coach SEO & GSO</span>
      </div>

      <div className="grid gap-4">
        {projects.map((project) => (
          <Link
            key={project.id}
            href={`/project/${project.id}`}
            className="flex items-center gap-4 p-4 border rounded-lg hover:bg-gray-50 transition-colors"
          >
            <ScoreBadge score={project.score} />
            <div className="flex-1">
              <h2 className="font-semibold">{project.name}</h2>
              <p className="text-sm text-gray-500">{project.url}</p>
            </div>
            <div className="text-right text-sm">
              <p className="text-gray-500">
                {project.tasks.length} action{project.tasks.length !== 1 ? "s" : ""} en attente
              </p>
              {project.audits[0] && (
                <p className="text-gray-400">
                  Dernier audit : {new Date(project.audits[0].date).toLocaleDateString("fr-FR")}
                </p>
              )}
            </div>
          </Link>
        ))}
      </div>

      {projects.length === 0 && (
        <p className="text-center text-gray-400 py-12">
          Aucun site ajouté. Lance le seed pour commencer.
        </p>
      )}
    </main>
  );
}
```

- [ ] **Step 2: Rediriger l'accueil vers le dashboard**

Modifier `app/page.tsx` :

```tsx
import { redirect } from "next/navigation";

export default function Home() {
  redirect("/dashboard");
}
```

- [ ] **Step 3: Vérifier dans le navigateur**

```bash
npm run dev
```

Ouvrir `http://localhost:3000` — on doit voir les 4 sites avec un score de 0 chacun.

- [ ] **Step 4: Commit**

```bash
git add app/dashboard/ app/page.tsx
git commit -m "feat(ui): dashboard — liste des projets avec scores"
```

---

### Task 10: Page projet minimale + bouton "Analyser"

**Files:**
- Create: `app/project/[id]/page.tsx`
- Create: `app/project/[id]/CrawlButton.tsx`

- [ ] **Step 1: Créer le composant bouton crawl (client)**

Créer `app/project/[id]/CrawlButton.tsx` :

```tsx
"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

export default function CrawlButton({ projectId }: { projectId: string }) {
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<{ score: number; tasksGenerated: number } | null>(null);
  const router = useRouter();

  async function handleCrawl() {
    setLoading(true);
    setResult(null);

    try {
      const res = await fetch("/api/crawl", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ projectId }),
      });

      const data = await res.json();
      setResult({ score: data.score, tasksGenerated: data.tasksGenerated });
      router.refresh();
    } catch (error) {
      console.error("Erreur lors du crawl:", error);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div>
      <button
        onClick={handleCrawl}
        disabled={loading}
        className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {loading ? "Analyse en cours..." : "Analyser mon site"}
      </button>

      {result && (
        <div className="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
          <p className="font-semibold">Analyse terminée !</p>
          <p>Score : {result.score}/100</p>
          <p>{result.tasksGenerated} action(s) à faire</p>
        </div>
      )}
    </div>
  );
}
```

- [ ] **Step 2: Créer la page projet**

Créer `app/project/[id]/page.tsx` :

```tsx
import Link from "next/link";
import { notFound } from "next/navigation";
import { prisma } from "@/lib/db";
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
      tasks: { where: { status: "pending" }, orderBy: { impact: "asc" } },
    },
  });

  if (!project) notFound();

  const lastAudit = project.audits[0];
  const checks = lastAudit
    ? JSON.parse(lastAudit.technicalChecks)
    : [];

  return (
    <main className="max-w-4xl mx-auto p-8">
      <Link href="/dashboard" className="text-blue-600 hover:underline text-sm">
        &larr; Retour au dashboard
      </Link>

      <div className="mt-4 mb-8">
        <h1 className="text-2xl font-bold">{project.name}</h1>
        <p className="text-gray-500">{project.url}</p>
        <p className="text-3xl font-bold mt-2">
          Score : <span className={project.score >= 70 ? "text-green-600" : project.score >= 40 ? "text-orange-500" : "text-red-500"}>{project.score}</span>/100
        </p>
      </div>

      <CrawlButton projectId={project.id} />

      {checks.length > 0 && (
        <div className="mt-8">
          <h2 className="text-xl font-semibold mb-4">Niveau 1 — Les fondations</h2>
          <div className="space-y-2">
            {checks.map((check: { id: string; label: string; passed: boolean; score: number; maxScore: number; details: string; fix?: string }) => (
              <div
                key={check.id}
                className={`p-3 rounded-lg border ${check.passed ? "bg-green-50 border-green-200" : "bg-red-50 border-red-200"}`}
              >
                <div className="flex items-center gap-2">
                  <span>{check.passed ? "✅" : "❌"}</span>
                  <span className="font-medium">{check.label}</span>
                  <span className="text-sm text-gray-500 ml-auto">{check.score}/{check.maxScore}</span>
                </div>
                <p className="text-sm text-gray-600 mt-1">{check.details}</p>
                {check.fix && (
                  <p className="text-sm text-blue-700 mt-1">💡 {check.fix}</p>
                )}
              </div>
            ))}
          </div>
        </div>
      )}

      {project.tasks.length > 0 && (
        <div className="mt-8">
          <h2 className="text-xl font-semibold mb-4">Plan d'action</h2>
          <div className="space-y-2">
            {project.tasks.map((task) => (
              <div key={task.id} className="p-3 border rounded-lg">
                <p className="font-medium">{task.title}</p>
                <p className="text-sm text-gray-600 mt-1 whitespace-pre-line">{task.description}</p>
              </div>
            ))}
          </div>
        </div>
      )}
    </main>
  );
}
```

- [ ] **Step 3: Tester dans le navigateur**

```bash
npm run dev
```

1. Ouvrir `http://localhost:3000` → voir le dashboard avec les 4 sites
2. Cliquer sur "Villa Plaisance" → voir la page projet
3. Cliquer sur "Analyser mon site" → attendre le crawl → voir le score, les checks et le plan d'action

- [ ] **Step 4: Commit**

```bash
git add app/project/
git commit -m "feat(ui): page projet — analyse, score, checks niveau 1, plan d'action"
```

---

## Récapitulatif

À la fin du Plan 1, l'application permet de :

1. ✅ Voir tous ses projets dans un dashboard avec scores
2. ✅ Cliquer sur un projet et lancer une analyse (crawl)
3. ✅ Voir le score 0-100 et le détail des vérifications Niveau 1
4. ✅ Voir un plan d'action avec les corrections à faire
5. ✅ Les explications sont en français simple (pas de jargon)

**Prochaine étape : Plan 2 — Interface** (onboarding, navigation inter-projets, niveaux 2-5, UI complète)
