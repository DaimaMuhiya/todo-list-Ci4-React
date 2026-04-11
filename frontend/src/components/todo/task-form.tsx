"use client";

import { useState, useEffect } from "react";
import type { Todo, Priority, Category } from "@/lib/types";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog";

interface TaskFormProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSubmit: (
    task: Omit<Todo, "id" | "createdAt">,
  ) => void | Promise<void>;
  editTask?: Todo | null;
}

export function TaskForm({
  open,
  onOpenChange,
  onSubmit,
  editTask,
}: TaskFormProps) {
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [priority, setPriority] = useState<Priority>("medium");
  const [category, setCategory] = useState<Category>("work");
  const [dueDate, setDueDate] = useState("");

  useEffect(() => {
    if (editTask) {
      setTitle(editTask.title);
      setDescription(editTask.description || "");
      setPriority(editTask.priority);
      setCategory(editTask.category);
      setDueDate(editTask.dueDate || "");
    } else {
      resetForm();
    }
  }, [editTask, open]);

  const resetForm = () => {
    setTitle("");
    setDescription("");
    setPriority("medium");
    setCategory("work");
    setDueDate("");
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!title.trim()) return;

    try {
      await Promise.resolve(
        onSubmit({
          title: title.trim(),
          description: description.trim() || undefined,
          completed: editTask?.completed || false,
          priority,
          category,
          dueDate: dueDate || undefined,
          sectionId: editTask?.sectionId ?? "",
        }),
      );
      resetForm();
      onOpenChange(false);
    } catch {
      /* Erreurs réseau / API gérées par le parent (toast, etc.) */
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>
            {editTask ? "Modifier la tache" : "Nouvelle tache"}
          </DialogTitle>
          <DialogDescription>
            {editTask
              ? "Modifiez les details de votre tache ci-dessous."
              : "Remplissez les informations pour creer une nouvelle tache."}
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="title">Titre</Label>
            <Input
              id="title"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              placeholder="Entrez le titre de la tache..."
              className="bg-secondary"
              autoFocus
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Description (optionnel)</Label>
            <Textarea
              id="description"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Ajoutez une description..."
              className="bg-secondary resize-none"
              rows={3}
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Priorite</Label>
              <Select
                value={priority}
                onValueChange={(v) => setPriority(v as Priority)}
              >
                <SelectTrigger className="bg-secondary">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="high">Haute</SelectItem>
                  <SelectItem value="medium">Moyenne</SelectItem>
                  <SelectItem value="low">Basse</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Categorie</Label>
              <Select
                value={category}
                onValueChange={(v) => setCategory(v as Category)}
              >
                <SelectTrigger className="bg-secondary">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="work">Travail</SelectItem>
                  <SelectItem value="personal">Personnel</SelectItem>
                  <SelectItem value="urgent">Urgent</SelectItem>
                  <SelectItem value="other">Autre</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="dueDate">Date limite (optionnel)</Label>
            <Input
              id="dueDate"
              type="date"
              value={dueDate}
              onChange={(e) => setDueDate(e.target.value)}
              className="bg-secondary"
            />
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="ghost"
              onClick={() => onOpenChange(false)}
            >
              Annuler
            </Button>
            <Button type="submit" disabled={!title.trim()}>
              {editTask ? "Enregistrer" : "Creer"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
