/**
 * JWT pour appels cross-origin (Vercel → API Render) quand les cookies tiers sont bloqués.
 * Stockage sessionStorage : fermeture d’onglet = déconnexion côté client.
 */
export const TF_ACCESS_TOKEN_KEY = "tf_access_token";

export function getStoredAccessToken(): string | null {
  try {
    return sessionStorage.getItem(TF_ACCESS_TOKEN_KEY);
  } catch {
    return null;
  }
}

export function setStoredAccessToken(token: string): void {
  try {
    sessionStorage.setItem(TF_ACCESS_TOKEN_KEY, token);
  } catch {
    /* ignore */
  }
}

export function clearStoredAccessToken(): void {
  try {
    sessionStorage.removeItem(TF_ACCESS_TOKEN_KEY);
  } catch {
    /* ignore */
  }
}

/**
 * À exécuter une fois au démarrage (avant le rendu React) pour le lien magique :
 * ?connexion=1&accessToken=…
 */
export function hydrateAccessTokenFromUrl(): void {
  if (typeof window === "undefined") {
    return;
  }
  const params = new URLSearchParams(window.location.search);
  const at = params.get("accessToken");
  if (!at) {
    return;
  }
  setStoredAccessToken(at);
  params.delete("accessToken");
  const qs = params.toString();
  const path = window.location.pathname;
  const next = path + (qs ? `?${qs}` : "") + window.location.hash;
  window.history.replaceState(null, "", next);
}
