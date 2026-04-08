import type { Todo } from "@/lib/types";

/** En prod (`vite build`), défini dans `.env.production`. Vide en dev → proxy Vite. */
const API_ORIGIN = (import.meta.env.VITE_API_URL as string | undefined)?.replace(
  /\/$/,
  "",
) ?? "";

const BASE = `${API_ORIGIN}/api/todos`;

async function parseError(res: Response): Promise<string> {
  try {
    const body = await res.json();
    if (body?.error && typeof body.error === "string") return body.error;
    if (body?.errors && typeof body.errors === "object") {
      return Object.values(body.errors).flat().join(" ");
    }
  } catch {
    /* ignore */
  }
  return res.statusText || `Erreur ${res.status}`;
}

/** Réponse API (camelCase) — alignée sur `Todo` */
type TodoJson = Todo;

export async function fetchTodos(): Promise<Todo[]> {
  const res = await fetch(BASE);
  if (!res.ok) throw new Error(await parseError(res));
  return res.json() as Promise<TodoJson[]>;
}

export async function createTodo(
  payload: Omit<Todo, "id" | "createdAt">,
): Promise<Todo> {
  const res = await fetch(BASE, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
  if (!res.ok) throw new Error(await parseError(res));
  return res.json() as Promise<Todo>;
}

export async function updateTodo(
  id: string,
  payload: Omit<Todo, "id" | "createdAt">,
): Promise<Todo> {
  const res = await fetch(`${BASE}/${id}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
  if (!res.ok) throw new Error(await parseError(res));
  return res.json() as Promise<Todo>;
}

export async function deleteTodo(id: string): Promise<void> {
  const res = await fetch(`${BASE}/${id}`, { method: "DELETE" });
  if (!res.ok) throw new Error(await parseError(res));
}
