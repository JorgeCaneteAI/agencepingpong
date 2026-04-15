"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

export default function TaskCheckbox({
  taskId,
  initialStatus,
}: {
  taskId: string;
  initialStatus: string;
}) {
  const router = useRouter();
  const [done, setDone] = useState(initialStatus === "done");
  const [loading, setLoading] = useState(false);

  async function toggle() {
    setLoading(true);
    const newStatus = done ? "pending" : "done";

    await fetch(`/api/tasks/${taskId}`, {
      method: "PATCH",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ status: newStatus }),
    });

    setDone(!done);
    setLoading(false);
    router.refresh();
  }

  return (
    <button
      onClick={toggle}
      disabled={loading}
      className={`w-6 h-6 rounded border-2 flex items-center justify-center shrink-0 transition-colors ${
        done
          ? "bg-green-500 border-green-500 text-white"
          : "border-gray-300 hover:border-green-400"
      }`}
      title={done ? "Marquer comme non fait" : "Marquer comme fait"}
    >
      {done && <span className="text-xs">✓</span>}
    </button>
  );
}
