import type { SecurityData } from "../types";

export function parseSecurity(url: string): SecurityData {
  const isHttps = url.startsWith("https://");
  return {
    isHttps,
    hasValidCert: isHttps,
  };
}
