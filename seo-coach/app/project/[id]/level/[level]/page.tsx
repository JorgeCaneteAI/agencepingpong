import { notFound } from "next/navigation";
import { prisma } from "@/lib/db";

const LEVEL_TITLES: Record<number, string> = {
  1: "Les fondations",
  2: "Les mots-clés",
  3: "Le contenu",
  4: "L'autorité",
  5: "Présence IA (GSO)",
};

interface Check {
  id: string;
  label: string;
  passed: boolean;
  score: number;
  maxScore: number;
  details: string;
  fix?: string;
  level: number;
}

function CheckIcon({ passed }: { passed: boolean }) {
  if (passed) return <span className="text-green-500 text-xl">✅</span>;
  return <span className="text-red-500 text-xl">❌</span>;
}

function ScoreBar({ score, maxScore }: { score: number; maxScore: number }) {
  const pct = maxScore > 0 ? Math.round((score / maxScore) * 100) : 0;
  return (
    <div className="flex items-center gap-2 mt-2">
      <div className="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
        <div
          className={`h-full rounded-full ${pct >= 70 ? "bg-green-500" : pct >= 40 ? "bg-orange-400" : "bg-red-400"}`}
          style={{ width: `${pct}%` }}
        />
      </div>
      <span className="text-xs text-gray-500 shrink-0">{score}/{maxScore}</span>
    </div>
  );
}

export default async function LevelPage({
  params,
}: {
  params: Promise<{ id: string; level: string }>;
}) {
  const { id, level } = await params;
  const levelNum = parseInt(level, 10);

  if (isNaN(levelNum) || levelNum < 1 || levelNum > 5) notFound();

  const project = await prisma.project.findUnique({
    where: { id },
    include: {
      audits: { orderBy: { date: "desc" }, take: 1 },
    },
  });

  if (!project) notFound();

  const lastAudit = project.audits[0];
  const allChecks: Check[] = lastAudit
    ? JSON.parse(lastAudit.technicalChecks)
    : [];

  const checks = allChecks.filter((c) => c.level === levelNum);
  const levelScore = checks.reduce((sum, c) => sum + c.score, 0);
  const levelMaxScore = checks.reduce((sum, c) => sum + c.maxScore, 0);
  const passedCount = checks.filter((c) => c.passed).length;

  const title = LEVEL_TITLES[levelNum] ?? `Niveau ${levelNum}`;

  return (
    <main className="p-8 max-w-2xl">
      <div className="mb-6">
        <div className="flex items-center gap-3 mb-2">
          <span className="w-8 h-8 rounded-full bg-blue-100 text-blue-700 text-sm flex items-center justify-center font-bold">
            {levelNum}
          </span>
          <h1 className="text-2xl font-bold">Niveau {levelNum} — {title}</h1>
        </div>

        {checks.length > 0 && (
          <div className="flex items-center gap-4 text-sm text-gray-500">
            <span>{passedCount}/{checks.length} vérifications réussies</span>
            <span>Score : {levelScore}/{levelMaxScore}</span>
          </div>
        )}
      </div>

      {checks.length === 0 && (
        <div className="text-center py-12 text-gray-400">
          <p className="text-4xl mb-3">📭</p>
          <p>Lance une analyse depuis la vue d'ensemble pour voir les résultats.</p>
        </div>
      )}

      <div className="space-y-3">
        {checks.map((check) => (
          <div
            key={check.id}
            className={`p-4 rounded-xl border ${
              check.passed
                ? "bg-green-50 border-green-200"
                : "bg-red-50 border-red-200"
            }`}
          >
            <div className="flex items-start gap-3">
              <CheckIcon passed={check.passed} />
              <div className="flex-1 min-w-0">
                <p className="font-medium">{check.label}</p>
                <p className="text-sm text-gray-600 mt-1">{check.details}</p>
                {check.fix && (
                  <div className="mt-2 p-2 bg-white rounded-lg border border-blue-100">
                    <p className="text-sm text-blue-700">
                      <span className="font-medium">💡 Comment corriger : </span>
                      {check.fix}
                    </p>
                  </div>
                )}
                <ScoreBar score={check.score} maxScore={check.maxScore} />
              </div>
            </div>
          </div>
        ))}
      </div>
    </main>
  );
}
