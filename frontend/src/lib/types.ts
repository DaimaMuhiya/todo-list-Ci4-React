export type Priority = "low" | "medium" | "high";

export type Category = "work" | "personal" | "urgent" | "other";

export interface CategoryInfo {
  id: Category;
  label: string;
  icon: "briefcase" | "user" | "alert" | "grid";
  color: string;
}

export interface Todo {
  id: string;
  title: string;
  description?: string;
  completed: boolean;
  priority: Priority;
  category: Category;
  dueDate?: string;
  createdAt: string;
}

export interface TodoStats {
  total: number;
  completed: number;
  pending: number;
}
