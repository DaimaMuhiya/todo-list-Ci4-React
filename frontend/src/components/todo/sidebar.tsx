"use client";

import { useState } from "react";
import { cn } from "@/lib/utils";
import type { Category, CategoryInfo, User } from "@/lib/types";
import {
  Briefcase,
  User as UserIcon,
  AlertTriangle,
  LayoutGrid,
  CheckCircle2,
  Clock,
  PanelLeftClose,
  Shield,
  ChevronDown,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";

const categories: CategoryInfo[] = [
  { id: "work", label: "Travail", icon: "briefcase", color: "text-blue-400" },
  {
    id: "personal",
    label: "Personnel",
    icon: "user",
    color: "text-emerald-400",
  },
  { id: "urgent", label: "Urgent", icon: "alert", color: "text-red-400" },
  { id: "other", label: "Autre", icon: "grid", color: "text-amber-400" },
];

const iconMap = {
  briefcase: Briefcase,
  user: UserIcon,
  alert: AlertTriangle,
  grid: LayoutGrid,
};

interface SidebarProps {
  selectedCategory: Category | "all";
  onSelectCategory: (category: Category | "all") => void;
  stats: {
    total: number;
    completed: number;
    pending: number;
  };
  /** Compteurs issus des tâches API — squelette tant que `true`. */
  statsLoading?: boolean;
  onCollapse?: () => void;
  user?: User | null;
  onLogout?: () => void | Promise<void>;
  onDeleteAccount?: () => void | Promise<void>;
  onAdmin?: () => void;
}

export function TodoSidebar({
  selectedCategory,
  onSelectCategory,
  stats,
  statsLoading = false,
  onCollapse,
  user,
  onLogout,
  onDeleteAccount,
  onAdmin,
}: SidebarProps) {
  const [accountMenuOpen, setAccountMenuOpen] = useState(false);

  return (
    <aside className="flex h-full w-64 min-w-64 flex-col bg-sidebar p-4">
      <div className="mb-8 flex items-start justify-between gap-2">
        <div className="min-w-0">
          <h1 className="text-xl font-bold text-foreground">TaskFlow</h1>
          <p className="text-sm text-muted-foreground">Gestionnaire de taches</p>
        </div>
        {onCollapse ? (
          <Button
            type="button"
            variant="ghost"
            size="icon"
            className="h-8 w-8 shrink-0 text-muted-foreground hover:text-foreground"
            onClick={onCollapse}
            title="Masquer le panneau"
            aria-label="Masquer le panneau lateral"
          >
            <PanelLeftClose className="h-4 w-4" />
          </Button>
        ) : null}
      </div>

      <nav className="flex-1">
        <p className="mb-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
          Categories
        </p>
        <ul className="space-y-1">
          <li>
            <button
              onClick={() => onSelectCategory("all")}
              className={cn(
                "flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors",
                selectedCategory === "all"
                  ? "bg-primary/10 text-primary"
                  : "text-muted-foreground hover:bg-secondary hover:text-foreground",
              )}
            >
              <LayoutGrid className="h-4 w-4" />
              Toutes les taches
            </button>
          </li>
          {categories.map((cat) => {
            const Icon = iconMap[cat.icon];
            return (
              <li key={cat.id}>
                <button
                  onClick={() => onSelectCategory(cat.id)}
                  className={cn(
                    "flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors",
                    selectedCategory === cat.id
                      ? "bg-primary/10 text-primary"
                      : "text-muted-foreground hover:bg-secondary hover:text-foreground",
                  )}
                >
                  <Icon className={cn("h-4 w-4", cat.color)} />
                  {cat.label}
                </button>
              </li>
            );
          })}
        </ul>
      </nav>

      <div className="mt-auto space-y-3 border-t border-border pt-4">
        <p className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
          Statistiques
        </p>
        <div className="grid grid-cols-2 gap-2">
          {statsLoading ? (
            <>
              <Skeleton className="h-17 rounded-lg" />
              <Skeleton className="h-17 rounded-lg" />
            </>
          ) : (
            <>
              <div className="rounded-lg bg-secondary p-3">
                <div className="flex items-center gap-2 text-emerald-400">
                  <CheckCircle2 className="h-4 w-4" />
                  <span className="text-lg font-semibold">{stats.completed}</span>
                </div>
                <p className="text-xs text-muted-foreground">Terminees</p>
              </div>
              <div className="rounded-lg bg-secondary p-3">
                <div className="flex items-center gap-2 text-amber-400">
                  <Clock className="h-4 w-4" />
                  <span className="text-lg font-semibold">{stats.pending}</span>
                </div>
                <p className="text-xs text-muted-foreground">En attente</p>
              </div>
            </>
          )}
        </div>

        {user ? (
          <div className="space-y-2 rounded-lg border border-border bg-secondary/40 p-3">
            <div className="flex items-start gap-2">
              <div className="min-w-0 flex-1 space-y-0.5">
                <p className="truncate text-sm font-medium text-foreground">
                  {user.firstName} {user.lastName}
                </p>
                <p className="truncate text-xs text-muted-foreground">
                  {user.email}
                </p>
              </div>
              {onLogout || onDeleteAccount ? (
                <Popover
                  open={accountMenuOpen}
                  onOpenChange={setAccountMenuOpen}
                >
                  <PopoverTrigger asChild>
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      className="h-8 w-8 shrink-0 text-muted-foreground hover:text-foreground"
                      title="Compte"
                      aria-label="Ouvrir le menu compte"
                    >
                      <ChevronDown
                        className={cn(
                          "h-4 w-4 transition-transform duration-200",
                          accountMenuOpen && "rotate-180",
                        )}
                      />
                    </Button>
                  </PopoverTrigger>
                  <PopoverContent
                    align="end"
                    side="top"
                    sideOffset={8}
                    className="w-52 p-2"
                  >
                    <div className="flex flex-col gap-1">
                      {onLogout ? (
                        <Button
                          type="button"
                          variant="ghost"
                          size="sm"
                          className="h-9 w-full justify-start"
                          onClick={() => {
                            setAccountMenuOpen(false);
                            void onLogout();
                          }}
                        >
                          Deconnexion
                        </Button>
                      ) : null}
                      {onDeleteAccount ? (
                        <Button
                          type="button"
                          variant="destructive"
                          size="sm"
                          className="h-9 w-full justify-start"
                          onClick={() => {
                            setAccountMenuOpen(false);
                            void onDeleteAccount();
                          }}
                        >
                          Supprimer compte
                        </Button>
                      ) : null}
                    </div>
                  </PopoverContent>
                </Popover>
              ) : null}
            </div>
            {user.role === "admin" && onAdmin ? (
              <Button
                type="button"
                variant="outline"
                size="sm"
                className="h-8 w-full gap-1"
                onClick={onAdmin}
              >
                <Shield className="h-3.5 w-3.5" />
                Admin
              </Button>
            ) : null}
          </div>
        ) : null}
      </div>
    </aside>
  );
}
