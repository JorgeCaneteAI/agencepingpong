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
  }, 30000);

  it("should handle unreachable sites gracefully", async () => {
    const result = await crawlSite("https://this-does-not-exist-xyz-999.com");

    expect(result.httpStatus).toBe(0);
    expect(result.meta.title).toBeNull();
  }, 20000);
});
