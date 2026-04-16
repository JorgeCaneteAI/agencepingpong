import { notFound } from "next/navigation";
import { prisma } from "@/lib/db";
import TaskCheckbox from "./TaskCheckbox";

type Impact = "high" | "medium" | "low";
type Difficulty = "easy" | "medium" | "hard";

interface Task {
  id: string;
  title: string;
  description: string;
  level: number;
  impact: Impact;
  difficulty: Difficulty;
  status: string;
}

const PRIORITY_GROUPS = [
  {
    key: "quick-wins",
    emoji: "🔥",
    label: "Quick wins — Impact fort, facile à faire",
    filter: (t: Task) => t.impact === "high" && t.difficulty === "easy",
  },
  {
    key: "high-impact",
    emoji: "💪",
    label: "Impact fort — Plus de travail",
    filter: (t: Task) => t.impact === "high" && t.difficulty !== "easy",
  },
  {
    key: "medium-easy",
    emoji: "📌",
    label: "Impact moyen — Facile à faire",
    filter: (t: Task) => t.impact === "medium" && t.difficulty === "easy",
  },
  {
    key: "rest",
    emoji: "📁",
    label: "Les autres",
    filter: (t: Task) =>
      !(
        (t.impact === "high") ||
        (t.impact === "medium" && t.difficulty === "easy")
      ),
  },
];

const IMPACT_LABELS: Record<Impact, string> = {
  high: "Impact fort",
  medium: "Impact moyen",
  low: "Impact faible",
};

const DIFFICULTY_LABELS: Record<Difficulty, string> = {
  easy: "Facile",
  medium: "Moyen",
  hard: "Difficile",
};

export default async function PlanPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  const project = await prisma.project.findUnique({
    where: { id },
    include: {
      tasks: {
        where: { status: { in: ["pending", "done"] } },
        orderBy: { createdAt: "asc" },
      },
    },
  });

  if (!project) notFound();

  const pending = project.tasks.filter((t) => t.status === "pending") as Task[];
  const done = project.tasks.filter((t) => t.status === "done") as Task[];

  return (
    <main className="p-8 max-w-2xl">
      <div className="mb-6">
        <h1 className="text-2xl font-bold">Plan d'action</h1>
        <p className="text-gray-500 text-sm mt-1">
          {pending.length} action{pending.length !== 1 ? "s" : ""} à faire
          {done.length > 0 && ` · ${done.length} terminée${done.length !== 1 ? "s" : ""}`}
        </p>
      </div>

      {pending.length === 0 && done.length === 0 && (
        <div className="text-center py-12 text-gray-400">
          <p className="text-4xl mb-3">✨</p>
          <p>Lance une analyse depuis la vue d'ensemble pour générer ton plan d'action.</p>
        </div>
      )}

      {PRIORITY_GROUPS.map((group) => {
        const tasks = pending.filter(group.filter);
        if (tasks.length === 0) return null;

        return (
          <div key={group.key} className="mb-8">
            <h2 className="text-sm font-semibold text-gray-500 uppercase mb-3">
              {group.emoji} {group.label}
            </h2>
            <div className="space-y-2">
              {tasks.map((task) => (
                <div key={task.id} className="flex gap-3 p-4 border rounded-xl hover:bg-gray-50">
                  <TaskCheckbox taskId={task.id} initialStatus={task.status} />
                  <div className="flex-1 min-w-0">
                    <p className="font-medium">{task.title}</p>
                    <p className="text-sm text-gray-600 mt-1 whitespace-pre-line">
                      {task.description}
                    </p>
                    <div className="flex gap-2 mt-2">
                      <span className="text-xs bg-gray-100 px-2 py-0.5 rounded">
                        {IMPACT_LABELS[task.impact as Impact] ?? task.impact}
                      </span>
                      <span className="text-xs bg-gray-100 px-2 py-0.5 rounded">
                        {DIFFICULTY_LABELS[task.difficulty as Difficulty] ?? task.difficulty}
                      </span>
                      <span className="text-xs bg-gray-100 px-2 py-0.5 rounded">
                        Niveau {task.level}
                      </span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        );
      })}

      {done.length > 0 && (
        <div className="mb-8">
          <h2 className="text-sm font-semibold text-gray-400 uppercase mb-3">
            ✅ Terminées
          </h2>
          <div className="space-y-2 opacity-60">
            {done.map((task) => (
              <div key={task.id} className="flex gap-3 p-4 border rounded-xl">
                <TaskCheckbox taskId={task.id} initialStatus={task.status} />
                <p className="font-medium text-gray-400 line-through">{task.title}</p>
              </div>
            ))}
          </div>
        </div>
      )}
    </main>
  );
}
