/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 */

import {ParentProps, JSX, createEffect, splitProps, untrack, children} from 'solid-js';
import {assign, insert} from 'solid-js/web';

export type PassthroughProps<E extends Element> = {element: E} & ParentProps & JSX.HTMLAttributes<E>;
export default function Passthrough<E extends Element>(props: PassthroughProps<E>): E {
  const element = untrack(() => props.element);
  const resolved = children(() => props.children);

  // Hand children to `insert` once: it sets up its own reactive tracking and
  // properly REMOVES the previous DOM nodes before inserting new ones.
  //
  // The previous implementation passed `children: resolved` to `assign` inside
  // a createEffect that re-fires on every prop change. Each re-fire ran the
  // children code path again with no notion of what was inserted before, so
  // when `resolved()` returned a new HTMLElement (e.g. a freshly-built i18n
  // span when a label changed), the new node was appended ALONGSIDE the old
  // one — producing visible duplicates like "JUMP TO DATE JUMP TO DATE".
  //
  // The createEffect below now skips children (`skipChildren: true`) and only
  // re-applies the other props.
  insert(element, resolved);

  // `assign` needs to see what it applied last time, or it cannot undo anything: a `classList` key
  // that goes false is never taken off the element, and a listener is re-added on every run instead
  // of being swapped. Defaulting `prevProps` to a fresh `{}` each run threw all of that away.
  const prevProps = {};

  createEffect(() => {
    const [_, others] = splitProps(props, ['element', 'children']);
    assign(element, others, element instanceof SVGElement, true, prevProps);
  });

  return element;
}
