import { NextResponse } from "next/server";
import { Prisma } from "@prisma/client";
import { prisma } from "@/lib/db";

export async function PATCH(
  request: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const { id } = await params;

  let body: unknown;
  try {
    body = await request.json();
  } catch {
    return NextResponse.json({ error: "Corps de requête invalide" }, { status: 400 });
  }
  const { status } = body as { status?: string };

  if (!["pending", "done", "skipped"].includes(status ?? "")) {
    return NextResponse.json({ error: "Statut invalide" }, { status: 400 });
  }

  try {
    const task = await prisma.task.update({
      where: { id },
      data: {
        status,
        completedAt: status === "done" ? new Date() : null,
      },
    });
    return NextResponse.json(task);
  } catch (e) {
    if (e instanceof Prisma.PrismaClientKnownRequestError && e.code === "P2025") {
      return NextResponse.json({ error: "Ressource non trouvée" }, { status: 404 });
    }
    throw e;
  }
}
