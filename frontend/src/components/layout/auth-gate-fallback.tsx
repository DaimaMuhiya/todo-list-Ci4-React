/**
 * Écran léger pendant /api/auth/me : évite l’illusion d’un accès au tableau de bord
 * quand aucun jeton n’est stocké côté client (visiteur typique).
 */
export function AuthGateFallback() {
  return (
    <div
      className="flex min-h-screen items-center justify-center bg-background text-sm text-muted-foreground"
      aria-busy="true"
      aria-label="Verification de la session"
    >
      Verification…
    </div>
  );
}
