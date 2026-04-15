import { describe, it, expect } from "vitest";
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
