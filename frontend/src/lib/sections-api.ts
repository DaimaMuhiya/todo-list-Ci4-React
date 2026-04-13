import type { BoardSection } from "@/lib/types";
import { apiFetch } from "@/lib/api-fetch";

const BASE = "/api/sections";

const API_ERROR_BODY_MAX = 4000;

async function handleNotOkResponse(res: Response): Promise<never> {
  const text = await res.text();
  const preview =
    text.length > API_ERROR_BODY_MAX
      ? `${text.slice(0, API_ERROR_BODY_MAX)}…`
      : text;

  console.error("[sections-api]", res.status, res.url, preview || "(corps vide)");

  let message = res.statusText || `Erreur ${res.status}`;

  if (preview.trim() !== "") {
    try {
      const body = JSON.parse(text) as Record<string, unknown>;
      if (body?.message && typeof body.message === "string") {
        message = body.message;
      } else if (body?.error && typeof body.error === "string") {
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

export async function fetchSections(): Promise<BoardSection[]> {
  const res = await apiFetch(BASE);
  if (!res.ok) await handleNotOkResponse(res);
  return res.json() as Promise<BoardSection[]>;
}

export async function createSection(name: string): Promise<BoardSection> {
  const res = await apiFetch(BASE, {
    method: "POST",
    body: JSON.stringify({ name }),
  });
  if (!res.ok) await handleNotOkResponse(res);
  return res.json() as Promise<BoardSection>;
}

export async function deleteSection(id: string): Promise<void> {
  const res = await apiFetch(`${BASE}/${id}`, { method: "DELETE" });
  if (!res.ok) await handleNotOkResponse(res);
}
