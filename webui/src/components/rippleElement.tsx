/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 */

import {createRenderEffect, createSignal, onCleanup, Ref, splitProps, ValidComponent} from 'solid-js';
import {DynamicProps} from 'solid-js/web';
import ripple from '@components/ripple';
import Passthrough from '@helpers/solid/passthrough';
ripple; // keep

export default function RippleElement<T extends ValidComponent>(props: DynamicProps<T> & {
  noRipple?: boolean,
  rippleSquare?: boolean
}) {
  const [local, rest] = splitProps(props, [
    'noRipple',
    'rippleSquare',
    'component',
    'children',
    'class',
    'classList',
    'ref'
  ]);
  const [rippleElement, setRippleElement] = createSignal<HTMLElement>();
  const el = document.createElement(local.component as string || 'div');

  createRenderEffect(() => {
    if(!local.noRipple) {
      const ret = ripple(el, undefined, 'no');
      setRippleElement(ret.element);
      onCleanup(() => {
        ret.dispose();
        setRippleElement();
      });
    }
  });

  (local.ref as Ref<any>)?.(el);

  // every class goes through `classList`, which toggles key by key. Handing `class` a joined string
  // instead would make Solid assign `className`, wiping whatever the element picked up from outside
  // — `audio-48` / `search-super-item` on a playlist row, say.
  return (
    <Passthrough
      element={el}
      {...rest as any}
      classList={{
        [local.class]: !!local.class,
        'rp': !local.noRipple,
        'rp-square': !local.noRipple && !!local.rippleSquare,
        ...(local.classList || {})
      }}
    >
      {rippleElement()}
      {local.children}
    </Passthrough>
  );
}
