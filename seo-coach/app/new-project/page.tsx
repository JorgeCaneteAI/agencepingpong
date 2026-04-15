"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

interface FormData {
  url: string;
  name: string;
  objective: string;
  theme: string;
  geoZone: string;
}

type Step = "questions" | "diagnostic" | "done";

interface DiagnosticResult {
  projectId: string;
  score: number;
  tasksGenerated: number;
  crawlOk: boolean;
  title: string | null;
  metaDescription: string | null;
  isHttps: boolean;
  hasSitemap: boolean;
  geoFound: boolean;
}

export default function NewProjectPage() {
  const router = useRouter();
  const [step, setStep] = useState<Step>("questions");
  const [formData, setFormData] = useState<FormData>({
    url: "",
    name: "",
    objective: "",
    theme: "",
    geoZone: "",
  });
  const [loading, setLoading] = useState(false);
  const [diagnostic, setDiagnostic] = useState<DiagnosticResult | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmitQuestions(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      // Step 1: Create project
      const projectRes = await fetch("/api/projects", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData),
      });

      if (!projectRes.ok) {
        throw new Error("Impossible de créer le projet");
      }

      const project = await projectRes.json();

      // Step 2: Run crawl
      setStep("diagnostic");
      const crawlRes = await fetch("/api/crawl", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ projectId: project.id }),
      });

      const crawlData = await crawlRes.json();

      // Step 3: Parse diagnostic
      const checks = crawlData.audit?.checks ?? [];
      const titleCheck = checks.find((c: { id: string }) => c.id === "title");
      const descCheck = checks.find((c: { id: string }) => c.id === "meta-description");
      const httpsCheck = checks.find((c: { id: string }) => c.id === "https");
      const sitemapCheck = checks.find((c: { id: string }) => c.id === "sitemap");

      // Check if geoZone appears in title or description
      const titleText = titleCheck?.details ?? "";
      const descText = descCheck?.details ?? "";
      const geoLower = formData.geoZone.toLowerCase();
      const geoFound =
        geoLower.length > 2 &&
        (titleText.toLowerCase().includes(geoLower) ||
          descText.toLowerCase().includes(geoLower));

      setDiagnostic({
        projectId: project.id,
        score: crawlData.score ?? 0,
        tasksGenerated: crawlData.tasksGenerated ?? 0,
        crawlOk: crawlData.score !== undefined,
        title: titleCheck ? titleCheck.details.replace(/^Ton titre fait.*$/, "").trim() || null : null,
        metaDescription: descCheck ? (descCheck.passed ? "Présente" : "Absente") : "Non vérifiée",
        isHttps: httpsCheck?.passed ?? false,
        hasSitemap: sitemapCheck?.passed ?? false,
        geoFound,
      });
    } catch (err) {
      setError(err instanceof Error ? err.message : "Une erreur est survenue");
      setStep("questions");
    } finally {
      setLoading(false);
    }
  }

  function handleGoToProject() {
    if (diagnostic?.projectId) {
      router.push(`/project/${diagnostic.projectId}`);
    }
  }

  // STEP: QUESTIONS
  if (step === "questions") {
    return (
      <main className="max-w-xl mx-auto p-8">
        <div className="mb-8">
          <h1 className="text-2xl font-bold">Ajouter un site</h1>
          <p className="text-gray-500 mt-1">
            Réponds à ces questions simples — on s'occupe du reste.
          </p>
        </div>

        {error && (
          <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmitQuestions} className="space-y-6">
          <div>
            <label className="block text-sm font-medium mb-1">
              Adresse du site <span className="text-red-500">*</span>
            </label>
            <input
              type="url"
              required
              placeholder="https://monsite.fr"
              value={formData.url}
              onChange={(e) => setFormData((d) => ({ ...d, url: e.target.value }))}
              className="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">
              Nom du site ou de l'entreprise <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              required
              placeholder="Villa Plaisance"
              value={formData.name}
              onChange={(e) => setFormData((d) => ({ ...d, name: e.target.value }))}
              className="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">
              Qu'est-ce que tu veux que les gens fassent sur ton site ?
            </label>
            <input
              type="text"
              placeholder="Réserver une chambre, demander un devis, me contacter..."
              value={formData.objective}
              onChange={(e) => setFormData((d) => ({ ...d, objective: e.target.value }))}
              className="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">
              De quoi parle ton site ?
            </label>
            <input
              type="text"
              placeholder="Chambre d'hôtes, traiteur mariage, agence web..."
              value={formData.theme}
              onChange={(e) => setFormData((d) => ({ ...d, theme: e.target.value }))}
              className="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">
              Tu vises des clients où ?
            </label>
            <input
              type="text"
              placeholder="Nîmes, Gard, France entière..."
              value={formData.geoZone}
              onChange={(e) => setFormData((d) => ({ ...d, geoZone: e.target.value }))}
              className="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50"
          >
            {loading ? "Analyse en cours..." : "Analyser mon site →"}
          </button>
        </form>
      </main>
    );
  }

  // STEP: DIAGNOSTIC (loading or result)
  if (step === "diagnostic") {
    if (!diagnostic) {
      return (
        <main className="max-w-xl mx-auto p-8 text-center">
          <div className="animate-pulse">
            <div className="text-4xl mb-4">🔍</div>
            <h2 className="text-xl font-semibold mb-2">Analyse en cours...</h2>
            <p className="text-gray-500">
              On visite ton site et on vérifie tout. Ça prend 10 à 20 secondes.
            </p>
          </div>
        </main>
      );
    }

    const scoreColor =
      diagnostic.score >= 70
        ? "text-green-600"
        : diagnostic.score >= 40
          ? "text-orange-500"
          : "text-red-500";

    return (
      <main className="max-w-xl mx-auto p-8">
        <h2 className="text-2xl font-bold mb-2">Voici ce qu'on a trouvé</h2>
        <p className="text-gray-500 mb-6">
          On a comparé ce que tu nous as dit avec ce que Google voit sur ton site.
        </p>

        <div className="border rounded-xl overflow-hidden mb-6">
          <div className="bg-gray-50 px-4 py-2 grid grid-cols-2 text-xs font-semibold text-gray-500 uppercase">
            <span>Ce que tu nous as dit</span>
            <span>Ce qu'on a trouvé</span>
          </div>
          <div className="divide-y">
            <div className="px-4 py-3 grid grid-cols-2 gap-4 text-sm">
              <span className="text-gray-600">Objectif : {formData.objective || "—"}</span>
              <span>{diagnostic.isHttps ? "✅ Site sécurisé (HTTPS)" : "❌ Site non sécurisé"}</span>
            </div>
            <div className="px-4 py-3 grid grid-cols-2 gap-4 text-sm">
              <span className="text-gray-600">Secteur : {formData.theme || "—"}</span>
              <span>{diagnostic.title ? `Titre : "${diagnostic.title.slice(0, 40)}${diagnostic.title.length > 40 ? "…" : ""}"` : "❌ Pas de titre"}</span>
            </div>
            <div className="px-4 py-3 grid grid-cols-2 gap-4 text-sm">
              <span className="text-gray-600">Zone : {formData.geoZone || "—"}</span>
              <span>{diagnostic.geoFound ? `✅ "${formData.geoZone}" mentionné` : `⚠️ "${formData.geoZone}" non trouvé`}</span>
            </div>
            <div className="px-4 py-3 grid grid-cols-2 gap-4 text-sm">
              <span className="text-gray-600">—</span>
              <span>{diagnostic.hasSitemap ? "✅ Sitemap présent" : "❌ Pas de sitemap"}</span>
            </div>
          </div>
        </div>

        <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 text-center">
          <p className="text-sm text-blue-700 mb-1">Score initial</p>
          <p className={`text-5xl font-bold ${scoreColor}`}>{diagnostic.score}</p>
          <p className="text-sm text-gray-500 mt-1">
            /100 — {diagnostic.tasksGenerated} action{diagnostic.tasksGenerated !== 1 ? "s" : ""} identifiée{diagnostic.tasksGenerated !== 1 ? "s" : ""}
          </p>
        </div>

        <button
          onClick={handleGoToProject}
          className="w-full py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700"
        >
          Voir mon programme →
        </button>
      </main>
    );
  }

  return null;
}
