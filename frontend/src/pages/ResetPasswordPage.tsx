import { useEffect, useState } from "react";
import { Link, Navigate, useNavigate, useSearchParams } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { PasswordInput } from "@/components/ui/password-input";
import { Label } from "@/components/ui/label";
import { toast } from "@/hooks/use-toast";
import {
  passwordResetComplete,
  passwordResetConfirm,
} from "@/lib/auth-api";
import { useAuth } from "@/auth/AuthContext";

export default function ResetPasswordPage() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const requestToken = searchParams.get("token")?.trim() ?? "";
  const [completionToken, setCompletionToken] = useState<string | null>(null);
  const [confirmBusy, setConfirmBusy] = useState(false);
  const [password, setPassword] = useState("");
  const [passwordConfirm, setPasswordConfirm] = useState("");
  const [completeBusy, setCompleteBusy] = useState(false);
  const { user } = useAuth();

  useEffect(() => {
    setCompletionToken(null);
  }, [requestToken]);

  if (user) {
    return <Navigate to="/" replace />;
  }

  if (!requestToken) {
    return (
      <div className="flex min-h-screen flex-col items-center justify-center bg-background p-6">
        <div className="w-full max-w-md space-y-4 rounded-xl border border-border bg-card p-8 shadow-lg text-center">
          <h1 className="text-xl font-semibold">Lien incomplet</h1>
          <p className="text-sm text-muted-foreground">
            Ouvrez le lien recu par e-mail, ou demandez une nouvelle
            reinitialisation.
          </p>
          <Button asChild variant="secondary" className="w-full">
            <Link to="/forgot-password">Mot de passe oublie</Link>
          </Button>
          <p className="text-sm text-muted-foreground">
            <Link
              to="/login"
              className="text-primary underline-offset-4 hover:underline"
            >
              Connexion
            </Link>
          </p>
        </div>
      </div>
    );
  }

  const handleConfirm = async () => {
    setConfirmBusy(true);
    try {
      const res = await passwordResetConfirm(requestToken);
      setCompletionToken(res.completionToken);
      toast({
        title: "Confirmation",
        description:
          "Vous pouvez maintenant choisir un nouveau mot de passe.",
      });
    } catch (err) {
      const message =
        err instanceof Error ? err.message : "Confirmation impossible.";
      toast({
        title: "Lien",
        description: message,
        variant: "destructive",
      });
    } finally {
      setConfirmBusy(false);
    }
  };

  const handleComplete = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!completionToken) return;
    if (password.length < 8) {
      toast({
        title: "Mot de passe",
        description: "Au moins 8 caracteres.",
        variant: "destructive",
      });
      return;
    }
    if (password !== passwordConfirm) {
      toast({
        title: "Mot de passe",
        description: "Les deux champs ne correspondent pas.",
        variant: "destructive",
      });
      return;
    }
    setCompleteBusy(true);
    try {
      const res = await passwordResetComplete({
        completionToken,
        password,
        passwordConfirm,
      });
      toast({
        title: "Mot de passe mis a jour",
        description: res.message,
      });
      navigate("/login", { replace: true });
    } catch (err) {
      const message =
        err instanceof Error ? err.message : "Mise a jour impossible.";
      toast({
        title: "Reinitialisation",
        description: message,
        variant: "destructive",
      });
    } finally {
      setCompleteBusy(false);
    }
  };

  if (!completionToken) {
    return (
      <div className="flex min-h-screen flex-col items-center justify-center bg-background p-6">
        <div className="w-full max-w-md space-y-6 rounded-xl border border-border bg-card p-8 shadow-lg">
          <div>
            <h1 className="text-2xl font-bold text-foreground">
              Confirmer la reinitialisation
            </h1>
            <p className="mt-1 text-sm text-muted-foreground">
              Si vous avez bien demande a reinitialiser le mot de passe de ce
              compte, cliquez sur le bouton ci-dessous. Sinon, fermez cette
              page : votre mot de passe actuel ne sera pas modifie.
            </p>
          </div>
          <Button
            type="button"
            className="w-full"
            onClick={handleConfirm}
            disabled={confirmBusy}
          >
            {confirmBusy ? "Verification…" : "Oui, je confirme"}
          </Button>
          <p className="text-center text-sm text-muted-foreground">
            <Link
              to="/login"
              className="text-primary underline-offset-4 hover:underline"
            >
              Annuler — retour a la connexion
            </Link>
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-background p-6">
      <div className="w-full max-w-md space-y-6 rounded-xl border border-border bg-card p-8 shadow-lg">
        <div>
          <h1 className="text-2xl font-bold text-foreground">
            Nouveau mot de passe
          </h1>
          <p className="mt-1 text-sm text-muted-foreground">
            Choisissez un mot de passe d&apos;au moins 8 caracteres.
          </p>
        </div>
        <form onSubmit={handleComplete} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="rp-password">Entrez le nouveau mot de passe</Label>
            <PasswordInput
              id="rp-password"
              autoComplete="new-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="bg-secondary"
              required
              minLength={8}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="rp-password2">
              Confirmer le nouveau mot de passe
            </Label>
            <PasswordInput
              id="rp-password2"
              autoComplete="new-password"
              value={passwordConfirm}
              onChange={(e) => setPasswordConfirm(e.target.value)}
              className="bg-secondary"
              required
              minLength={8}
            />
          </div>
          <Button type="submit" className="w-full" disabled={completeBusy}>
            {completeBusy ? "Enregistrement…" : "Enregistrer le mot de passe"}
          </Button>
        </form>
        <p className="text-center text-sm text-muted-foreground">
          <Link
            to="/login"
            className="text-primary underline-offset-4 hover:underline"
          >
            Retour a la connexion
          </Link>
        </p>
      </div>
    </div>
  );
}
