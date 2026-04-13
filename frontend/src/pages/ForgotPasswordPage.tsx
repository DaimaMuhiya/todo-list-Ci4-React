import { useEffect, useState } from "react";
import { Link, Navigate } from "react-router-dom";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "@/hooks/use-toast";
import { passwordResetRequest } from "@/lib/auth-api";
import { useAuth } from "@/auth/AuthContext";

const SENT_MODAL_MS = 5000;

export default function ForgotPasswordPage() {
  const [lastName, setLastName] = useState("");
  const [firstName, setFirstName] = useState("");
  const [email, setEmail] = useState("");
  const [busy, setBusy] = useState(false);
  const [showSentModal, setShowSentModal] = useState(false);
  const { user, loading } = useAuth();

  useEffect(() => {
    if (!showSentModal) return;
    const id = window.setTimeout(() => setShowSentModal(false), SENT_MODAL_MS);
    return () => clearTimeout(id);
  }, [showSentModal]);

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
    if (!lastName.trim() || !firstName.trim() || !email.trim()) {
      return;
    }
    setBusy(true);
    try {
      const res = await passwordResetRequest({
        lastName: lastName.trim(),
        firstName: firstName.trim(),
        email: email.trim().toLowerCase(),
      });
      toast({
        title: "Demande enregistree",
        description: res.message,
      });
    } catch (err) {
      const message =
        err instanceof Error ? err.message : "Envoi impossible pour le moment.";
      toast({
        title: "Reinitialisation",
        description: message,
        variant: "destructive",
      });
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-background p-6">
      <Dialog open={showSentModal} onOpenChange={setShowSentModal}>
        <DialogContent showCloseButton={false} className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>E-mail envoye</DialogTitle>
            <DialogDescription className="text-base text-foreground/90">
              Si les informations correspondent a un compte, un e-mail vous a
              ete envoye pour reinitialiser votre mot de passe. Consultez votre
              boite de reception (et le dossier courrier indesirable).
            </DialogDescription>
          </DialogHeader>
        </DialogContent>
      </Dialog>

      <div className="w-full max-w-md space-y-6 rounded-xl border border-border bg-card p-8 shadow-lg">
        <div>
          <h1 className="text-2xl font-bold text-foreground">
            Mot de passe oublie
          </h1>
          <p className="mt-1 text-sm text-muted-foreground">
            Saisissez les memes nom, prenom et e-mail que sur votre compte. Si
            tout correspond, vous recevrez un e-mail avec un lien securise.
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="fp-lastName">Nom</Label>
            <Input
              id="fp-lastName"
              autoComplete="family-name"
              value={lastName}
              onChange={(e) => setLastName(e.target.value)}
              className="bg-secondary"
              required
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="fp-firstName">Prenom</Label>
            <Input
              id="fp-firstName"
              autoComplete="given-name"
              value={firstName}
              onChange={(e) => setFirstName(e.target.value)}
              className="bg-secondary"
              required
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="fp-email">Adresse e-mail du compte</Label>
            <Input
              id="fp-email"
              type="email"
              autoComplete="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="bg-secondary"
              required
            />
          </div>
          <Button type="submit" className="w-full" disabled={busy}>
            {busy ? "Envoi…" : "Envoyer le lien par e-mail"}
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
