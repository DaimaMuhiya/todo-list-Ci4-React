import { getStoredAccessToken } from "@/lib/auth-token-storage";

/** En prod, défini dans `.env.production`. Vide en dev → proxy Vite vers le backend. */
const API_ORIGIN = (import.meta.env.VITE_API_URL as string | undefined)?.replace(
  /\/$/,
  "",
) ?? "";

export function apiUrl(path: string): string {
  const p = path.startsWith("/") ? path : `/${path}`;
  return `${API_ORIGIN}${p}`;
}

/**
 * Appels API : cookie HttpOnly si le navigateur l’envoie, + Bearer en secours
 * (SPA sur un autre domaine que l’API).
 */
export function apiFetch(path: string, init?: RequestInit): Promise<Response> {
  const headers = new Headers(init?.headers);
  if (init?.body != null && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }
  if (!headers.has("Authorization")) {
    const bearer = getStoredAccessToken();
    if (bearer) {
      headers.set("Authorization", `Bearer ${bearer}`);
    }
  }

  return fetch(apiUrl(path), {
    ...init,
    credentials: "include",
    headers,
  });
}
