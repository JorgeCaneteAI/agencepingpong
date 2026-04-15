import Link from "next/link";
import { notFound } from "next/navigation";
import { prisma } from "@/lib/db";
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
      tasks: { where: { status: "pending" }, orderBy: { impact: "asc" } },
    },
  });

  if (!project) notFound();

  const lastAudit = project.audits[0];
  const checks = lastAudit
    ? JSON.parse(lastAudit.technicalChecks)
    : [];

  return (
    <main className="max-w-4xl mx-auto p-8">
      <Link href="/dashboard" className="text-blue-600 hover:underline text-sm">
        &larr; Retour au dashboard
      </Link>

      <div className="mt-4 mb-8">
        <h1 className="text-2xl font-bold">{project.name}</h1>
        <p className="text-gray-500">{project.url}</p>
        <p className="text-3xl font-bold mt-2">
          Score : <span className={project.score >= 70 ? "text-green-600" : project.score >= 40 ? "text-orange-500" : "text-red-500"}>{project.score}</span>/100
        </p>
      </div>

      <CrawlButton projectId={project.id} />

      {checks.length > 0 && (
        <div className="mt-8">
          <h2 className="text-xl font-semibold mb-4">Niveau 1 — Les fondations</h2>
          <div className="space-y-2">
            {checks.map((check: { id: string; label: string; passed: boolean; score: number; maxScore: number; details: string; fix?: string }) => (
              <div
                key={check.id}
                className={`p-3 rounded-lg border ${check.passed ? "bg-green-50 border-green-200" : "bg-red-50 border-red-200"}`}
              >
                <div className="flex items-center gap-2">
                  <span>{check.passed ? "✅" : "❌"}</span>
                  <span className="font-medium">{check.label}</span>
                  <span className="text-sm text-gray-500 ml-auto">{check.score}/{check.maxScore}</span>
                </div>
                <p className="text-sm text-gray-600 mt-1">{check.details}</p>
                {check.fix && (
                  <p className="text-sm text-blue-700 mt-1">💡 {check.fix}</p>
                )}
              </div>
            ))}
          </div>
        </div>
      )}

      {project.tasks.length > 0 && (
        <div className="mt-8">
          <h2 className="text-xl font-semibold mb-4">Plan d'action</h2>
          <div className="space-y-2">
            {project.tasks.map((task) => (
              <div key={task.id} className="p-3 border rounded-lg">
                <p className="font-medium">{task.title}</p>
                <p className="text-sm text-gray-600 mt-1 whitespace-pre-line">{task.description}</p>
              </div>
            ))}
          </div>
        </div>
      )}
    </main>
  );
}
