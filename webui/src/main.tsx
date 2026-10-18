/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto: the start of the auth flow (tweb: src/index.ts and mountAuthFlow.tsx), on the
 * page MadelineProto's templates serve (see PROTOCOL.md): the state in `#madeline-state`, the stylesheet in
 * `link#madeline-app-css` (media="print", so it doesn't apply without JavaScript), the root in
 * `#madeline-app`.
 */

import './scss/app.scss';

import {render} from 'solid-js/web';

import IS_TOUCH_SUPPORTED from '@environment/touchSupport';
import {initTheme} from '@helpers/theme';
import {initGlass} from '@helpers/glass';
import lottieLoader from '@lib/lottie/lottieLoader';
import I18n from '@lib/langPack';
import type {State} from '@/types';

import {startAuth} from '@/pages/authFlow';
import AuthCardsHost from '@/pages/AuthCardsHost';

/** tweb's classes: the hover styles only without touch, animations off if the system asks for less motion. */
function initDocument() {
  document.documentElement.classList.add(IS_TOUCH_SUPPORTED ? 'is-touch' : 'no-touch');
  const reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
  document.body.classList.add(...(reduced ? ['animation-level-0'] : ['animation-level-1', 'animation-level-2']));
}

/** Enable the stylesheet, and wait for it: the first render must not be unstyled. */
function loadStylesheet(): Promise<void> {
  const link = document.getElementById('madeline-app-css') as HTMLLinkElement | null;
  if(!link) {
    return Promise.resolve();
  }

  link.media = 'all';
  if(link.sheet) {
    return Promise.resolve();
  }

  return new Promise((resolve) => {
    link.addEventListener('load', () => resolve(), {once: true});
    // a missing stylesheet must not keep the page empty
    link.addEventListener('error', () => resolve(), {once: true});
  });
}

/** Mount the auth flow in `root`, on the page of `state`. */
export function mount(root: HTMLElement, state: State) {
  initDocument();
  initTheme();
  initGlass();
  lottieLoader.setAssetUrl(state.assetUrl ?? '');
  // the strings of the session's language, before anything is rendered
  I18n.applyStrings((state.strings ?? {}) as Record<string, unknown>, state.lang);
  if(state.lang) document.documentElement.lang = state.lang;
  if(state.dir) {
    document.documentElement.dir = state.dir;
    document.documentElement.classList.toggle('is-rtl', state.dir === 'rtl');
  }
  startAuth(state);
  root.replaceChildren();
  return render(() => <AuthCardsHost />, root);
}

export async function start() {
  const stateElement = document.getElementById('madeline-state');
  const root = document.getElementById('madeline-app');
  if(!stateElement || !root) {
    // custom templates without the app's elements are left alone
    return;
  }

  const state = JSON.parse(stateElement.textContent || '{}') as State;
  await loadStylesheet();
  mount(root, state);
}

if(!import.meta.env.VITEST && !(window as any).__MADELINE_DEV__) {
  start();
}
