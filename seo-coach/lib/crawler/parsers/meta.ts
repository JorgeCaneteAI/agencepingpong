import * as cheerio from "cheerio";
import type { MetaData } from "../types";

export function parseMeta(html: string): MetaData {
  const $ = cheerio.load(html);

  const title = $("title").first().text().trim() || null;
  const metaDescription =
    $('meta[name="description"]').attr("content")?.trim() || null;
  const canonical = $('link[rel="canonical"]').attr("href")?.trim() || null;
  const metaRobots =
    $('meta[name="robots"]').attr("content")?.trim() || null;
  const ogTitle =
    $('meta[property="og:title"]').attr("content")?.trim() || null;
  const ogDescription =
    $('meta[property="og:description"]').attr("content")?.trim() || null;
  const ogImage =
    $('meta[property="og:image"]').attr("content")?.trim() || null;
  const lang = $("html").attr("lang")?.trim() || null;

  return {
    title,
    titleLength: title?.length ?? 0,
    metaDescription,
    metaDescriptionLength: metaDescription?.length ?? 0,
    canonical,
    metaRobots,
    ogTitle,
    ogDescription,
    ogImage,
    lang,
  };
}
