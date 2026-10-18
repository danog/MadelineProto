/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 */

/**
 * Runs `callback` once `element` is out of the document — for teardown that must outlive the scope
 * that rendered it, but not the element itself.
 *
 * `<Transition>` keeps a leaving element in place for its exit animation while Solid has already
 * disposed the component that rendered it, so anything torn down in `onCleanup` is gone while the
 * element is still on screen. An offscreen lottie canvas is the visible case: destroying its player
 * blanks the canvas at once, and the sticker vanishes mid-fade.
 *
 * `timeout` is the backstop for an element taken away with its whole subtree (the observer watches
 * its parent, which is then removed unobserved): the callback runs by then at the latest.
 */
export default function whenDetached(element: HTMLElement, callback: () => void, timeout = 1000) {
  const parent = element?.parentElement;
  if(!element?.isConnected || !parent) {
    callback();
    return;
  }

  let done = false;
  const finish = () => {
    if(done) return;
    done = true;
    observer.disconnect();
    clearTimeout(timer);
    callback();
  };

  const observer = new MutationObserver(() => {
    if(!element.isConnected) finish();
  });
  observer.observe(parent, {childList: true});
  const timer = self.setTimeout(finish, timeout);
}
