import Link from "next/link";
import { notFound } from "next/navigation";
import { prisma } from "@/lib/db";

const LEVELS = [
  { num: 1, title: "Les fondations" },
  { num: 2, title: "Les mots-clés" },
  { num: 3, title: "Le contenu" },
  { num: 4, title: "L'autorité" },
  { num: 5, title: "Présence IA (GSO)" },
];

function ScoreRing({ score }: { score: number }) {
  const color =
    score >= 70 ? "text-green-600" : score >= 40 ? "text-orange-500" : "text-red-500";
  return (
    <div className="text-center py-4">
      <span className={`text-4xl font-bold ${color}`}>{score}</span>
      <span className="text-gray-400 text-lg">/100</span>
    </div>
  );
}

export default async function ProjectLayout({
  children,
  params,
}: {
  children: React.ReactNode;
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  const project = await prisma.project.findUnique({
    where: { id },
    select: { id: true, name: true, url: true, score: true, currentLevel: true },
  });

  if (!project) notFound();

  return (
    <div className="flex min-h-screen">
      {/* Sidebar */}
      <aside className="w-64 border-r bg-gray-50 flex flex-col shrink-0">
        <div className="p-4 border-b">
          <Link href="/dashboard" className="text-xs text-gray-500 hover:text-gray-700">
            ← Tous les sites
          </Link>
          <h2 className="font-semibold mt-1 truncate">{project.name}</h2>
          <p className="text-xs text-gray-400 truncate">{project.url}</p>
        </div>

        <ScoreRing score={project.score} />

        <nav className="flex-1 p-2 space-y-1">
          <Link
            href={`/project/${id}`}
            className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm hover:bg-white hover:shadow-sm transition-all"
          >
            <span>📊</span>
            <span>Vue d'ensemble</span>
          </Link>

          <div className="pt-2 pb-1 px-3 text-xs font-semibold text-gray-400 uppercase">
            Niveaux
          </div>

          {LEVELS.map((level) => {
            const isUnlocked = level.num <= project.currentLevel;
            return (
              <div key={level.num}>
                {isUnlocked ? (
                  <Link
                    href={`/project/${id}/level/${level.num}`}
                    className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm hover:bg-white hover:shadow-sm transition-all"
                  >
                    <span className="w-5 h-5 rounded-full bg-blue-100 text-blue-700 text-xs flex items-center justify-center font-bold">
                      {level.num}
                    </span>
                    <span>{level.title}</span>
                  </Link>
                ) : (
                  <div className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-300 cursor-not-allowed">
                    <span className="w-5 h-5 rounded-full bg-gray-100 text-gray-300 text-xs flex items-center justify-center font-bold">
                      {level.num}
                    </span>
                    <span>{level.title}</span>
                    <span className="ml-auto text-xs">🔒</span>
                  </div>
                )}
              </div>
            );
          })}

          <div className="pt-2">
            <Link
              href={`/project/${id}/plan`}
              className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm hover:bg-white hover:shadow-sm transition-all"
            >
              <span>📋</span>
              <span>Plan d'action</span>
            </Link>
          </div>

          <div className="opacity-40 cursor-not-allowed">
            <div className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm">
              <span>🤖</span>
              <span>Coach IA</span>
              <span className="ml-auto text-xs bg-gray-200 px-1.5 py-0.5 rounded">Bientôt</span>
            </div>
          </div>
        </nav>
      </aside>

      {/* Main content */}
      <div className="flex-1 overflow-auto">
        {children}
      </div>
    </div>
  );
}
