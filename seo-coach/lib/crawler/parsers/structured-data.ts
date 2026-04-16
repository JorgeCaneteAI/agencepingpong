import * as cheerio from "cheerio";
import type { StructuredDataItem } from "../types";

export function parseStructuredData(html: string): StructuredDataItem[] {
  const $ = cheerio.load(html);
  const items: StructuredDataItem[] = [];

  $('script[type="application/ld+json"]').each((_, el) => {
    try {
      const raw = JSON.parse($(el).html() || "");
      const type = raw["@type"] || "Unknown";
      items.push({ type, raw });
    } catch {
      // Invalid JSON-LD, skip
    }
  });

  return items;
}
