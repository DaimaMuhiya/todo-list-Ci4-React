import type { User, UserRole } from "@/lib/types";
import { apiFetch } from "@/lib/api-fetch";

const API_ERROR_BODY_MAX = 4000;

async function handleNotOkResponse(res: Response): Promise<never> {
  const text = await res.text();
  const preview =
    text.length > API_ERROR_BODY_MAX
      ? `${text.slice(0, API_ERROR_BODY_MAX)}…`
      : text;

  console.error("[admin-api]", res.status, res.url, preview || "(corps vide)");

  let message = res.statusText || `Erreur ${res.status}`;

  if (preview.trim() !== "") {
    try {
      const body = JSON.parse(text) as Record<string, unknown>;
      if (body?.error && typeof body.error === "string") {
        message = body.error;
      } else if (body?.errors && typeof body.errors === "object") {
        message = Object.values(body.errors as Record<string, string[]>).flat().join(" ");
      }
    } catch {
      message = preview.trim().slice(0, 500) || message;
    }
  }

  throw new Error(message);
}

export async function fetchAdminUsers(): Promise<User[]> {
  const res = await apiFetch("/api/admin/users");
  if (!res.ok) await handleNotOkResponse(res);
  return res.json() as Promise<User[]>;
}

export async function updateUserRole(
  id: string,
  role: UserRole,
): Promise<User> {
  const res = await apiFetch(`/api/admin/users/${id}`, {
    method: "PATCH",
    body: JSON.stringify({ role }),
  });
  if (!res.ok) await handleNotOkResponse(res);
  return res.json() as Promise<User>;
}

export async function deleteUser(id: string): Promise<void> {
  const res = await apiFetch(`/api/admin/users/${id}`, { method: "DELETE" });
  if (!res.ok) await handleNotOkResponse(res);
}
