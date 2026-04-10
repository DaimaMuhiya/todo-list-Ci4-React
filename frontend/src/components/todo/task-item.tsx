"use client";

import { cn } from "@/lib/utils";
import type { Todo, Priority } from "@/lib/types";
import { Checkbox } from "@/components/ui/checkbox";
import { Button } from "@/components/ui/button";
import { Trash2, Edit2, Calendar } from "lucide-react";
import { Badge } from "@/components/ui/badge";

const priorityConfig: Record<Priority, { label: string; className: string }> = {
  high: {
    label: "Haute",
    className: "bg-red-500/20 text-red-400 border-red-500/30",
  },
  medium: {
    label: "Moyenne",
    className: "bg-amber-500/20 text-amber-400 border-amber-500/30",
  },
  low: {
    label: "Basse",
    className: "bg-emerald-500/20 text-emerald-400 border-emerald-500/30",
  },
};

const categoryLabels: Record<string, string> = {
  work: "Travail",
  personal: "Personnel",
  urgent: "Urgent",
  other: "Autre",
};

interface TaskItemProps {
  task: Todo;
  onToggle: (id: string) => void;
  onDelete: (id: string) => void;
  onEdit: (task: Todo) => void;
}

export function TaskItem({ task, onToggle, onDelete, onEdit }: TaskItemProps) {
  const priority = priorityConfig[task.priority];

  return (
    <div
      className={cn(
        "group flex items-start gap-4 rounded-xl border border-border bg-card p-4 transition-all duration-200 hover:border-primary/30 hover:shadow-lg hover:shadow-primary/5",
        task.completed && "opacity-60",
      )}
    >
      <Checkbox
        checked={task.completed}
        onCheckedChange={() => onToggle(task.id)}
        className="mt-1 h-5 w-5 rounded-full border-2 border-muted-foreground data-[state=checked]:border-primary data-[state=checked]:bg-primary"
      />

      <div className="flex-1 min-w-0">
        <div className="flex items-start justify-between gap-2">
          <div className="flex min-w-0 flex-1 flex-wrap items-center gap-2">
            <h3
              className={cn(
                "min-w-0 font-medium text-foreground transition-all",
                task.completed && "line-through text-muted-foreground",
              )}
            >
              {task.title}
            </h3>
            {task.completed ? (
              <Badge
                variant="outline"
                className="shrink-0 border-emerald-500/40 bg-emerald-500/15 text-emerald-500 dark:text-emerald-400"
              >
                Terminé
              </Badge>
            ) : null}
          </div>
          <div className="flex shrink-0 items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
            <Button
              variant="ghost"
              size="icon"
              className="h-8 w-8 text-muted-foreground hover:text-foreground"
              onClick={() => onEdit(task)}
            >
              <Edit2 className="h-4 w-4" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              className="h-8 w-8 text-muted-foreground hover:text-destructive"
              onClick={() => onDelete(task.id)}
            >
              <Trash2 className="h-4 w-4" />
            </Button>
          </div>
        </div>

        {task.description && (
          <p className="mt-1 text-sm text-muted-foreground line-clamp-2">
            {task.description}
          </p>
        )}

        <div className="mt-3 flex flex-wrap items-center gap-2">
          <Badge
            variant="outline"
            className={cn("text-xs", priority.className)}
          >
            {priority.label}
          </Badge>
          <Badge variant="secondary" className="text-xs">
            {categoryLabels[task.category]}
          </Badge>
          {task.dueDate && (
            <span className="flex items-center gap-1 text-xs text-muted-foreground">
              <Calendar className="h-3 w-3" />
              {new Date(task.dueDate).toLocaleDateString("fr-FR", {
                day: "numeric",
                month: "short",
              })}
            </span>
          )}
        </div>
      </div>
    </div>
  );
}
