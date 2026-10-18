/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 */

import {doubleRaf} from '@helpers/schedulers';
import pause from '@helpers/schedulers/pause';

/**
 * Calls `el.focus()` once the element is in the DOM *and* the animation that
 * brought it there has finished.
 *
 * Two things have to be waited out, and both of them bite:
 *
 * - **Attachment.** Solid's `onMount` (and `fastRaf` / `setTimeout(..., 0)` from
 *   inside it) can fire BEFORE the element is attached when the component lives
 *   inside a transition with `mode="outin"` — the new component instance is
 *   created as soon as the source signal updates, but its node is only inserted
 *   after the outgoing one finishes its exit. `HTMLElement.focus()` on a
 *   detached node is silently ignored.
 * - **The entrance.** Focusing scrolls the nearest scrollable ancestor to reveal
 *   the element, so doing it while an ancestor is still sliding in (the auth
 *   cards translate 24px, a sidebar tab slides, a popup's slider pages) scrolls
 *   to a position the element is about to leave — the entrance visibly breaks.
 *   A transition is only attached a frame or two after insertion, hence the
 *   `doubleRaf` before sampling, and the loop for animations that chain.
 *
 * Pass `signal: () => boolean` to cancel — useful when the host may be unmounted
 * before it ever attaches (e.g. the user clicks back during enter).
 *
 * Returns a cancel function.
 */

/**
 * Never hold the focus hostage to an animation that does not end: the document
 * timeline stops advancing while the page is not being rendered, so a tab put in
 * the background mid-entrance can otherwise sit on a pending `finished` forever.
 */
const SETTLE_TIMEOUT = 1000;

function runningAnimations(el: HTMLElement): Animation[] {
  const animations: Animation[] = [];

  // an element's own `getAnimations()` covers only itself, and what moves the
  // element is usually an ancestor (the card, the tab, the popup page)
  for(let node: HTMLElement = el; node; node = node.parentElement) {
    if(typeof node.getAnimations !== 'function') break;
    for(const animation of node.getAnimations()) {
      // a decorative loop (a spinner above the field) never finishes and is not
      // what the element is waiting for
      if(animation.effect?.getTiming().iterations === Infinity) continue;
      animations.push(animation);
    }
  }

  return animations;
}

export default function focusWhenSettled(
  el: HTMLElement,
  signal?: () => boolean
): () => void {
  let cancelled = false;
  let rafId = 0;

  const alive = () => !cancelled && (!signal || signal());

  const settle = async() => {
    const deadline = Date.now() + SETTLE_TIMEOUT;

    await doubleRaf();

    while(alive() && Date.now() < deadline) {
      const animations = runningAnimations(el);
      if(!animations.length) break;

      await Promise.race([
        // an interrupted transition rejects its `finished` — that is still "done"
        Promise.all(animations.map((animation) => animation.finished.catch(() => {}))),
        pause(Math.max(0, deadline - Date.now()))
      ]);
    }

    if(alive()) el.focus();
  };

  const tick = () => {
    if(!alive()) return;
    if(el.isConnected) {
      settle();
      return;
    }
    rafId = requestAnimationFrame(tick);
  };

  rafId = requestAnimationFrame(tick);

  return () => {
    cancelled = true;
    cancelAnimationFrame(rafId);
  };
}
