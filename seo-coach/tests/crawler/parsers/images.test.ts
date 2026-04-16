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
