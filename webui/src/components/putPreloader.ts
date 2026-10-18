/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto.
 */

import classNames from '@helpers/string/classNames';

export function putPreloader(elem: Element, returnDiv = false): HTMLElement {
  const html = `
  <svg xmlns="http://www.w3.org/2000/svg" class="preloader-circular" viewBox="25 25 50 50">
  <circle class="preloader-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
  </svg>`;

  if(returnDiv) {
    const div = document.createElement('div');
    div.classList.add('preloader');
    div.innerHTML = html;

    if(elem) {
      elem.appendChild(div);
    }

    return div;
  }

  elem.insertAdjacentHTML('beforeend', html);
  return elem.lastElementChild as HTMLElement;
}

export function setButtonLoader(elem: HTMLButtonElement) {
  const iconElement = elem.querySelector('.tgico');
  iconElement?.remove();
  elem.disabled = true;
  putPreloader(elem);

  return () => {
    elem.replaceChildren();
    if(iconElement) elem.append(iconElement);
    elem.removeAttribute('disabled');
  };
}

/* export function parseMenuButtonsTo(to: {[name: string]: HTMLElement}, elements: HTMLCollection | NodeListOf<HTMLElement>) {
  Array.from(elements).forEach((el) => {
    const match = el.className.match(/(?:^|\s)menu-(.+?)(?:$|\s)/);
    if(!match) return;
    to[match[1]] = el as HTMLElement;
  });
} */

export function PreloaderTsx(props?: {class?: string}) {
  const div = document.createElement('div');
  div.className = classNames('preloader', props?.class);
  putPreloader(div);
  return div;
}
