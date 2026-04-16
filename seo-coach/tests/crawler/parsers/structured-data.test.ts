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
