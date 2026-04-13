"use client";

import { useMemo, useState } from "react";
import type { Todo, Category, BoardSection } from "@/lib/types";
import { TaskItem, TASK_DRAG_MIME } from "./task-item";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Search,
  Filter,
  ListTodo,
  PanelLeftOpen,
  Plus,
  LayoutGrid,
  Trash2,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Empty,
  EmptyMedia,
  EmptyHeader,
  EmptyTitle,
  EmptyDescription,
} from "@/components/ui/empty";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { cn } from "@/lib/utils";

const categoryLabels: Record<Category | "all", string> = {
  all: "Toutes les taches",
  work: "Travail",
  personal: "Personnel",
  urgent: "Urgent",
  other: "Autre",
};

interface TaskListProps {
  tasks: Todo[];
  sections: BoardSection[];
  selectedCategory: Category | "all";
  searchQuery: string;
  onSearchChange: (query: string) => void;
  filterStatus: "all" | "pending" | "completed";
  onFilterChange: (status: "all" | "pending" | "completed") => void;
  onToggle: (id: string) => void;
  onDelete: (id: string) => void;
  onEdit: (task: Todo) => void;
  onTaskMove: (taskId: string, sectionId: string) => void | Promise<void>;
  onAddSection: (name: string) => void | Promise<void>;
  onDeleteSection: (sectionId: string) => void | Promise<void>;
  /** Si défini, affiche le bouton pour rouvrir la barre latérale à gauche du titre. */
  onExpandSidebar?: () => void;
}

