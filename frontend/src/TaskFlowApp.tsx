import { useState, useMemo, useEffect } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import type { Todo, Category, BoardSection } from "@/lib/types";
import { TodoSidebar } from "@/components/todo/sidebar";
import { TaskList } from "@/components/todo/task-list";
import { TaskForm } from "@/components/todo/task-form";
import { Toaster } from "@/components/ui/toaster";
import { Button } from "@/components/ui/button";
import { toast } from "@/hooks/use-toast";
import { PanelLeftOpen } from "lucide-react";
import { cn } from "@/lib/utils";
import {
  fetchTodos,
  createTodo,
  updateTodo,
  deleteTodo,
} from "@/lib/todos-api";
import {
  fetchSections,
  createSection,
  deleteSection,
} from "@/lib/sections-api";
import { logout } from "@/lib/auth-api";
import { useAuth } from "@/auth/AuthContext";

const SIDEBAR_STORAGE_KEY = "taskflow-sidebar-open";

export default function TaskFlowApp() {
  const { user, setUser } = useAuth();
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const [tasks, setTasks] = useState<Todo[]>([]);
  const [sections, setSections] = useState<BoardSection[]>([]);
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
  const [sidebarOpen, setSidebarOpen] = useState(() => {
    try {
      const v = localStorage.getItem(SIDEBAR_STORAGE_KEY);
      if (v === "0") return false;
      return true;
    } catch {
      return true;
    }
  });

  useEffect(() => {
    if (searchParams.get("connexion") === "1") {
      toast({
        title: "Connexion",
        description: "Vous etes connecte via le lien e-mail.",
      });
      searchParams.delete("connexion");
      setSearchParams(searchParams, { replace: true });
    }
  }, [searchParams, setSearchParams]);

  useEffect(() => {
    try {
      localStorage.setItem(SIDEBAR_STORAGE_KEY, sidebarOpen ? "1" : "0");
    } catch {
      /* ignore */
    }
  }, [sidebarOpen]);

  useEffect(() => {
    let cancelled = false;

    (async () => {
      setLoading(true);
      try {
        const [data, secs] = await Promise.all([fetchTodos(), fetchSections()]);
        if (!cancelled) {
          setTasks(data);
          setSections(secs);
        }
      } catch (err) {
        if (!cancelled) {
          const message =
            err instanceof Error
              ? err.message
              : "Impossible de charger les taches.";
          if (
            err instanceof Error &&
            (message.includes("Authentification") ||
              message.includes("Session invalide"))
          ) {
            setUser(null);
            navigate("/login", { replace: true });
            return;
          }
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
      sectionId: next.sectionId,
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

  const handleTaskMove = async (taskId: string, sectionId: string) => {
    const task = tasks.find((t) => t.id === taskId);
    if (!task) return;
    try {
      const saved = await updateTodo(taskId, {
        title: task.title,
        description: task.description,
        completed: task.completed,
        priority: task.priority,
        category: task.category,
        sectionId,
        dueDate: task.dueDate,
      });
      setTasks((prev) => prev.map((t) => (t.id === taskId ? saved : t)));
    } catch (err) {
      const message =
        err instanceof Error ? err.message : "Deplacement impossible.";
      toast({
        title: "Tableau",
        description: message,
        variant: "destructive",
      });
    }
  };

  const handleAddSection = async (name: string) => {
    try {
      const created = await createSection(name);
      setSections((prev) =>
        [...prev, created].sort((a, b) => a.sortOrder - b.sortOrder),
      );
      toast({ title: "Section", description: `« ${created.name} » ajoutée.` });
    } catch (err) {
      const message =
        err instanceof Error ? err.message : "Creation de section impossible.";
      toast({
        title: "Section",
        description: message,
        variant: "destructive",
      });
    }
  };

  const handleDeleteSection = async (sectionId: string) => {
    try {
      await deleteSection(sectionId);
      const todoId = sections.find((s) => s.slug === "todo")?.id;
      setSections((prev) => prev.filter((s) => s.id !== sectionId));
      setTasks((prev) =>
        prev.map((t) =>
          t.sectionId === sectionId
            ? {
                ...t,
                sectionId: todoId ?? t.sectionId,
                completed: false,
              }
            : t,
        ),
      );
      toast({ title: "Section", description: "Section supprimée." });
    } catch (err) {
      const message =
        err instanceof Error ? err.message : "Suppression impossible.";
      toast({
        title: "Section",
        description: message,
        variant: "destructive",
      });
    }
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
        const todoSectionId = sections.find((s) => s.slug === "todo")?.id;
        if (!todoSectionId) {
          toast({
            title: "Creation",
            description: "Colonnes non chargees. Reessayez dans un instant.",
            variant: "destructive",
          });
          throw new Error("Colonnes non chargees.");
        }
        const saved = await createTodo({
          ...taskData,
          sectionId: todoSectionId,
        });
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

  const handleLogout = async () => {
    try {
      await logout();
    } catch {
      /* ignore */
    }
    setUser(null);
    navigate("/login", { replace: true });
    toast({ title: "Deconnexion", description: "A bientot." });
  };

  return (
    <div className="flex h-screen overflow-hidden bg-background">
      <div
        className={cn(
          "shrink-0 overflow-hidden border-r border-border bg-sidebar transition-[width] duration-200 ease-in-out",
          sidebarOpen ? "w-64" : "w-0 border-r-0",
        )}
      >
        <TodoSidebar
          selectedCategory={selectedCategory}
          onSelectCategory={setSelectedCategory}
          stats={stats}
          onAddTask={() => setIsFormOpen(true)}
          onCollapse={() => setSidebarOpen(false)}
          user={user}
          onLogout={handleLogout}
          onAdmin={() => navigate("/admin")}
        />
      </div>

      <main className="min-h-0 flex-1 overflow-hidden">
        {loading ? (
          !sidebarOpen ? (
            <div className="flex h-full flex-col">
              <div className="flex items-start gap-3 border-b border-border p-6">
                <Button
                  type="button"
                  variant="outline"
                  size="icon"
                  className="mt-1 shrink-0 shadow-sm"
                  onClick={() => setSidebarOpen(true)}
                  title="Afficher le panneau"
                  aria-label="Afficher le panneau lateral"
                >
                  <PanelLeftOpen className="h-4 w-4" />
                </Button>
                <p className="min-w-0 flex-1 pt-2 text-sm text-muted-foreground">
                  Chargement des taches…
                </p>
              </div>
            </div>
          ) : (
            <div className="flex h-full items-center justify-center text-muted-foreground">
              Chargement des taches…
            </div>
          )
        ) : (
          <TaskList
            tasks={filteredTasks}
            sections={sections}
            selectedCategory={selectedCategory}
            searchQuery={searchQuery}
            onSearchChange={setSearchQuery}
            filterStatus={filterStatus}
            onFilterChange={setFilterStatus}
            onToggle={handleToggle}
            onDelete={handleDelete}
            onEdit={handleEdit}
            onTaskMove={handleTaskMove}
            onAddSection={handleAddSection}
            onDeleteSection={handleDeleteSection}
            onExpandSidebar={
              sidebarOpen ? undefined : () => setSidebarOpen(true)
            }
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
