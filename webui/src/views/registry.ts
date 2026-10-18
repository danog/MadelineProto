import type { ComponentType } from "react";
import type { Page, State } from "../types";

export interface ViewProps {
    page: Page;
    state: State;
}

/**
 * Views by name: the server picks one with Page::$view. New surfaces of the web UI (a Telegram client, a bot
 * IDE...) register their views here, and get their pages from the same state.
 */
const views = new Map<string, ComponentType<ViewProps>>();

export function registerView(name: string, view: ComponentType<ViewProps>): void {
    views.set(name, view);
}

export function getView(name: string): ComponentType<ViewProps> | undefined {
    return views.get(name);
}
