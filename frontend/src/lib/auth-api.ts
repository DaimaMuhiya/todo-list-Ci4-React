import type { User } from "@/lib/types";
import { apiFetch } from "@/lib/api-fetch";

const API_ERROR_BODY_MAX = 4000;

async function handleNotOkResponse(res: Response): Promise<never> {
  const text = await res.text();
  const preview =
    text.length > API_ERROR_BODY_MAX
      ? `${text.slice(0, API_ERROR_BODY_MAX)}…`
      : text;

  console.error("[auth-api]", res.status, res.url, preview || "(corps vide)");

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
      }
    } catch {
      message = preview.trim().slice(0, 500) || message;
    }
  }

  throw new Error(message);
}

export async function fetchMe(): Promise<User> {
  const res = await apiFetch("/api/auth/me");
  if (res.status === 401) {
    throw new Error("401");
  }
  if (!res.ok) await handleNotOkResponse(res);
  return res.json() as Promise<User>;
}

export async function login(email: string, password: string): Promise<User> {
  const res = await apiFetch("/api/auth/login", {
    method: "POST",
    body: JSON.stringify({ email, password }),
  });
  if (!res.ok) await handleNotOkResponse(res);
  const body = (await res.json()) as { user: User };
  return body.user;
}

export async function logout(): Promise<void> {
  const res = await apiFetch("/api/auth/logout", { method: "POST" });
  if (!res.ok) await handleNotOkResponse(res);
}

export interface RegisterPayload {
  lastName: string;
  firstName: string;
  email: string;
  password: string;
}

export async function register(
  payload: RegisterPayload,
): Promise<{ user: User; message: string }> {
  const res = await apiFetch("/api/auth/register", {
    method: "POST",
    body: JSON.stringify({
      lastName: payload.lastName,
      firstName: payload.firstName,
      email: payload.email,
      password: payload.password,
    }),
  });
  if (!res.ok) await handleNotOkResponse(res);
  return res.json() as Promise<{ user: User; message: string }>;
}
