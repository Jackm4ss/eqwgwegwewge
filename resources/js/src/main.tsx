import { createRoot } from "react-dom/client";
import { BrowserRouter } from 'react-router';
import App from "./App";
import { registerServiceWorker } from "./lib/pwa";
import "./styles/index.css";

registerServiceWorker();

createRoot(document.getElementById("root")!).render(
  <BrowserRouter>
    <App />
    </BrowserRouter>
  );
