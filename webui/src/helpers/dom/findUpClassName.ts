/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 */

// export function findUpClassName<T>(el: any, className: string): T;
export default function findUpClassName(el: EventTarget | {closest: (selector: string) => any}, className: string): HTMLElement {
  return (el as any).closest('.' + className);
  /* if(el.classList.contains(className)) return el; // 03.02.2020

  while(el.parentElement) {
    el = el.parentElement;
    if(el.classList.contains(className))
      return el;
  }
  return null; */
}
