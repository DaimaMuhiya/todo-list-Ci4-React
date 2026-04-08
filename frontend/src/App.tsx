import { useState, useMemo } from "react";
import type { Todo, Category } from "@/lib/types";
import { TodoSidebar } from "@/components/todo/sidebar";
import { TaskList } from "@/components/todo/task-list";
import { TaskForm } from "@/components/todo/task-form";

// Donnees de demonstration
const initialTasks: Todo[] = [
  {
    id: "1",
    title: "Finaliser le rapport trimestriel",
    description:
      "Completer les sections financieres et ajouter les graphiques de performance.",
    completed: false,
    priority: "high",
    category: "work",
    dueDate: "2026-04-10",
    createdAt: "2026-04-05T10:00:00",
  },
  {
    id: "2",
    title: "Reunion avec le client",
    description: "Preparer la presentation pour la reunion de demain.",
    completed: true,
    priority: "high",
    category: "work",
    dueDate: "2026-04-07",
    createdAt: "2026-04-04T14:30:00",
  },
  {
    id: "3",
    title: "Faire les courses",
    description: "Legumes, fruits, pain, lait",
    completed: false,
    priority: "medium",
    category: "personal",
    dueDate: "2026-04-08",
    createdAt: "2026-04-06T09:00:00",
  },
  {
    id: "4",
    title: "Payer les factures",
    completed: false,
    priority: "high",
    category: "urgent",
    dueDate: "2026-04-09",
    createdAt: "2026-04-05T16:00:00",
  },
  {
    id: "5",
    title: "Appeler le medecin",
    description: "Prendre rendez-vous pour le controle annuel.",
    completed: false,
    priority: "low",
    category: "personal",
    createdAt: "2026-04-03T11:00:00",
  },
  {
    id: "6",
    title: "Reviser le code du projet",
    description:
      "Effectuer une revue de code pour les nouvelles fonctionnalites.",
    completed: false,
    priority: "medium",
    category: "work",
    dueDate: "2026-04-12",
    createdAt: "2026-04-06T08:00:00",
  },
];

export default function App() {
  const [tasks, setTasks] = useState<Todo[]>(initialTasks);
  const [selectedCategory, setSelectedCategory] = useState<Category | "all">(
    "all",
  );
  const [searchQuery, setSearchQuery] = useState("");
  const [filterStatus, setFilterStatus] = useState<
    "all" | "pending" | "completed"
  >("all");
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingTask, setEditingTask] = useState<Todo | null>(null);

  // Statistiques
  const stats = useMemo(() => {
    const total = tasks.length;
    const completed = tasks.filter((t) => t.completed).length;
    return { total, completed, pending: total - completed };
  }, [tasks]);

  // Taches filtrees
  const filteredTasks = useMemo(() => {
    return tasks.filter((task) => {
      // Filtre par categorie
      if (selectedCategory !== "all" && task.category !== selectedCategory) {
        return false;
      }
      // Filtre par statut
      if (filterStatus === "pending" && task.completed) return false;
      if (filterStatus === "completed" && !task.completed) return false;
      // Filtre par recherche
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

  // Actions
  const handleToggle = (id: string) => {
    setTasks((prev) =>
      prev.map((t) => (t.id === id ? { ...t, completed: !t.completed } : t)),
    );
  };

  const handleDelete = (id: string) => {
    setTasks((prev) => prev.filter((t) => t.id !== id));
  };

  const handleEdit = (task: Todo) => {
    setEditingTask(task);
    setIsFormOpen(true);
  };

  const handleSubmit = (taskData: Omit<Todo, "id" | "createdAt">) => {
    if (editingTask) {
      // Modification
      setTasks((prev) =>
        prev.map((t) => (t.id === editingTask.id ? { ...t, ...taskData } : t)),
      );
      setEditingTask(null);
    } else {
      // Creation
      const newTask: Todo = {
        ...taskData,
        id: Date.now().toString(),
        createdAt: new Date().toISOString(),
      };
      setTasks((prev) => [newTask, ...prev]);
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
      </main>

      <TaskForm
        open={isFormOpen}
        onOpenChange={handleOpenChange}
        onSubmit={handleSubmit}
        editTask={editingTask}
      />
    </div>
  );
}
