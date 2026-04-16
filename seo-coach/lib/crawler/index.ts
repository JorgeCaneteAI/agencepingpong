import type { CrawlResult } from "./types";
import { fetchPage } from "./fetcher";
import { parseMeta } from "./parsers/meta";
import { parseHeadings } from "./parsers/headings";
import { parseImages } from "./parsers/images";
import { parseLinks } from "./parsers/links";
import { parseSecurity } from "./parsers/security";
import { parseStructuredData } from "./parsers/structured-data";
import { checkSitemap, checkRobots } from "./parsers/sitemap";

export async function crawlSite(url: string): Promise<CrawlResult> {
  const normalizedUrl = url.replace(/\/$/, "");
  const fetchResult = await fetchPage(normalizedUrl);

  const [sitemap, robots] = await Promise.all([
    checkSitemap(normalizedUrl),
    checkRobots(normalizedUrl),
  ]);

  return {
    url: normalizedUrl,
    fetchedAt: new Date().toISOString(),
    httpStatus: fetchResult.httpStatus,
    responseTimeMs: fetchResult.responseTimeMs,
    html: fetchResult.html,
    meta: parseMeta(fetchResult.html),
    headings: parseHeadings(fetchResult.html),
    images: parseImages(fetchResult.html),
    links: parseLinks(fetchResult.html, normalizedUrl),
    security: parseSecurity(normalizedUrl),
    structuredData: parseStructuredData(fetchResult.html),
    sitemap,
    robots,
  };
}

export type { CrawlResult } from "./types";
