/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto.
 */

/*
 * Originally from:
 * https://github.com/zhukov/webogram
 * Copyright (C) 2014 Igor Zhukov <igor.beatle@gmail.com>
 * https://github.com/zhukov/webogram/blob/master/LICENSE
 */

import IS_TOUCH_SUPPORTED from '@environment/touchSupport';

export default function placeCaretAtEnd(el: HTMLElement, ignoreTouchCheck = false, focus = true) {
  // Safari leaves `activeElement` null when nothing is focused (other engines fall back to <body>),
  // and reading `.tagName` off it threw out of the phone/search inputs that call this on touch.
  // No focused element means no editable one, which is the case we bail out on anyway.
  const activeElement = el.ownerDocument.activeElement as HTMLElement;
  const isEditableFocused = !!activeElement &&
    (activeElement.tagName === 'INPUT' || activeElement.isContentEditable);
  if(IS_TOUCH_SUPPORTED && (!ignoreTouchCheck || !isEditableFocused)) {
    return;
  }

  focus && el.focus();
  if(el instanceof HTMLInputElement) {
    const length = el.value.length;
    el.selectionStart = length;
    el.selectionEnd = length;
  } else {
    const view = el.ownerDocument.defaultView;
    if(!view) return;

    const range = el.ownerDocument.createRange();
    range.selectNodeContents(el);
    range.collapse(false);
    const sel = view.getSelection();
    if(!sel) return;

    sel.removeAllRanges();
    sel.addRange(range);
  }
}

