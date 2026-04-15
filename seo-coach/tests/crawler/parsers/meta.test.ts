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
    expect(result.titleLength).toBe(39);
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
