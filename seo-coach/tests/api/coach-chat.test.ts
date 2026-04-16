// @vitest-environment node
import { describe, it, expect } from "vitest";
import { prisma } from "@/lib/db";

describe("coach/chat API helpers", () => {
  it("le projet de test existe en base", async () => {
    const project = await prisma.project.findFirst();
    expect(project).not.toBeNull();
    expect(project?.name).toBeTruthy();
  });

  it("le projet a bien les champs nécessaires au prompt", async () => {
    const project = await prisma.project.findFirst();
    if (!project) return;
    expect(typeof project.score).toBe("number");
    expect(typeof project.currentLevel).toBe("number");
    expect(project.url).toBeTruthy();
  });
});
