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
 * Appels API avec cookie HttpOnly (JWT) — obligatoire pour l’auth.
 */
export function apiFetch(path: string, init?: RequestInit): Promise<Response> {
  const headers = new Headers(init?.headers);
  if (init?.body != null && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }

  return fetch(apiUrl(path), {
    ...init,
    credentials: "include",
    headers,
  });
}
