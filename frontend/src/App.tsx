import { useState, useMemo, useEffect } from "react";
import type { Todo, Category } from "@/lib/types";
import { TodoSidebar } from "@/components/todo/sidebar";
import { TaskList } from "@/components/todo/task-list";
import { TaskForm } from "@/components/todo/task-form";
import { Toaster } from "@/components/ui/toaster";
import { toast } from "@/hooks/use-toast";
import {
  fetchTodos,
  createTodo,
  updateTodo,
  deleteTodo,
} from "@/lib/todos-api";

export default function App() {
  const [tasks, setTasks] = useState<Todo[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedCategory, setSelectedCategory] = useState<Category | "all">(
    "all",
  );
  const [searchQuery, setSearchQuery] = useState("");
  const [filterStatus, setFilterStatus] = useState<
    "all" | "pending" | "completed"
  >("all");
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingTask, setEditingTask] = useState<Todo | null>(null);

  useEffect(() => {
    let cancelled = false;

    (async () => {
      setLoading(true);
      try {
        const data = await fetchTodos();
        if (!cancelled) setTasks(data);
      } catch (err) {
        if (!cancelled) {
          const message =
            err instanceof Error
              ? err.message
              : "Impossible de charger les taches.";
          toast({
            title: "Chargement",
            description: message,
            variant: "destructive",
          });
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, []);

  const stats = useMemo(() => {
    const total = tasks.length;
    const completed = tasks.filter((t) => t.completed).length;
    return { total, completed, pending: total - completed };
  }, [tasks]);

  const filteredTasks = useMemo(() => {
    return tasks.filter((task) => {
      if (selectedCategory !== "all" && task.category !== selectedCategory) {
        return false;
      }
      if (filterStatus === "pending" && task.completed) return false;
      if (filterStatus === "completed" && !task.completed) return false;
      if (searchQuery) {
        const query = searchQuery.toLowerCase();
        return (
          task.title.toLowerCase().includes(query) ||
          task.description?.toLowerCase().includes(query)
        );
      }
      return true;
    });
  }, [tasks, selectedCategory, filterStatus, searchQuery]);

  const handleToggle = async (id: string) => {
    const task = tasks.find((t) => t.id === id);
    if (!task) return;
    const next = { ...task, completed: !task.completed };
    const payload: Omit<Todo, "id" | "createdAt"> = {
      title: next.title,
      description: next.description,
      completed: next.completed,
      priority: next.priority,
      category: next.category,
      dueDate: next.dueDate,
    };
    try {
      const saved = await updateTodo(id, payload);
      setTasks((prev) => prev.map((t) => (t.id === id ? saved : t)));
    } catch (err) {
      const message =
        err instanceof Error ? err.message : "Mise a jour impossible.";
      toast({
        title: "Tache",
        description: message,
        variant: "destructive",
      });
    }
  };

  const handleDelete = async (id: string) => {
    try {
      await deleteTodo(id);
      setTasks((prev) => prev.filter((t) => t.id !== id));
    } catch (err) {
      const message =
        err instanceof Error ? err.message : "Suppression impossible.";
      toast({
        title: "Suppression",
        description: message,
        variant: "destructive",
      });
    }
  };

  const handleEdit = (task: Todo) => {
    setEditingTask(task);
    setIsFormOpen(true);
  };

  const handleSubmit = async (taskData: Omit<Todo, "id" | "createdAt">) => {
    try {
      if (editingTask) {
        const saved = await updateTodo(editingTask.id, taskData);
        setTasks((prev) =>
          prev.map((t) => (t.id === editingTask.id ? saved : t)),
        );
        setEditingTask(null);
      } else {
        const saved = await createTodo(taskData);
        setTasks((prev) => [saved, ...prev]);
      }
    } catch (err) {
      const message =
        err instanceof Error ? err.message : "Enregistrement impossible.";
      toast({
        title: editingTask ? "Modification" : "Creation",
        description: message,
        variant: "destructive",
      });
      throw err;
    }
  };

  const handleOpenChange = (open: boolean) => {
    setIsFormOpen(open);
    if (!open) {
      setEditingTask(null);
    }
  };

  return (
    <div className="flex h-screen overflow-hidden bg-background">
      <TodoSidebar
        selectedCategory={selectedCategory}
        onSelectCategory={setSelectedCategory}
        stats={stats}
        onAddTask={() => setIsFormOpen(true)}
      />

      <main className="flex-1 overflow-hidden">
        {loading ? (
          <div className="flex h-full items-center justify-center text-muted-foreground">
            Chargement des taches…
          </div>
        ) : (
          <TaskList
            tasks={filteredTasks}
            selectedCategory={selectedCategory}
            searchQuery={searchQuery}
            onSearchChange={setSearchQuery}
            filterStatus={filterStatus}
            onFilterChange={setFilterStatus}
            onToggle={handleToggle}
            onDelete={handleDelete}
            onEdit={handleEdit}
          />
        )}
      </main>

      <TaskForm
        open={isFormOpen}
        onOpenChange={handleOpenChange}
        onSubmit={handleSubmit}
        editTask={editingTask}
      />
      <Toaster />
    </div>
  );
}
