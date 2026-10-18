/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 */

import classNames from '@helpers/string/classNames';
import {batch, createMemo, createSignal, JSX, mergeProps, onCleanup, onMount, Ref} from 'solid-js';
import styles from './autoHeight.module.scss';


export const AutoHeight = (inProps: {
  ref?: Ref<HTMLDivElement>;
  children: JSX.Element;
  overflowHidden?: boolean;
  outerClass?: string;
  hasTransition?: boolean;
}) => {
  const props = mergeProps({hasTransition: true}, inProps);

  let contentRef!: HTMLDivElement;

  const [canHaveHeight, setCanHaveHeight] = createSignal(false);
  const [height, setHeight] = createSignal(0);

  const canHaveHeightAndTransition = createMemo(() => canHaveHeight() && props.hasTransition);

  onMount(() => {
    const observer = new ResizeObserver(() => {
      // * a hidden screen (a sidebar tab under another) reports nothing to measure: keep the height
      // * it had, or it would grow back from zero every time the screen shows again
      if(!contentRef.getClientRects().length) {
        return;
      }

      batch(() => {
        setCanHaveHeight(true);
        setHeight(contentRef.offsetHeight);
      });
    });

    observer.observe(contentRef);

    onCleanup(() => observer.disconnect());
  });

  return (
    <div
      ref={props.ref}
      class={classNames(props.outerClass, styles.outer)}
      classList={{
        [styles.overflowHidden]: props.overflowHidden,
        [styles.hasTransition]: canHaveHeightAndTransition()
      }}
      style={{
        'height': canHaveHeightAndTransition() ? `${height()}px` : undefined
      }}
    >
      <div ref={contentRef}>{props.children}</div>
    </div>
  );
};
