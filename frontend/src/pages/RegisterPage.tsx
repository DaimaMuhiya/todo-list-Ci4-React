import { useState } from "react";
import { Link, Navigate, useNavigate } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "@/hooks/use-toast";
import { register } from "@/lib/auth-api";
import { useAuth } from "@/auth/AuthContext";

export default function RegisterPage() {
  const [lastName, setLastName] = useState("");
  const [firstName, setFirstName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [busy, setBusy] = useState(false);
  const navigate = useNavigate();
  const { user, loading } = useAuth();

  if (loading) {
    return (
      <div className="flex min-h-screen items-center justify-center text-muted-foreground">
        Chargement…
      </div>
    );
  }

  if (user) {
    return <Navigate to="/" replace />;
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!lastName.trim() || !firstName.trim() || !email.trim() || !password) {
      return;
    }
    if (password.length < 8) {
      toast({
        title: "Mot de passe",
        description: "Au moins 8 caracteres.",
        variant: "destructive",
      });
      return;
    }
    setBusy(true);
    try {
      const res = await register({
        lastName: lastName.trim(),
        firstName: firstName.trim(),
        email: email.trim().toLowerCase(),
        password,
      });
      toast({
        title: "Compte cree",
        description: res.message,
      });
      navigate("/login", { replace: true });
    } catch (err) {
      const message =
        err instanceof Error ? err.message : "Inscription impossible.";
      toast({
        title: "Inscription",
        description: message,
        variant: "destructive",
      });
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-background p-6">
      <div className="w-full max-w-md space-y-6 rounded-xl border border-border bg-card p-8 shadow-lg">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Creer un compte</h1>
          <p className="mt-1 text-sm text-muted-foreground">
            Vous recevrez un e-mail avec un lien pour vous connecter.
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="lastName">Nom</Label>
            <Input
              id="lastName"
              autoComplete="family-name"
              value={lastName}
              onChange={(e) => setLastName(e.target.value)}
              className="bg-secondary"
              required
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="firstName">Prenom</Label>
            <Input
              id="firstName"
              autoComplete="given-name"
              value={firstName}
              onChange={(e) => setFirstName(e.target.value)}
              className="bg-secondary"
              required
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="email">Adresse e-mail</Label>
            <Input
              id="email"
              type="email"
              autoComplete="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="bg-secondary"
              required
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="password">Mot de passe</Label>
            <Input
              id="password"
              type="password"
              autoComplete="new-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="bg-secondary"
              required
              minLength={8}
            />
            <p className="text-xs text-muted-foreground">Minimum 8 caracteres.</p>
          </div>
          <Button type="submit" className="w-full" disabled={busy}>
            {busy ? "Creation…" : "S inscrire"}
          </Button>
        </form>

        <p className="text-center text-sm text-muted-foreground">
          Deja inscrit ?{" "}
          <Link to="/login" className="text-primary underline-offset-4 hover:underline">
            Se connecter
          </Link>
        </p>
      </div>
    </div>
  );
}
