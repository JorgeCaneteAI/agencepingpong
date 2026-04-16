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
