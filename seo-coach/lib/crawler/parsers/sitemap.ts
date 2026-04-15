import type { SitemapData, RobotsData } from "../types";

export async function checkSitemap(baseUrl: string): Promise<SitemapData> {
  const sitemapUrl = `${baseUrl.replace(/\/$/, "")}/sitemap.xml`;

  try {
    const response = await fetch(sitemapUrl, {
      signal: AbortSignal.timeout(5000),
    });

    if (!response.ok) {
      return { exists: false, url: sitemapUrl, urlCount: 0 };
    }

    const text = await response.text();
    const urlCount = (text.match(/<loc>/g) || []).length;

    return { exists: true, url: sitemapUrl, urlCount };
  } catch {
    return { exists: false, url: sitemapUrl, urlCount: 0 };
  }
}

export async function checkRobots(baseUrl: string): Promise<RobotsData> {
  const robotsUrl = `${baseUrl.replace(/\/$/, "")}/robots.txt`;

  try {
    const response = await fetch(robotsUrl, {
      signal: AbortSignal.timeout(5000),
    });

    if (!response.ok) {
      return { exists: false, content: null, blocksImportantPaths: false };
    }

    const content = await response.text();
    const blocksImportantPaths =
      content.includes("Disallow: /") &&
      !content.includes("Disallow: /admin") &&
      content.split("Disallow: /").length > 2;

    return { exists: true, content, blocksImportantPaths };
  } catch {
    return { exists: false, content: null, blocksImportantPaths: false };
  }
}
