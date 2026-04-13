import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { toast } from "@/hooks/use-toast";
import type { User, UserRole } from "@/lib/types";
import {
  deleteUser,
  fetchAdminUsers,
  updateUserRole,
} from "@/lib/admin-api";
import { useAuth } from "@/auth/AuthContext";
import { ArrowLeft, Trash2 } from "lucide-react";

export default function AdminPage() {
  const { user: me, setUser } = useAuth();
  const navigate = useNavigate();
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);

  const load = async () => {
    setLoading(true);
    try {
      const data = await fetchAdminUsers();
      setUsers(data);
    } catch (err) {
      const message =
        err instanceof Error ? err.message : "Chargement impossible.";
      toast({
        title: "Administration",
        description: message,
        variant: "destructive",
      });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void load();
  }, []);

  const handleRole = async (id: string, role: UserRole) => {
    if (me?.id === id && role === me.role) {
      return;
    }
    try {
      const updated = await updateUserRole(id, role);
      setUsers((prev) => prev.map((u) => (u.id === id ? updated : u)));
      if (me?.id === id && role !== me.role) {
        try {
          await logout();
        } catch {
          /* ignore */
        }
        setUser(null);
        toast({
          title: "Role",
          description: "Reconnectez-vous pour appliquer votre nouveau role.",
        });
        navigate("/login", { replace: true });
        return;
      }
    } catch (err) {
      const message =
        err instanceof Error ? err.message : "Mise a jour impossible.";
      toast({
        title: "Administration",
        description: message,
        variant: "destructive",
      });
    }
  };

  const handleDelete = async (u: User) => {
    if (!window.confirm(`Supprimer ${u.firstName} ${u.lastName} ?`)) return;
    try {
      await deleteUser(u.id);
      setUsers((prev) => prev.filter((x) => x.id !== u.id));
      toast({ title: "Utilisateur", description: "Compte supprime." });
    } catch (err) {
      const message =
        err instanceof Error ? err.message : "Suppression impossible.";
      toast({
        title: "Administration",
        description: message,
        variant: "destructive",
      });
    }
  };

  return (
    <div className="min-h-screen bg-background p-6">
      <div className="mx-auto max-w-5xl space-y-6">
        <div className="flex flex-wrap items-center gap-4">
          <Button variant="outline" size="sm" asChild>
            <Link to="/" className="gap-2">
              <ArrowLeft className="h-4 w-4" />
              Retour
            </Link>
          </Button>
          <div>
            <h1 className="text-2xl font-bold text-foreground">Utilisateurs</h1>
            <p className="text-sm text-muted-foreground">
              Gestion des comptes et des roles
            </p>
          </div>
        </div>

        {loading ? (
          <p className="text-muted-foreground">Chargement…</p>
        ) : (
          <div className="overflow-x-auto rounded-xl border border-border">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-border bg-muted/40">
                <tr>
                  <th className="p-3 font-medium">Nom</th>
                  <th className="p-3 font-medium">E-mail</th>
                  <th className="p-3 font-medium">Role</th>
                  <th className="p-3 font-medium">Inscription</th>
                  <th className="p-3 w-24" />
                </tr>
              </thead>
              <tbody>
                {users.map((u) => (
                  <tr key={u.id} className="border-b border-border last:border-0">
                    <td className="p-3">
                      {u.firstName} {u.lastName}
                    </td>
                    <td className="p-3 text-muted-foreground">{u.email}</td>
                    <td className="p-3">
                      <Select
                        value={u.role}
                        onValueChange={(v) =>
                          void handleRole(u.id, v as UserRole)
                        }
                      >
                        <SelectTrigger className="w-36 bg-secondary">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="user">Utilisateur</SelectItem>
                          <SelectItem value="admin">Administrateur</SelectItem>
                        </SelectContent>
                      </Select>
                    </td>
                    <td className="p-3 text-muted-foreground">
                      {new Date(u.createdAt).toLocaleDateString("fr-FR")}
                    </td>
                    <td className="p-3">
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="text-muted-foreground hover:text-destructive"
                        disabled={u.id === me?.id}
                        title={
                          u.id === me?.id
                            ? "Impossible de supprimer votre compte ici"
                            : "Supprimer"
                        }
                        onClick={() => void handleDelete(u)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
