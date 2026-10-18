// Whether the notices of the server (missing extensions, the translation) were dismissed: by their close
// button, or by the first form submitted (they were seen by then). For the tab, across the reloads of the flow.

import {createRoot, createSignal} from 'solid-js';

const STORAGE_KEY = 'madeline-webui-notices-dismissed';

function stored(): boolean {
  try {
    return window.sessionStorage.getItem(STORAGE_KEY) === '1';
  } catch(err) {
    return false;
  }
}

const [noticesDismissed, setNoticesDismissed] = createRoot(() => createSignal(stored()));

export {noticesDismissed};

export function dismissNotices() {
  setNoticesDismissed(true);
  try {
    window.sessionStorage.setItem(STORAGE_KEY, '1');
  } catch(err) {}
}

/** For the tests. */
export function resetNotices() {
  setNoticesDismissed(false);
  try {
    window.sessionStorage.removeItem(STORAGE_KEY);
  } catch(err) {}
}
