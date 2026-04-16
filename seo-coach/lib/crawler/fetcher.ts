import type { FetchResult } from "./types";

const DEFAULT_TIMEOUT = 15000;
const USER_AGENT =
  "MonSiteSurGoogle/1.0 (SEO Audit Bot; +https://agencepingpong.fr)";

export async function fetchPage(
  url: string,
  timeout: number = DEFAULT_TIMEOUT
): Promise<FetchResult> {
  const start = Date.now();

  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);

    const response = await fetch(url, {
      headers: { "User-Agent": USER_AGENT },
      signal: controller.signal,
      redirect: "follow",
    });

    clearTimeout(timeoutId);

    const html = await response.text();
    const responseTimeMs = Date.now() - start;
    const isHttps = new URL(response.url).protocol === "https:";

    return {
      html,
      httpStatus: response.status,
      responseTimeMs,
      isHttps,
      finalUrl: response.url,
    };
  } catch (error) {
    return {
      html: "",
      httpStatus: 0,
      responseTimeMs: Date.now() - start,
      isHttps: false,
      finalUrl: url,
    };
  }
}
