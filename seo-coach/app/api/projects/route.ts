import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";

export async function GET() {
  const projects = await prisma.project.findMany({
    orderBy: { updatedAt: "desc" },
    include: {
      audits: {
        orderBy: { date: "desc" },
        take: 1,
      },
      tasks: {
        where: { status: "pending" },
      },
    },
  });

  const result = projects.map((p) => ({
    id: p.id,
    url: p.url,
    name: p.name,
    score: p.score,
    currentLevel: p.currentLevel,
    pendingTasks: p.tasks.length,
    lastAudit: p.audits[0]?.date ?? null,
    updatedAt: p.updatedAt,
  }));

  return NextResponse.json(result);
}

export async function POST(request: Request) {
  const body = await request.json();
  const { url, name, objective, theme, geoZone } = body;

  if (!url || !name) {
    return NextResponse.json(
      { error: "URL et nom sont requis" },
      { status: 400 }
    );
  }

  const project = await prisma.project.create({
    data: {
      url: url.replace(/\/$/, ""),
      name,
      objective: objective || "",
      theme: theme || "",
      geoZone: geoZone || "",
    },
  });

  return NextResponse.json(project, { status: 201 });
}
