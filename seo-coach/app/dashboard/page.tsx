import Link from "next/link";
import { prisma } from "@/lib/db";

const LEVEL_TITLES: Partial<Record<number, string>> = {
  1: "Les fondations",
  2: "Les mots-clés",
  3: "Le contenu",
  4: "L'autorité",
  5: "Présence IA (GSO)",
};

function ScoreBadge({ score }: { score: number }) {
  const color =
    score >= 70
      ? "bg-green-100 text-green-700 ring-green-200"
      : score >= 40
        ? "bg-orange-100 text-orange-700 ring-orange-200"
        : "bg-red-100 text-red-700 ring-red-200";

  return (
    <span
      className={`inline-flex items-center justify-center w-12 h-12 rounded-full text-lg font-bold ring-2 ${color}`}
    >
      {score}
    </span>
  );
}

export default async function DashboardPage() {
  const projects = await prisma.project.findMany({
    orderBy: { updatedAt: "desc" },
    include: {
      audits: { orderBy: { date: "desc" }, take: 1 },
      tasks: { where: { status: "pending" } },
    },
  });

  return (
    <main className="max-w-4xl mx-auto p-8">
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-2xl font-bold">Mon Site Sur Google</h1>
          <p className="text-sm text-gray-500">Coach SEO & GSO</p>
        </div>
        <Link
          href="/new-project"
          className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
        >
          + Ajouter un site
        </Link>
      </div>

      {projects.length === 0 && (
        <div className="text-center py-16 border-2 border-dashed border-gray-200 rounded-xl">
          <p className="text-4xl mb-3">🌐</p>
          <p className="text-gray-500 mb-4">Aucun site ajouté pour l'instant.</p>
          <Link
            href="/new-project"
            className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700"
          >
            Ajouter mon premier site
          </Link>
        </div>
      )}

      <div className="grid gap-3">
        {projects.map((project) => (
          <Link
            key={project.id}
            href={`/project/${project.id}`}
            className="flex items-center gap-4 p-4 border rounded-xl hover:bg-gray-50 hover:shadow-sm transition-all"
          >
            <ScoreBadge score={project.score} />

            <div className="flex-1 min-w-0">
              <h2 className="font-semibold truncate">{project.name}</h2>
              <p className="text-sm text-gray-400 truncate">{project.url}</p>
              <p className="text-xs text-blue-600 mt-0.5">
                Niveau {project.currentLevel} — {LEVEL_TITLES[project.currentLevel] ?? "..."}
              </p>
            </div>

            <div className="text-right text-sm shrink-0">
              {project.tasks.length > 0 ? (
                <p className="text-orange-600 font-medium">
                  {project.tasks.length} action{project.tasks.length !== 1 ? "s" : ""}
                </p>
              ) : (
                <p className="text-green-600">✓ À jour</p>
              )}
              {project.audits[0] ? (
                <p className="text-gray-400 text-xs mt-0.5">
                  {new Date(project.audits[0].date).toLocaleDateString("fr-FR")}
                </p>
              ) : (
                <p className="text-gray-300 text-xs mt-0.5">Pas encore analysé</p>
              )}
            </div>

            <span className="text-gray-300 shrink-0">→</span>
          </Link>
        ))}
      </div>
    </main>
  );
}
