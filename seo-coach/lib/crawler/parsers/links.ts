import * as cheerio from "cheerio";
import type { LinkData } from "../types";

export function parseLinks(html: string, baseUrl: string): LinkData {
  const $ = cheerio.load(html);
  const baseHost = new URL(baseUrl).hostname;

  const internal: LinkData["internal"] = [];
  const external: LinkData["external"] = [];

  $("a[href]").each((_, el) => {
    const href = $(el).attr("href") || "";
    const text = $(el).text().trim();

    if (!href || href.startsWith("#") || href.startsWith("mailto:") || href.startsWith("tel:")) {
      return;
    }

    try {
      const resolved = new URL(href, baseUrl);
      if (resolved.hostname === baseHost) {
        internal.push({ href, text });
      } else {
        external.push({ href, text });
      }
    } catch {
      internal.push({ href, text });
    }
  });

  return {
    internal,
    external,
    broken: [],
    internalCount: internal.length,
    externalCount: external.length,
    brokenCount: 0,
  };
}
