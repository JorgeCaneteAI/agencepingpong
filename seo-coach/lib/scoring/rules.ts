import type { CrawlResult } from "../crawler/types";
import type { CheckResult } from "./types";

export function evaluateLevel1Rules(crawl: CrawlResult): CheckResult[] {
  const checks: CheckResult[] = [];

  // HTTPS
  checks.push({
    id: "https",
    label: "Ton site est sécurisé (HTTPS)",
    level: 1,
    passed: crawl.security.isHttps,
    score: crawl.security.isHttps ? 3 : 0,
    maxScore: 3,
    details: crawl.security.isHttps
      ? "Ton site utilise HTTPS — Google adore ça."
      : "Ton site n'est pas en HTTPS. Google pénalise les sites non sécurisés.",
    fix: crawl.security.isHttps
      ? undefined
      : "Active le certificat SSL chez ton hébergeur. C'est souvent gratuit (Let's Encrypt).",
  });

  // Title
  const titleOk =
    crawl.meta.title !== null &&
    crawl.meta.titleLength >= 30 &&
    crawl.meta.titleLength <= 65;
  checks.push({
    id: "title",
    label: "Tes pages ont un bon titre",
    level: 1,
    passed: titleOk,
    score: titleOk ? 3 : crawl.meta.title ? 1 : 0,
    maxScore: 3,
    details: crawl.meta.title
      ? `Ton titre fait ${crawl.meta.titleLength} caractères. L'idéal est entre 30 et 65.`
      : "Aucun titre trouvé sur ta page. C'est la première chose que Google lit !",
    fix: titleOk
      ? undefined
      : "Ajoute un titre descriptif avec ton mot-clé principal. Ex: 'Traiteur Mariage Nîmes — YelloEvent'",
  });

  // Meta description
  const descOk =
    crawl.meta.metaDescription !== null &&
    crawl.meta.metaDescriptionLength >= 120 &&
    crawl.meta.metaDescriptionLength <= 160;
  checks.push({
    id: "meta-description",
    label: "Le résumé qui apparaît dans Google",
    level: 1,
    passed: descOk,
    score: descOk ? 3 : crawl.meta.metaDescription ? 1 : 0,
    maxScore: 3,
    details: crawl.meta.metaDescription
      ? `Ta meta description fait ${crawl.meta.metaDescriptionLength} caractères. L'idéal est entre 120 et 160.`
      : "Pas de meta description. Google invente un résumé à ta place — souvent pas terrible.",
    fix: descOk
      ? undefined
      : "Écris un résumé accrocheur de 120-160 caractères qui donne envie de cliquer.",
  });

  // H1
  const h1Ok = crawl.headings.h1Count === 1;
  checks.push({
    id: "h1",
    label: "Ta page a un titre principal (H1)",
    level: 1,
    passed: h1Ok,
    score: h1Ok ? 2 : 0,
    maxScore: 2,
    details: h1Ok
      ? `Parfait : un seul H1 — "${crawl.headings.h1[0]}".`
      : crawl.headings.h1Count === 0
        ? "Aucun H1 trouvé. Le H1 est le titre principal de ta page."
        : `${crawl.headings.h1Count} H1 trouvés. Il ne doit y en avoir qu'un seul par page.`,
    fix: h1Ok
      ? undefined
      : "Ajoute un seul H1 par page avec ton sujet principal.",
  });

  // Sitemap
  checks.push({
    id: "sitemap",
    label: "Google peut trouver toutes tes pages (sitemap)",
    level: 1,
    passed: crawl.sitemap.exists,
    score: crawl.sitemap.exists ? 2 : 0,
    maxScore: 2,
    details: crawl.sitemap.exists
      ? `Sitemap trouvé avec ${crawl.sitemap.urlCount} URLs.`
      : "Pas de sitemap.xml. Google doit deviner quelles pages existent.",
    fix: crawl.sitemap.exists
      ? undefined
      : "Crée un fichier sitemap.xml à la racine de ton site avec la liste de tes pages.",
  });

  // Robots.txt
  checks.push({
    id: "robots",
    label: "Google sait quoi visiter (robots.txt)",
    level: 1,
    passed: crawl.robots.exists && !crawl.robots.blocksImportantPaths,
    score: crawl.robots.exists && !crawl.robots.blocksImportantPaths ? 2 : crawl.robots.exists ? 1 : 0,
    maxScore: 2,
    details: crawl.robots.exists
      ? crawl.robots.blocksImportantPaths
        ? "Attention : ton robots.txt bloque des pages importantes !"
        : "robots.txt en place et correct."
      : "Pas de robots.txt. Ce n'est pas bloquant mais c'est une bonne pratique.",
    fix: !crawl.robots.exists
      ? "Crée un fichier robots.txt à la racine de ton site."
      : crawl.robots.blocksImportantPaths
        ? "Vérifie ton robots.txt — il bloque des pages que Google devrait voir."
        : undefined,
  });

  // Response time
  const speedOk = crawl.responseTimeMs < 3000;
  checks.push({
    id: "speed",
    label: "Ton site répond vite",
    level: 1,
    passed: speedOk,
    score: speedOk ? 2 : crawl.responseTimeMs < 5000 ? 1 : 0,
    maxScore: 2,
    details: `Ton serveur répond en ${crawl.responseTimeMs}ms. ${speedOk ? "C'est rapide !" : "C'est trop lent — Google pénalise les sites lents."}`,
    fix: speedOk
      ? undefined
      : "Contacte ton hébergeur ou optimise ton site (images, cache, code).",
  });

  // Lang attribute
  const langOk = crawl.meta.lang !== null;
  checks.push({
    id: "lang",
    label: "Google sait dans quelle langue est ton site",
    level: 1,
    passed: langOk,
    score: langOk ? 1 : 0,
    maxScore: 1,
    details: langOk
      ? `Langue détectée : "${crawl.meta.lang}".`
      : "Pas d'attribut lang sur ta page. Google ne sait pas dans quelle langue est ton site.",
    fix: langOk
      ? undefined
      : 'Ajoute lang="fr" sur la balise <html> de ton site.',
  });

  // Canonical
  const canonicalOk = crawl.meta.canonical !== null;
  checks.push({
    id: "canonical",
    label: "Quelle est la vraie adresse de ta page",
    level: 1,
    passed: canonicalOk,
    score: canonicalOk ? 2 : 0,
    maxScore: 2,
    details: canonicalOk
      ? `Canonical défini : ${crawl.meta.canonical}`
      : "Pas de balise canonical. Google pourrait indexer des doublons de ta page.",
    fix: canonicalOk
      ? undefined
      : "Ajoute une balise canonical dans le <head> de chaque page.",
  });

  return checks;
}
