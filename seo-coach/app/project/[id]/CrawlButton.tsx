"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

export default function CrawlButton({ projectId }: { projectId: string }) {
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<{ score: number; tasksGenerated: number } | null>(null);
  const [error, setError] = useState<string | null>(null);
  const router = useRouter();

  async function handleCrawl() {
    setLoading(true);
    setResult(null);
    setError(null);

    try {
      const res = await fetch("/api/crawl", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ projectId }),
      });

      if (!res.ok) {
        setError("Erreur lors de l'analyse. Réessaie.");
        return;
      }
      const data = await res.json();
      setResult({ score: data.score, tasksGenerated: data.tasksGenerated });
      router.refresh();
    } catch (err) {
      console.error("Erreur lors du crawl:", err);
      setError("Erreur lors de l'analyse. Réessaie.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div>
      <button
        onClick={handleCrawl}
        disabled={loading}
        className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {loading ? "Analyse en cours..." : "Analyser mon site"}
      </button>

      {error && (
        <div className="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
          {error}
        </div>
      )}

      {result && (
        <div className="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
          <p className="font-semibold">Analyse terminée !</p>
          <p>Score : {result.score}/100</p>
          <p>{result.tasksGenerated} action(s) à faire</p>
        </div>
      )}
    </div>
  );
}
