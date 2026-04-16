import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";
import { anthropic } from "@/lib/claude/client";
import { buildSystemPrompt } from "@/lib/claude/prompts";

interface Message {
  role: "user" | "assistant";
  content: string;
}

export async function POST(request: Request) {
  let body: unknown;
  try {
    body = await request.json();
  } catch {
    return NextResponse.json({ error: "Corps de requête invalide" }, { status: 400 });
  }

  const { projectId, message, history = [] } = body as {
    projectId?: string;
    message?: string;
    history?: Message[];
  };

  if (!projectId || !message) {
    return NextResponse.json(
      { error: "projectId et message sont requis" },
      { status: 400 }
    );
  }

  const project = await prisma.project.findUnique({
    where: { id: projectId },
    include: {
      audits: { orderBy: { date: "desc" }, take: 1 },
      tasks: { where: { status: "pending" }, orderBy: { createdAt: "asc" } },
    },
  });

  if (!project) {
    return NextResponse.json({ error: "Projet non trouvé" }, { status: 404 });
  }

  const systemPrompt = buildSystemPrompt(
    project,
    project.audits[0] ?? null,
    project.tasks
  );

  const messages: Message[] = [...history, { role: "user", content: message }];

  try {
    const response = await anthropic.messages.create({
      model: "claude-haiku-4-5-20251001",
      max_tokens: 1024,
      system: systemPrompt,
      messages,
    });

    const reply =
      response.content[0].type === "text" ? response.content[0].text : "";

    return NextResponse.json({ reply });
  } catch (error) {
    console.error("[coach/chat] Anthropic error:", error);
    return NextResponse.json(
      { error: "Le coach est temporairement indisponible" },
      { status: 503 }
    );
  }
}
