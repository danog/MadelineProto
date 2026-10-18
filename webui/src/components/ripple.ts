/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto.
 */

import findUpClassName from '@helpers/dom/findUpClassName';
import IS_TOUCH_SUPPORTED from '@environment/touchSupport';
import findUpAsChild from '@helpers/dom/findUpAsChild';
import {fastRaf} from '@helpers/schedulers';
import liteMode from '@helpers/liteMode';
import {Accessor, createRenderEffect, onCleanup} from 'solid-js';


declare module 'solid-js' {
  namespace JSX {
    interface Directives {
      /**
       * To be used in solid-js as `<button use:ripple />`
       */
      ripple: boolean;
    }
  }
}

/**
 * One wave of a ripple in `container` (a `.c-ripple`), grown from where the press began. Returns
 * what ends it: the wave fades out once the press is over, but not before it has half grown.
 * `isStale` drops a wave a newer press has overtaken before it was drawn.
 */
export function startRippleWave(
  container: HTMLElement,
  clientX: number,
  clientY: number,
  {isStale, onEnd}: {isStale?: () => boolean, onEnd?: () => void} = {}
) {
  const startTime = Date.now();
  const circle = document.createElement('div');
  const duration = +(container.ownerDocument.defaultView || window).getComputedStyle(container).getPropertyValue('--ripple-duration').replace('s', '') * 1000;

  fastRaf(() => {
    if(isStale?.()) {
      return;
    }

    const rect = container.getBoundingClientRect();
    circle.classList.add('c-ripple__circle');

    const clickX = clientX - rect.left;
    const clickY = clientY - rect.top;

    const radius = Math.sqrt((Math.abs(clickY - rect.height / 2) + rect.height / 2) ** 2 + (Math.abs(clickX - rect.width / 2) + rect.width / 2) ** 2);
    const size = radius;

    // center of circle
    const x = clickX - size / 2;
    const y = clickY - size / 2;

    circle.style.width = circle.style.height = size + 'px';
    circle.style.left = x + 'px';
    circle.style.top = y + 'px';
    circle.style.opacity = '0';

    container.append(circle);

    void circle.offsetWidth; // force reflow
    circle.style.opacity = '';
  });

  return () => {
    const elapsedTime = Date.now() - startTime;
    const cb = () => {
      fastRaf(() => {
        circle.remove();
      });

      onEnd?.();
    };
    if(elapsedTime < duration) {
      const delay = Math.max(duration - elapsedTime, duration / 2);
      setTimeout(() => circle.classList.add('hiding'), Math.max(delay - duration / 2, 0));

      setTimeout(cb, delay);
    } else {
      circle.classList.add('hiding');
      setTimeout(cb, duration / 2);
    }
  };
}

let rippleClickId = 0;
function _ripple(
  elem: HTMLElement,
  prepend: boolean | 'no' = true,
  callback: (id: number) => Promise<boolean | void> = () => Promise.resolve(),
  onEnd: (id: number) => void = null,
  attachListenerTo = elem
) {
  // return;
  // * a ripple of its own only: a control inside with its own ripple (a web-page box's footer
  // * button) is no reason for the box to go without one
  if(elem.querySelector(':scope > .c-ripple')) return;
  elem.classList.add('rp');

  const r = document.createElement('div');
  r.classList.add('c-ripple');

  const isSquare = elem.classList.contains('rp-square');
  if(isSquare) {
    r.classList.add('is-square');
  }

  if(prepend !== 'no') {
    elem[prepend ? 'prepend' : 'append'](r);
  }

  let handler: () => void, lastHandler: typeof handler;
  const drawRipple = (clientX: number, clientY: number) => {
    const clickId = rippleClickId++;

    const _handler = handler = lastHandler = () => {
      release();

      if(!IS_TOUCH_SUPPORTED) {
        // Same window the listeners were attached to (the element's own — the Document PiP window
        // when the client is popped out), not the main `window`.
        const win = r.ownerDocument.defaultView || window;
        win.removeEventListener('contextmenu', handler);
        win.removeEventListener('mousemove', handler);
      }

      handler = null;
      touchStartFired = false;
    };

    callback?.(clickId);

    const release = startRippleWave(r, clientX, clientY, {
      isStale: () => lastHandler !== _handler,
      onEnd: () => onEnd?.(clickId)
    });
  };

  const isRippleUnneeded = (e: Event) => {
    return e.target !== elem && (
      ['BUTTON', 'A'].includes((e.target as HTMLElement).tagName) ||
        findUpClassName(e.target as HTMLElement, 'c-ripple') !== r
    ) && (
      attachListenerTo === elem ||
        !findUpAsChild(e.target as HTMLElement, attachListenerTo)
    ) && !findUpClassName(e.target, 'checkbox-field');
  };

  // TODO: rename this variable
  let touchStartFired = false;
  if(IS_TOUCH_SUPPORTED) {
    const touchEnd = () => {
      handler?.();
    };

    const onTouchStart = (e: TouchEvent) => {
      if(!liteMode.isAvailable('animations')) {
        return;
      }

      // console.log('ripple touchstart', e);
      if(e.touches.length > 1 || touchStartFired || isRippleUnneeded(e)) {
        return;
      }

      // console.log('touchstart', e);
      touchStartFired = true;

      const {clientX, clientY} = e.touches[0];
      drawRipple(clientX, clientY);
      attachListenerTo.addEventListener('touchend', touchEnd, {once: true});

      window.addEventListener('touchmove', (e) => {
        e.cancelBubble = true;
        e.stopPropagation();
        touchEnd();
        attachListenerTo.removeEventListener('touchend', touchEnd);
      }, {once: true});
    };

    attachListenerTo.addEventListener('touchstart', onTouchStart, {passive: true});
    return {
      dispose: () => {
        attachListenerTo.removeEventListener('touchstart', onTouchStart);
        r.remove();
      },
      element: r
    };
  } else {
    const onMouseDown = (e: MouseEvent) => {
      if(![0, 2].includes(e.button)) { // only left and right buttons
        return;
      }

      if(!liteMode.isAvailable('animations')) {
        return;
      }
      // console.log('ripple mousedown', e, e.target, findUpClassName(e.target as HTMLElement, 'c-ripple') === r);

      if(attachListenerTo.dataset.ripple === '0' || isRippleUnneeded(e)) {
        return;
      } else if(touchStartFired) {
        touchStartFired = false;
        return;
      }

      const {clientX, clientY} = e;
      drawRipple(clientX, clientY);
      // Attach the ripple-end listeners to the element's OWN window — in a Document PiP window the
      // mouseup fires there, not on the main `window`, so binding to main left the ripple stuck.
      const win = attachListenerTo.ownerDocument.defaultView || window;
      win.addEventListener('mouseup', handler, {once: true, passive: true});
      win.addEventListener('contextmenu', handler, {once: true, passive: true});
    };

    attachListenerTo.addEventListener('mousedown', onMouseDown, {passive: true});
    return {
      dispose: () => {
        attachListenerTo.removeEventListener('mousedown', onMouseDown);
        r.remove();
      },
      element: r
    };
  }
}

export default function ripple(elem: HTMLElement, accessor?: Accessor<boolean>, prepend?: boolean | 'no') {
  if(accessor) {
    createRenderEffect(() => {
      const value = accessor();
      if(value === undefined || value) {
        const ret = _ripple(elem, prepend);
        onCleanup(() => {
          ret?.dispose();
        });
      }
    });

    return;
  }

  const ret = _ripple(elem, prepend);
  return ret;
}
