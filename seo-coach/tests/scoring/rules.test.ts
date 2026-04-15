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
