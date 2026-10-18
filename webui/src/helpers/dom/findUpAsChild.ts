/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 */

export default function findUpAsChild<T extends {parentElement: HTMLElement}>(el: T, parent: HTMLElement): T {
  if(!el) return null;
  if(el.parentElement === parent) return el;

  while(el.parentElement) {
    el = el.parentElement as any;
    if(el.parentElement === parent) {
      return el;
    }
  }

  return null;
}
