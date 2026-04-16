import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";
import { crawlSite } from "@/lib/crawler";
import { evaluateLevel1Rules } from "@/lib/scoring/rules";
import { calculateScore } from "@/lib/scoring";

export async function POST(request: Request) {
  const body = await request.json();
  const { projectId } = body;

  if (!projectId) {
    return NextResponse.json(
      { error: "projectId est requis" },
      { status: 400 }
    );
  }

  const project = await prisma.project.findUnique({
    where: { id: projectId },
  });

  if (!project) {
    return NextResponse.json(
      { error: "Projet non trouvé" },
      { status: 404 }
    );
  }

  // Crawl the site
  const crawlResult = await crawlSite(project.url);

  // Evaluate rules (Level 1 for now)
  const checks = evaluateLevel1Rules(crawlResult);
  const scoreBreakdown = calculateScore(checks);

  // Save audit
  const audit = await prisma.audit.create({
    data: {
      projectId: project.id,
      scoreBreakdown: JSON.stringify(scoreBreakdown),
      technicalChecks: JSON.stringify(checks),
      contentAnalysis: JSON.stringify({
        headings: crawlResult.headings,
        images: crawlResult.images,
        links: crawlResult.links,
        structuredData: crawlResult.structuredData,
      }),
    },
  });

  // Update project score
  await prisma.project.update({
    where: { id: project.id },
    data: {
      score: scoreBreakdown.total,
    },
  });

  // Generate tasks from failed checks
  const failedChecks = checks.filter((c) => !c.passed && c.fix);

  // Clear old pending tasks for this project (level 1)
  await prisma.task.deleteMany({
    where: {
      projectId: project.id,
      level: 1,
      status: "pending",
    },
  });

  // Create new tasks
  for (const check of failedChecks) {
    await prisma.task.create({
      data: {
        projectId: project.id,
        title: check.label,
        description: `${check.details}\n\n**Comment corriger :** ${check.fix}`,
        level: check.level,
        impact: check.maxScore >= 3 ? "high" : check.maxScore >= 2 ? "medium" : "low",
        difficulty: "easy",
      },
    });
  }

  return NextResponse.json({
    audit: {
      id: audit.id,
      date: audit.date,
      scoreBreakdown,
      checks,
    },
    score: scoreBreakdown.total,
    tasksGenerated: failedChecks.length,
  });
}
