import * as cheerio from "cheerio";
import type { ImageData } from "../types";

export function parseImages(html: string): ImageData[] {
  const $ = cheerio.load(html);

  return $("img")
    .map((_, el) => {
      const src = $(el).attr("src") || "";
      const alt = $(el).attr("alt") ?? null;
      const hasAlt = alt !== null && alt.trim().length > 0;
      return { src, alt, hasAlt };
    })
    .get();
}
