import type { User } from "@/lib/types";
import { apiFetch } from "@/lib/api-fetch";
import {
  clearStoredAccessToken,
  setStoredAccessToken,
} from "@/lib/auth-token-storage";

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
  const body = (await res.json()) as { user: User; accessToken?: string };
  if (body.accessToken) {
    setStoredAccessToken(body.accessToken);
  }
  return body.user;
}

export async function logout(): Promise<void> {
  try {
    const res = await apiFetch("/api/auth/logout", { method: "POST" });
    if (!res.ok) await handleNotOkResponse(res);
  } finally {
    clearStoredAccessToken();
  }
}

export async function deleteAccount(): Promise<void> {
  const res = await apiFetch("/api/auth/account", { method: "DELETE" });
  if (!res.ok) await handleNotOkResponse(res);
  clearStoredAccessToken();
}

export interface RegisterPayload {
  lastName: string;
  firstName: string;
  email: string;
  password: string;
}

export type RegisterMailStatus = "sent" | "not_configured" | "send_failed";

export async function register(
  payload: RegisterPayload,
): Promise<{
  user: User;
  message: string;
  mailSent: boolean;
  mailStatus: RegisterMailStatus;
}> {
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
  return res.json() as Promise<{
    user: User;
    message: string;
    mailSent: boolean;
    mailStatus: RegisterMailStatus;
  }>;
}

export interface PasswordResetRequestPayload {
  lastName: string;
  firstName: string;
  email: string;
}

export async function passwordResetRequest(
  payload: PasswordResetRequestPayload,
): Promise<{ message: string }> {
  const res = await apiFetch("/api/auth/password-reset/request", {
    method: "POST",
    body: JSON.stringify({
      lastName: payload.lastName,
      firstName: payload.firstName,
      email: payload.email,
    }),
  });
  if (!res.ok) await handleNotOkResponse(res);
  return res.json() as Promise<{ message: string }>;
}

export async function passwordResetConfirm(
  token: string,
): Promise<{ completionToken: string }> {
  const res = await apiFetch("/api/auth/password-reset/confirm", {
    method: "POST",
    body: JSON.stringify({ token }),
  });
  if (!res.ok) await handleNotOkResponse(res);
  return res.json() as Promise<{ completionToken: string }>;
}

export async function passwordResetComplete(payload: {
  completionToken: string;
  password: string;
  passwordConfirm: string;
}): Promise<{ ok: boolean; message: string }> {
  const res = await apiFetch("/api/auth/password-reset/complete", {
    method: "POST",
    body: JSON.stringify({
      completionToken: payload.completionToken,
      password: payload.password,
      passwordConfirm: payload.passwordConfirm,
    }),
  });
  if (!res.ok) await handleNotOkResponse(res);
  return res.json() as Promise<{ ok: boolean; message: string }>;
}
