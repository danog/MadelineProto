/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * tweb's auth-flow router and context. The cards are MadelineProto's pages: each `Page` the server sends
 * names its card with `page.view` (see PROTOCOL.md), and the cards talk to the script that serves them
 * (`lib/transport.ts`) instead of tweb's managers.
 */

import {Accessor, createContext, createRoot, createSignal, useContext} from 'solid-js';

import {submitForm, reloadPage} from '@lib/transport';
import type {Page, State} from '@/types';

/* ------------------------------------------------------------------ */
/* Types                                                              */
/* ------------------------------------------------------------------ */

export type CardName =
  | 'signIn'
  | 'signBot'
  | 'authCode'
  | 'password'
  | 'signUp'
  | 'signQR'
  | 'apiForm'
  | 'status';

/** Every card gets the page it shows: what the server sent for it. */
export type CardSpec = {name: CardName, page: Page};

export type AuthFlowContextValue = {
  /** The state the server sent last. */
  state: Accessor<State>;
  /** Currently mounted card (or `null` before bootstrap). */
  current: Accessor<CardSpec | null>;
  /** Switch to another card. The current card unmounts. */
  navigate(spec: CardSpec): void;
  /**
   * POST the fields of the card to the script (tweb: a call of `managers`).
   *
   * Resolves with the page when the server answers with the same card again (an error to show: in
   * `page.data.rpcError` and the error alerts); with nothing when it moved on — to another card, which
   * is then mounted, or to the end of the login, and the page reloads.
   */
  submit(fields: Record<string, string>): Promise<Page | undefined>;
  /** Logged in: leave the auth flow (tweb: bootstrap the IM page; here, reload the page). */
  toIm(): Promise<void>;
};

/** The card of a page. */
export function pageToCardSpec(page: Page): CardSpec {
  const name: CardName = (() => {
    switch(page.view) {
      case 'login.signIn':
        return 'signIn';
      case 'login.code':
        return 'authCode';
      case 'login.password':
        return 'password';
      case 'login.signUp':
        return 'signUp';
      case 'status':
        return 'status';
      default:
        // `api.*`, and any page without a card of its own: drawn from its fields
        return 'apiForm';
    }
  })();

  return {name, page};
}

/* ------------------------------------------------------------------ */
/* Module-level state                                                 */
/* ------------------------------------------------------------------ */

const [currentCard, setCurrentCard] = createRoot(() => createSignal<CardSpec | null>(null));
const [currentState, setCurrentState] = createRoot(() => createSignal<State>(null));

export {currentCard, currentState};

/** Imperative navigation entry-point, as in tweb. */
export function navigateAuth(spec: CardSpec): void {
  setCurrentCard(spec);
}

/** Start the flow on the page of a state. */
export function startAuth(state: State): void {
  setCurrentState(state);
  navigateAuth(pageToCardSpec(state.page));
}

/**
 * Type-narrowed accessor — useful inside `<Match when={matchCard('authCode')}>`.
 * Returns the spec when the current card matches, else `null`.
 */
export function matchCard(name: CardName): CardSpec | null {
  const c = currentCard();
  if(!c || c.name !== name) return null;
  return c;
}

export async function submitAuth(fields: Record<string, string>): Promise<Page | undefined> {
  const result = await submitForm(fields);
  if('loggedIn' in result) {
    await toImAuth();
    return;
  }

  setCurrentState(result.state);
  const spec = pageToCardSpec(result.state.page);
  const current = currentCard();
  // the same page (with an error) stays: its card shows the error where it is, as tweb's cards do. The
  // bot and QR cards are the sign-in page drawn differently: an error there stays there too. Pages, not
  // cards: the pages of the API ID setup share one card
  if(current && result.state.page.view === current.page.view) {
    return result.state.page;
  }

  navigateAuth(spec);
}

export async function toImAuth(): Promise<void> {
  document.getElementById('auth-pages')?.classList.add('leaving');
  reloadPage();
}

/* ------------------------------------------------------------------ */
/* Solid context                                                      */
/* ------------------------------------------------------------------ */

export const AuthFlowContext = createContext<AuthFlowContextValue>();

/**
 * Hook for cards to grab the transport + navigation API.
 *
 * Throws if called outside `<AuthFlowProvider>` to fail fast — auth cards must
 * never be mounted standalone.
 */
export function useAuthFlow(): AuthFlowContextValue {
  const ctx = useContext(AuthFlowContext);
  if(!ctx) {
    throw new Error('useAuthFlow() called outside of <AuthFlowProvider>');
  }
  return ctx;
}
