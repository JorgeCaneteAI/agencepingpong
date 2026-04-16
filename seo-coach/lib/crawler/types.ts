export interface CrawlResult {
  url: string;
  fetchedAt: string;
  httpStatus: number;
  responseTimeMs: number;
  html: string;
  meta: MetaData;
  headings: HeadingData;
  images: ImageData[];
  links: LinkData;
  security: SecurityData;
  structuredData: StructuredDataItem[];
  sitemap: SitemapData;
  robots: RobotsData;
}

export interface MetaData {
  title: string | null;
  titleLength: number;
  metaDescription: string | null;
  metaDescriptionLength: number;
  canonical: string | null;
  metaRobots: string | null;
  ogTitle: string | null;
  ogDescription: string | null;
  ogImage: string | null;
  lang: string | null;
}

export interface HeadingData {
  h1: string[];
  h2: string[];
  h3: string[];
  h4: string[];
  h5: string[];
  h6: string[];
  h1Count: number;
  hasMultipleH1: boolean;
  hierarchyValid: boolean;
}

export interface ImageData {
  src: string;
  alt: string | null;
  hasAlt: boolean;
}

export interface LinkData {
  internal: { href: string; text: string; status?: number }[];
  external: { href: string; text: string; status?: number }[];
  broken: { href: string; text: string; status: number }[];
  internalCount: number;
  externalCount: number;
  brokenCount: number;
}

export interface SecurityData {
  isHttps: boolean;
  hasValidCert: boolean;
}

export interface StructuredDataItem {
  type: string;
  raw: Record<string, unknown>;
}

export interface SitemapData {
  exists: boolean;
  url: string | null;
  urlCount: number;
}

export interface RobotsData {
  exists: boolean;
  content: string | null;
  blocksImportantPaths: boolean;
}

export interface FetchResult {
  html: string;
  httpStatus: number;
  responseTimeMs: number;
  isHttps: boolean;
  finalUrl: string;
}
