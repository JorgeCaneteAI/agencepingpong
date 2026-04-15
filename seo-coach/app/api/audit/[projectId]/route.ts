import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";

export async function GET(
  _request: Request,
  { params }: { params: Promise<{ projectId: string }> }
) {
  const { projectId } = await params;

  const audit = await prisma.audit.findFirst({
    where: { projectId },
    orderBy: { date: "desc" },
  });

  if (!audit) {
    return NextResponse.json(
      { error: "Aucun audit trouvé pour ce projet" },
      { status: 404 }
    );
  }

  return NextResponse.json({
    id: audit.id,
    date: audit.date,
    scoreBreakdown: JSON.parse(audit.scoreBreakdown),
    technicalChecks: JSON.parse(audit.technicalChecks),
    contentAnalysis: JSON.parse(audit.contentAnalysis),
  });
}
