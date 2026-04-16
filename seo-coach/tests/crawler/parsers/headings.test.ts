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
