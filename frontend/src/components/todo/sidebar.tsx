"use client";

import { cn } from "@/lib/utils";
import type { Category, CategoryInfo } from "@/lib/types";
import {
  Briefcase,
  User,
  AlertTriangle,
  LayoutGrid,
  Plus,
  CheckCircle2,
  Clock,
} from "lucide-react";
import { Button } from "@/components/ui/button";

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
  user: User,
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
  onAddTask: () => void;
}

export function TodoSidebar({
  selectedCategory,
  onSelectCategory,
  stats,
  onAddTask,
}: SidebarProps) {
  return (
    <aside className="flex h-full w-64 flex-col border-r border-border bg-sidebar p-4">
      <div className="mb-8">
        <h1 className="text-xl font-bold text-foreground">TaskFlow</h1>
        <p className="text-sm text-muted-foreground">Gestionnaire de taches</p>
      </div>

      <Button
        onClick={onAddTask}
        className="mb-6 w-full gap-2 bg-primary text-primary-foreground hover:bg-primary/90"
      >
        <Plus className="h-4 w-4" />
        Nouvelle tache
      </Button>

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
        </div>
      </div>
    </aside>
  );
}
