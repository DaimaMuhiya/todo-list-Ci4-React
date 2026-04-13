import { Skeleton } from "@/components/ui/skeleton";

/**
 * Mise en page proche de TaskFlow pendant le chargement du profil (/api/auth/me).
 */
export function ProtectedRouteSkeleton() {
  return (
    <div
      className="flex h-screen overflow-hidden bg-background"
      aria-busy="true"
      aria-label="Chargement de l application"
    >
      <div className="flex h-full w-64 min-w-64 shrink-0 flex-col border-r border-border bg-sidebar p-4">
        <Skeleton className="mb-2 h-7 w-28" />
        <Skeleton className="mb-8 h-4 w-40" />
        <Skeleton className="mb-2 h-3 w-24" />
        <div className="flex flex-1 flex-col space-y-2">
          {Array.from({ length: 5 }, (_, i) => (
            <Skeleton key={i} className="h-9 w-full" />
          ))}
        </div>
        <div className="mt-auto space-y-3 border-t border-border pt-4">
          <Skeleton className="h-3 w-28" />
          <div className="grid grid-cols-2 gap-2">
            <Skeleton className="h-16 rounded-lg" />
            <Skeleton className="h-16 rounded-lg" />
          </div>
          <Skeleton className="h-24 w-full rounded-lg" />
        </div>
      </div>
      <main className="flex min-h-0 flex-1 flex-col overflow-hidden">
        <div className="shrink-0 space-y-4 border-b border-border p-6">
          <Skeleton className="h-8 w-56" />
          <Skeleton className="h-4 w-80 max-w-full" />
          <div className="flex flex-wrap items-center gap-3">
            <Skeleton className="h-10 min-w-48 flex-1" />
            <Skeleton className="h-10 w-40" />
            <Skeleton className="h-10 w-44" />
            <Skeleton className="h-10 w-36" />
          </div>
        </div>
        <div className="flex min-h-0 flex-1 gap-4 overflow-hidden p-6">
          {Array.from({ length: 3 }, (_, i) => (
            <div
              key={i}
              className="flex w-[min(100vw-3rem,18rem)] shrink-0 flex-col rounded-xl border border-border bg-muted/20"
            >
              <div className="space-y-2 border-b border-border px-3 py-2.5">
                <Skeleton className="h-4 w-24" />
                <Skeleton className="h-3 w-16" />
              </div>
              <div className="space-y-2 p-2">
                <Skeleton className="h-20 w-full rounded-lg" />
                <Skeleton className="h-20 w-full rounded-lg" />
                <Skeleton className="h-16 w-full rounded-lg" />
              </div>
            </div>
          ))}
        </div>
      </main>
    </div>
  );
}
