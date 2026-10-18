/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto.
 */

// * Jolly Cobra's schedulers
type NoneToVoidFunction = () => void;

/*
export function throttleWithTickEnd<F extends AnyToVoidFunction>(fn: F) {
  return throttleWith(onTickEnd, fn);
}

export function throttleWithNow<F extends AnyToVoidFunction>(fn: F) {
  return throttleWith(runNow, fn);
}

export function onTickEnd(cb: NoneToVoidFunction) {
  Promise.resolve().then(cb);
}

function runNow(fn: NoneToVoidFunction) {
  fn();
} */

/**
 * Callbacks batched into one frame are unrelated to each other, so one of them throwing must not
 * cancel the rest: `fastRafPromise` resolves from a callback queued in here, and dropping that
 * resolve leaves its cached promise pending forever — every later `fastRafPromise()` then returns
 * the same dead promise and silently parks whatever awaits it for the lifetime of the tab.
 */
function runFastRafCallback(callback: NoneToVoidFunction) {
  try {
    callback();
  } catch(err) {
    console.error('fastRaf callback error:', err);
  }
}

let fastRafCallbacks: NoneToVoidFunction[] | undefined;
export function fastRaf(callback: NoneToVoidFunction) {
  if(!fastRafCallbacks) {
    fastRafCallbacks = [callback];

    requestAnimationFrame(() => {
      const currentCallbacks = fastRafCallbacks!;
      fastRafCallbacks = undefined;
      currentCallbacks.forEach(runFastRafCallback);
    });
  } else {
    fastRafCallbacks.push(callback);
  }
}

let fastRafConventionalCallbacks: NoneToVoidFunction[] | undefined, processing = false;
export function fastRafConventional(callback: NoneToVoidFunction) {
  if(!fastRafConventionalCallbacks) {
    fastRafConventionalCallbacks = [callback];

    requestAnimationFrame(() => {
      processing = true;
      // same reasoning as above, plus a throw escaping here would leave the queue non-empty and
      // `processing` stuck true, degrading every later call to synchronous execution for good
      for(let i = 0; i < fastRafConventionalCallbacks.length; ++i) {
        runFastRafCallback(fastRafConventionalCallbacks[i]);
      }

      fastRafConventionalCallbacks = undefined;
      processing = false;
    });
  } else if(processing) {
    callback();
  } else {
    fastRafConventionalCallbacks.push(callback);
  }
}

let rafPromise: Promise<void>;
export function fastRafPromise() {
  if(rafPromise) return rafPromise;

  rafPromise = new Promise<void>((resolve) => fastRaf(() => resolve()));
  rafPromise.then(() => {
    rafPromise = undefined;
  });

  return rafPromise;
}

export function doubleRaf() {
  return new Promise<void>((resolve) => {
    fastRaf(() => {
      fastRaf(resolve);
    });
  });
}
