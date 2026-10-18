/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 */

export default function replaceContent(elem: HTMLElement, node: string | Node) {
  if(typeof(node) === 'string') {
    elem.textContent = node;
    return;
  }

  // * children.length doesn't count text nodes
  const firstChild = elem.firstChild;
  if(firstChild) {
    if(elem.lastChild === firstChild) {
      firstChild.replaceWith(node);
    } else {
      elem.textContent = '';
      elem.append(node);
    }
  } else {
    elem.append(node);
  }
}
