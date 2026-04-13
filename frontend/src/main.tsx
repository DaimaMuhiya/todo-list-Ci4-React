import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import App from "./App";
import "./index.css";
import { hydrateAccessTokenFromUrl } from "@/lib/auth-token-storage";

hydrateAccessTokenFromUrl();

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <App />
  </StrictMode>,
);
