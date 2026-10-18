/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 */

export default function findUpTag(el: any, tag: string): HTMLElement {
  return el.closest(tag);
  /* if(el.tagName === tag) return el; // 03.02.2020

  while(el.parentElement) {
    el = el.parentElement;
    if(el.tagName === tag)
      return el;
  }
  return null; */
}
