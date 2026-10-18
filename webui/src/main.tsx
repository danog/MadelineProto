import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { App } from "./App";
import type { State } from "./types";

export { registerView } from "./views/registry";

/**
 * Start the app: the server renders the page twice, as markup (for browsers without JavaScript) and as the
 * JSON state in #madeline-state; the app replaces the markup in #madeline-app, rendering the state.
 *
 * Custom templates without these elements are left alone.
 */
export function start(): void {
    const stateElement = document.getElementById("madeline-state");
    const root = document.getElementById("madeline-app");
    if (!stateElement || !root) {
        return;
    }
    const state = JSON.parse(stateElement.textContent ?? "") as State;
    root.replaceChildren();
    createRoot(root).render(
        <StrictMode>
            <App state={state} />
        </StrictMode>,
    );
}

start();
