import { prisma } from "@/lib/db";
import { notFound } from "next/navigation";
import Link from "next/link";
import CrawlButton from "./CrawlButton";

export default async function ProjectPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  const project = await prisma.project.findUnique({
    where: { id },
    include: {
      audits: { orderBy: { date: "desc" }, take: 1 },
      tasks: { where: { status: "pending" } },
    },
  });

  if (!project) notFound();

  const lastAudit = project.audits[0];

  return (
    <main className="p-8 max-w-2xl">
      <div className="mb-8">
        <h1 className="text-2xl font-bold">{project.name}</h1>
        <p className="text-gray-500 text-sm">{project.url}</p>
      </div>

      <div className="grid grid-cols-3 gap-4 mb-8">
        <div className="border rounded-lg p-4 text-center">
          <p className="text-3xl font-bold text-blue-600">{project.score}</p>
          <p className="text-xs text-gray-500 mt-1">Score global</p>
        </div>
        <div className="border rounded-lg p-4 text-center">
          <p className="text-3xl font-bold">{project.tasks.length}</p>
          <p className="text-xs text-gray-500 mt-1">Actions en attente</p>
        </div>
        <div className="border rounded-lg p-4 text-center">
          <p className="text-3xl font-bold">Niv. {project.currentLevel}</p>
          <p className="text-xs text-gray-500 mt-1">Niveau actuel</p>
        </div>
      </div>

      <div className="mb-8">
        <CrawlButton projectId={project.id} />
        {lastAudit && (
          <p className="text-xs text-gray-400 mt-2">
            Dernier audit : {new Date(lastAudit.date).toLocaleDateString("fr-FR")}
          </p>
        )}
      </div>

      <div className="space-y-2">
        <Link
          href={`/project/${id}/level/1`}
          className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition-colors"
        >
          <span className="font-medium">Niveau 1 — Les fondations</span>
          <span className="text-gray-400">→</span>
        </Link>
        <Link
          href={`/project/${id}/plan`}
          className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition-colors"
        >
          <span className="font-medium">📋 Plan d'action ({project.tasks.length} actions)</span>
          <span className="text-gray-400">→</span>
        </Link>
      </div>
    </main>
  );
}
