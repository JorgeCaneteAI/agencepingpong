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
