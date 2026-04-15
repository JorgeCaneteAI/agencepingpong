// @vitest-environment node
import { describe, it, expect } from "vitest";
import { prisma } from "@/lib/db";

describe("projects/:id API helpers", () => {
  it("should find a project by id", async () => {
    const project = await prisma.project.findFirst();
    expect(project).not.toBeNull();
    expect(project?.url).toBeTruthy();
  });

  it("should update a project's name", async () => {
    const project = await prisma.project.findFirst();
    if (!project) return;
    const updated = await prisma.project.update({
      where: { id: project.id },
      data: { name: "Test Update" },
    });
    expect(updated.name).toBe("Test Update");
    // Restore
    await prisma.project.update({
      where: { id: project.id },
      data: { name: project.name },
    });
  });
});
