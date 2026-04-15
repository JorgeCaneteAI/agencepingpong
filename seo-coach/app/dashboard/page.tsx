import Link from "next/link";
import { prisma } from "@/lib/db";

function ScoreBadge({ score }: { score: number }) {
  const color =
    score >= 70
      ? "bg-green-100 text-green-800"
      : score >= 40
        ? "bg-orange-100 text-orange-800"
        : "bg-red-100 text-red-800";

  return (
    <span className={`inline-flex items-center justify-center w-12 h-12 rounded-full text-lg font-bold ${color}`}>
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
        <h1 className="text-2xl font-bold">Mon Site Sur Google</h1>
        <span className="text-sm text-gray-500">Coach SEO & GSO</span>
      </div>

      <div className="grid gap-4">
        {projects.map((project) => (
          <Link
            key={project.id}
            href={`/project/${project.id}`}
            className="flex items-center gap-4 p-4 border rounded-lg hover:bg-gray-50 transition-colors"
          >
            <ScoreBadge score={project.score} />
            <div className="flex-1">
              <h2 className="font-semibold">{project.name}</h2>
              <p className="text-sm text-gray-500">{project.url}</p>
            </div>
            <div className="text-right text-sm">
              <p className="text-gray-500">
                {project.tasks.length} action{project.tasks.length !== 1 ? "s" : ""} en attente
              </p>
              {project.audits[0] && (
                <p className="text-gray-400">
                  Dernier audit : {new Date(project.audits[0].date).toLocaleDateString("fr-FR")}
                </p>
              )}
            </div>
          </Link>
        ))}
      </div>

      {projects.length === 0 && (
        <p className="text-center text-gray-400 py-12">
          Aucun site ajouté. Lance le seed pour commencer.
        </p>
      )}
    </main>
  );
}
