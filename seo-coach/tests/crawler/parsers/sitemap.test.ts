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
