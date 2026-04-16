import { notFound } from "next/navigation";
import { prisma } from "@/lib/db";
import ChatInterface from "./ChatInterface";

export default async function CoachPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  const project = await prisma.project.findUnique({
    where: { id },
    select: { id: true, name: true, score: true, currentLevel: true },
  });

  if (!project) notFound();

  return (
    <div className="flex flex-col h-[calc(100vh-0px)]">
      <div className="border-b p-4 bg-white shrink-0">
        <h1 className="font-semibold">Coach IA</h1>
        <p className="text-xs text-gray-500 mt-0.5">
          Score actuel : {project.score}/100 · Niveau {project.currentLevel}
        </p>
      </div>
      <ChatInterface projectId={id} />
    </div>
  );
}
