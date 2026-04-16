import * as cheerio from "cheerio";
import type { HeadingData } from "../types";

export function parseHeadings(html: string): HeadingData {
  const $ = cheerio.load(html);

  const extract = (tag: string): string[] =>
    $(tag)
      .map((_, el) => $(el).text().trim())
      .get();

  const h1 = extract("h1");
  const h2 = extract("h2");
  const h3 = extract("h3");
  const h4 = extract("h4");
  const h5 = extract("h5");
  const h6 = extract("h6");

  const hasMultipleH1 = h1.length > 1;
  const hierarchyValid =
    h1.length === 1 && !(h3.length > 0 && h2.length === 0);

  return {
    h1,
    h2,
    h3,
    h4,
    h5,
    h6,
    h1Count: h1.length,
    hasMultipleH1,
    hierarchyValid,
  };
}
