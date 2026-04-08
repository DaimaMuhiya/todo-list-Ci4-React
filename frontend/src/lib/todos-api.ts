import type { Todo } from "@/lib/types";

/** En prod (`vite build`), défini dans `.env.production`. Vide en dev → proxy Vite. */
const API_ORIGIN = (import.meta.env.VITE_API_URL as string | undefined)?.replace(
  /\/$/,
  "",
) ?? "";

const BASE = `${API_ORIGIN}/api/todos`;

const API_ERROR_BODY_MAX = 4000;

/**
 * Lit le corps d’une réponse d’échec pour message utilisateur + diagnostic.
 * Journalise dans la console du navigateur (pas dans le terminal Vite) pour les 5xx / erreurs API.
 */
async function handleNotOkResponse(res: Response): Promise<never> {
  const text = await res.text();
  const preview =
    text.length > API_ERROR_BODY_MAX
      ? `${text.slice(0, API_ERROR_BODY_MAX)}…`
      : text;

  console.error("[todos-api]", res.status, res.url, preview || "(corps vide)");

  let message = res.statusText || `Erreur ${res.status}`;

  if (preview.trim() !== "") {
    try {
      const body = JSON.parse(text) as Record<string, unknown>;
      if (body?.error && typeof body.error === "string") {
        message = body.error;
      } else if (body?.errors && typeof body.errors === "object") {
        message = Object.values(body.errors as Record<string, string[]>).flat().join(" ");
      } else if (res.status >= 500) {
        message = `Erreur serveur (${res.status}) — voir la console pour le corps de la réponse.`;
      }
    } catch {
      if (res.status >= 500) {
        message = `Erreur serveur (${res.status}) — voir la console pour le détail (HTML/texte).`;
      } else {
        message = preview.trim().slice(0, 500) || message;
      }
    }
  }

  throw new Error(message);
}

/** Réponse API (camelCase) — alignée sur `Todo` */
type TodoJson = Todo;

export async function fetchTodos(): Promise<Todo[]> {
  const res = await fetch(BASE);
  if (!res.ok) await handleNotOkResponse(res);
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
  if (!res.ok) await handleNotOkResponse(res);
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
  if (!res.ok) await handleNotOkResponse(res);
  return res.json() as Promise<Todo>;
}

export async function deleteTodo(id: string): Promise<void> {
  const res = await fetch(`${BASE}/${id}`, { method: "DELETE" });
  if (!res.ok) await handleNotOkResponse(res);
}
