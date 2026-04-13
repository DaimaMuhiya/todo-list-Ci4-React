import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from "react";
import { useNavigate } from "react-router-dom";
import type { User } from "@/lib/types";
import { fetchMe, logout } from "@/lib/auth-api";
import { clearStoredAccessToken } from "@/lib/auth-token-storage";
import { toast } from "@/hooks/use-toast";

/** Déconnexion automatique après cette durée sans activité (ms). */
const IDLE_LOGOUT_MS = 5 * 60 * 1000;
/** Réduit le bruit des événements (mousemove, scroll, etc.). */
const ACTIVITY_THROTTLE_MS = 500;

export type AuthContextValue = {
  user: User | null;
  loading: boolean;
  refresh: () => Promise<void>;
  setUser: (u: User | null) => void;
};

const AuthContext = createContext<AuthContextValue | null>(null);

/**
 * Déconnexion + redirection login si aucune activité utilisateur pendant IDLE_LOGOUT_MS.
 */
function IdleSessionBridge() {
  const { user, setUser } = useAuth();
  const navigate = useNavigate();

  useEffect(() => {
    if (!user) {
      return;
    }

    let idleTimer: ReturnType<typeof setTimeout> | null = null;
    let lastThrottle = 0;

    const performIdleLogout = async () => {
      try {
        await logout();
      } catch {
        /* ignore */
      }
      setUser(null);
      navigate("/login", { replace: true });
      toast({
        title: "Session expirée",
        description:
          "Aucune activité depuis 5 minutes. Veuillez vous reconnecter.",
      });
    };

    const armTimer = () => {
      if (idleTimer !== null) {
        clearTimeout(idleTimer);
      }
      idleTimer = setTimeout(() => {
        void performIdleLogout();
      }, IDLE_LOGOUT_MS);
    };

    const onActivity = () => {
      const now = Date.now();
      if (now - lastThrottle < ACTIVITY_THROTTLE_MS) {
        return;
      }
      lastThrottle = now;
      armTimer();
    };

    armTimer();

    const events = [
      "mousedown",
      "mousemove",
      "keydown",
      "scroll",
      "touchstart",
      "wheel",
      "click",
    ] as const;
    const opts: AddEventListenerOptions = { passive: true };

    for (const e of events) {
      window.addEventListener(e, onActivity, opts);
    }

    const onVisibility = () => {
      if (document.visibilityState === "visible") {
        onActivity();
      }
    };
    document.addEventListener("visibilitychange", onVisibility);

    return () => {
      if (idleTimer !== null) {
        clearTimeout(idleTimer);
      }
      for (const e of events) {
        window.removeEventListener(e, onActivity, opts);
      }
      document.removeEventListener("visibilitychange", onVisibility);
    };
  }, [user, setUser, navigate]);

  return null;
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    try {
      const me = await fetchMe();
      setUser(me);
    } catch {
      setUser(null);
      clearStoredAccessToken();
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const value = useMemo(
    () => ({ user, loading, refresh, setUser }),
    [user, loading, refresh],
  );

  return (
    <AuthContext.Provider value={value}>
      <IdleSessionBridge />
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error("useAuth doit etre utilise dans AuthProvider.");
  }
  return ctx;
}