export function TaskList({
  tasks,
  sections,
  selectedCategory,
  searchQuery,
  onSearchChange,
  filterStatus,
  onFilterChange,
  onToggle,
  onDelete,
  onEdit,
  onTaskMove,
  onAddSection,
  onDeleteSection,
  onExpandSidebar,
}: TaskListProps) {
  const [addSectionOpen, setAddSectionOpen] = useState(false);
  const [newSectionName, setNewSectionName] = useState("");
  const [dragOverSectionId, setDragOverSectionId] = useState<string | null>(
    null,
  );
  const [addingSection, setAddingSection] = useState(false);

  const fallbackSectionId = useMemo(() => {
    const todo = sections.find((s) => s.slug === "todo");
    return todo?.id ?? sections[0]?.id ?? "";
  }, [sections]);

  const tasksBySection = useMemo(() => {
    const map = new Map<string, Todo[]>();
    for (const s of sections) {
      map.set(s.id, []);
    }
    for (const t of tasks) {
      const list = map.get(t.sectionId);
      if (list) {
        list.push(t);
      } else if (fallbackSectionId) {
        const fb = map.get(fallbackSectionId);
        if (fb) fb.push(t);
      }
    }
    return map;
  }, [tasks, sections, fallbackSectionId]);

  const readDragTaskId = (e: React.DragEvent): string => {
    return (
      e.dataTransfer.getData(TASK_DRAG_MIME) ||
      e.dataTransfer.getData("text/plain")
    );
  };

  const handleDropOnSection = async (
    e: React.DragEvent,
    sectionId: string,
  ) => {
    e.preventDefault();
    setDragOverSectionId(null);
    const taskId = readDragTaskId(e);
    if (!taskId) return;
    await Promise.resolve(onTaskMove(taskId, sectionId));
  };

  const handleAddSectionSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const name = newSectionName.trim();
    if (!name || addingSection) return;
    setAddingSection(true);
    try {
      await Promise.resolve(onAddSection(name));
      setNewSectionName("");
      setAddSectionOpen(false);
    } finally {
      setAddingSection(false);
    }
  };

  return (
    <div className="flex h-full min-h-0 flex-col">
      <header className="shrink-0 border-b border-border p-6">
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
            <div className="flex flex-wrap items-center gap-2">
              <h2 className="text-2xl font-bold text-foreground">
                {categoryLabels[selectedCategory]}
              </h2>
              {/* <span className="inline-flex items-center gap-1 rounded-md border border-border bg-muted/40 px-2 py-0.5 text-xs text-muted-foreground">
                <LayoutGrid className="h-3.5 w-3.5" />
                Tableau
              </span> */}
            </div>
            <p className="text-sm text-muted-foreground">
              {tasks.length} tache{tasks.length !== 1 ? "s" : ""} — glissez les
              cartes entre les colonnes
            </p>
          </div>
        </div>

        <div className="mt-4 flex flex-wrap items-center gap-3">
          <div className="relative min-w-48 flex-1">
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

          <Button
            type="button"
            variant="outline"
            className="shrink-0 gap-1 shadow-sm"
            onClick={() => setAddSectionOpen(true)}
          >
            <Plus className="h-4 w-4" />
            Ajouter une section
          </Button>
        </div>
      </header>

      <div className="min-h-0 flex-1 overflow-x-auto overflow-y-hidden p-6">
        {sections.length === 0 ? (
          <div className="flex h-full items-center justify-center text-sm text-muted-foreground">
            Chargement des colonnes…
          </div>
        ) : (
          <div className="flex h-full min-h-[min(100%,28rem)] flex-col gap-4 pb-2">
            {tasks.length === 0 ? (
              <Empty className="shrink-0 py-6">
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
            ) : null}
            <div className="flex min-h-0 flex-1 gap-4">
            {sections.map((section) => {
              const columnTasks = tasksBySection.get(section.id) ?? [];
              const isSystem = Boolean(section.slug);
              const isOver = dragOverSectionId === section.id;

              return (
                <section
                  key={section.id}
                  className={cn(
                    "flex w-[min(100vw-3rem,18rem)] shrink-0 flex-col rounded-xl border bg-muted/20",
                    isOver
                      ? "border-primary ring-2 ring-primary/30"
                      : "border-border",
                  )}
                  onDragOver={(e) => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = "move";
                    setDragOverSectionId(section.id);
                  }}
                  onDragLeave={() => {
                    setDragOverSectionId((prev) =>
                      prev === section.id ? null : prev,
                    );
                  }}
                  onDrop={(e) => handleDropOnSection(e, section.id)}
                >
                  <div className="flex items-start justify-between gap-2 border-b border-border px-3 py-2.5">
                    <div className="min-w-0">
                      <h3 className="truncate text-sm font-semibold text-foreground">
                        {section.name}
                      </h3>
                      <p className="text-xs text-muted-foreground">
                        {columnTasks.length} tache
                        {columnTasks.length !== 1 ? "s" : ""}
                      </p>
                    </div>
                    {!isSystem ? (
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="h-8 w-8 shrink-0 text-muted-foreground hover:text-destructive"
                        title="Supprimer la section"
                        aria-label={`Supprimer la section ${section.name}`}
                        onClick={() => {
                          if (
                            typeof window !== "undefined" &&
                            !window.confirm(
                              `Supprimer la section « ${section.name} » ? Les taches seront deplacees vers « A faire ».`,
                            )
                          ) {
                            return;
                          }
                          void onDeleteSection(section.id);
                        }}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    ) : null}
                  </div>

                  <div className="min-h-0 flex-1 space-y-2 overflow-y-auto p-2">
                    {columnTasks.map((task) => (
                      <TaskItem
                        key={task.id}
                        task={task}
                        onToggle={onToggle}
                        onDelete={onDelete}
                        onEdit={onEdit}
                      />
                    ))}
                  </div>
                </section>
              );
            })}
            </div>
          </div>
        )}
      </div>

      <Dialog open={addSectionOpen} onOpenChange={setAddSectionOpen}>
        <DialogContent className="sm:max-w-md">
          <form onSubmit={handleAddSectionSubmit}>
            <DialogHeader>
              <DialogTitle>Nouvelle section</DialogTitle>
              <DialogDescription>
                Ajoutez une colonne supplementaire a votre tableau.
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-2 py-2">
              <Label htmlFor="section-name">Nom de la section</Label>
              <Input
                id="section-name"
                value={newSectionName}
                onChange={(e) => setNewSectionName(e.target.value)}
                placeholder="Ex. : En validation, Bloque…"
                className="bg-secondary"
                autoFocus
              />
            </div>
            <DialogFooter>
              <Button
                type="button"
                variant="ghost"
                onClick={() => setAddSectionOpen(false)}
              >
                Annuler
              </Button>
              <Button
                type="submit"
                disabled={!newSectionName.trim() || addingSection}
              >
                Ajouter
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
