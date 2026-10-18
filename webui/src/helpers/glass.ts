// MadelineProto: the liquid glass can be switched off, leaving tweb's look: `.glass` on <html> turns it on
// (scss/glass/, components/liquidGlass.tsx). It's on until the user switches it off.

import {createSignal} from 'solid-js';

const STORAGE_KEY = 'madeline-webui-glass';

function stored(): boolean {
  try {
    return window.localStorage.getItem(STORAGE_KEY) !== '0';
  } catch(err) {
    return true;
  }
}

const [glass, setGlassSignal] = createSignal(true);

/** Whether the glass is on (reactive). */
export {glass};

export function setGlass(on: boolean) {
  document.documentElement.classList.toggle('glass', on);
  setGlassSignal(on);
  // the QR code is drawn on the colours of the theme (--qr-background)
  window.dispatchEvent(new CustomEvent('madeline-theme-change'));
}

export function toggleGlass() {
  const on = !glass();
  setGlass(on);
  try {
    window.localStorage.setItem(STORAGE_KEY, on ? '1' : '0');
  } catch(err) {}
}

/** Apply the user's choice: before the first render, so the page isn't drawn in the other look first. */
export function initGlass() {
  setGlass(stored());
}
