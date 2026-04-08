"use client";

import type { Todo, Category } from "@/lib/types";
import { TaskItem } from "./task-item";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Search, Filter, ListTodo, PanelLeftOpen } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Empty,
  EmptyMedia,
  EmptyHeader,
  EmptyTitle,
  EmptyDescription,
} from "@/components/ui/empty";

const categoryLabels: Record<Category | "all", string> = {
  all: "Toutes les taches",
  work: "Travail",
  personal: "Personnel",
  urgent: "Urgent",
  other: "Autre",
};

interface TaskListProps {
  tasks: Todo[];
  selectedCategory: Category | "all";
  searchQuery: string;
  onSearchChange: (query: string) => void;
  filterStatus: "all" | "pending" | "completed";
  onFilterChange: (status: "all" | "pending" | "completed") => void;
  onToggle: (id: string) => void;
  onDelete: (id: string) => void;
  onEdit: (task: Todo) => void;
  /** Si défini, affiche le bouton pour rouvrir la barre latérale à gauche du titre. */
  onExpandSidebar?: () => void;
}

export function TaskList({
  tasks,
  selectedCategory,
  searchQuery,
  onSearchChange,
  filterStatus,
  onFilterChange,
  onToggle,
  onDelete,
  onEdit,
  onExpandSidebar,
}: TaskListProps) {
  return (
    <div className="flex h-full flex-col">
      <header className="border-b border-border p-6">
        <div className="flex items-start gap-3">
          {onExpandSidebar ? (
            <Button
              type="button"
              variant="outline"
              size="icon"
              className="mt-1 shrink-0 shadow-sm"
              onClick={onExpandSidebar}
              title="Afficher le panneau"
              aria-label="Afficher le panneau lateral"
            >
              <PanelLeftOpen className="h-4 w-4" />
            </Button>
          ) : null}
          <div className="min-w-0 flex-1">
            <h2 className="text-2xl font-bold text-foreground">
              {categoryLabels[selectedCategory]}
            </h2>
            <p className="text-sm text-muted-foreground">
              {tasks.length} tache{tasks.length !== 1 ? "s" : ""}
            </p>
          </div>
        </div>

        <div className="mt-4 flex items-center gap-3">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              placeholder="Rechercher une tache..."
              value={searchQuery}
              onChange={(e) => onSearchChange(e.target.value)}
              className="bg-secondary pl-10"
            />
          </div>

          <Select
            value={filterStatus}
            onValueChange={(v) => onFilterChange(v as typeof filterStatus)}
          >
            <SelectTrigger className="w-40 bg-secondary">
              <Filter className="mr-2 h-4 w-4" />
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Toutes</SelectItem>
              <SelectItem value="pending">En attente</SelectItem>
              <SelectItem value="completed">Terminees</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </header>

      <div className="flex-1 overflow-y-auto p-6">
        {tasks.length === 0 ? (
          <Empty className="mt-12">
            <EmptyMedia variant="icon">
              <ListTodo className="h-6 w-6" />
            </EmptyMedia>
            <EmptyHeader>
              <EmptyTitle>Aucune tache</EmptyTitle>
              <EmptyDescription>
                {searchQuery
                  ? "Aucune tache ne correspond a votre recherche."
                  : "Commencez par creer une nouvelle tache."}
              </EmptyDescription>
            </EmptyHeader>
          </Empty>
        ) : (
          <div className="space-y-3">
            {tasks.map((task) => (
              <TaskItem
                key={task.id}
                task={task}
                onToggle={onToggle}
                onDelete={onDelete}
                onEdit={onEdit}
              />
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
