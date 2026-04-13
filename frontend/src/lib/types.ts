export type UserRole = "user" | "admin";

export interface User {
  id: string;
  lastName: string;
  firstName: string;
  email: string;
  role: UserRole;
  createdAt: string;
}

export type Priority = "low" | "medium" | "high";

export type Category = "work" | "personal" | "urgent" | "other";

export interface CategoryInfo {
  id: Category;
  label: string;
  icon: "briefcase" | "user" | "alert" | "grid";
  color: string;
}

/** Colonne du tableau (sections système : todo, in_progress, done). */
export interface BoardSection {
  id: string;
  name: string;
  /** Présent uniquement pour les trois colonnes par défaut. */
  slug?: string | null;
  sortOrder: number;
}

export interface Todo {
  id: string;
  title: string;
  description?: string;
  completed: boolean;
  priority: Priority;
  category: Category;
  /** Identifiant de la colonne (`board_sections.id`). */
  sectionId: string;
  dueDate?: string;
  createdAt: string;
}

export interface TodoStats {
  total: number;
  completed: number;
  pending: number;
}
