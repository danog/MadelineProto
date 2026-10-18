/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto.
 */

import {JSX, Ref} from 'solid-js';
import {Dynamic} from 'solid-js/web';

import classNames from '@helpers/string/classNames';

import styles from '@components/mediaHeader.module.scss';

/**
 * Compound header used by auth cards, intro popups, and other "icon → title →
 * subtitle" surfaces. Each piece is a sub-component with its own props, so a
 * caller picks only what they need:
 *
 * ```tsx
 * <MediaHeader>
 *   <MediaHeader.Sticker name="key" size={120} />
 *   <MediaHeader.Title>{i18n('Login.Title')}</MediaHeader.Title>
 *   <MediaHeader.Subtitle>{i18n('Login.StartText')}</MediaHeader.Subtitle>
 * </MediaHeader>
 * ```
 *
 * `MediaHeader.Sticker` accepts either a built-in lottie asset (`name`) or a
 * custom JSX element (`element`) — useful for SVG logos, avatars, canvases,
 * monkey components, etc. Cards that need an imperative ref to the slot can
 * pass a stable container element via `element`. It is a square, centred box;
 * media that is not square (a wide illustration) goes straight into
 * `<MediaHeader>` as a child instead, with its own sizing. A tab passes its
 * asset as `animationData` instead of `name`, fetched from its `getInitArgs`,
 * and does not slide in before the sticker has drawn its first frame.
 *
 * The type scale is deliberately narrow — a 24px or a 20px title (`size`), body
 * copy or a smaller grey / danger variant (`color`). Reach for a prop rather
 * than restyling a copy of it. A subtitle honours the line breaks its language
 * string carries, so translated copy never needs markup to break a line.
 *
 * A header can also sit on a painted backdrop, which is how collectible gifts
 * are presented — the copy turns white and stacks above it:
 *
 * ```tsx
 * <MediaHeader onBackdrop>
 *   <MediaHeader.Backdrop><StarGiftBackdrop … /></MediaHeader.Backdrop>
 *   <MediaHeader.Sticker size={120} ref={stickerContainer} />
 *   <MediaHeader.Title>{gift.title}</MediaHeader.Title>
 * </MediaHeader>
 * ```
 *
 * Spacing: the block owns the rhythm between its own pieces (a single `gap`),
 * and the pieces themselves declare no vertical margins — so a caller is free
 * to set any margin on any of them without racing this module's declarations
 * through CSS-module load order. Distance around the whole block comes from
 * `marginTop` / `marginBottom`; a block that wants a different internal rhythm
 * overrides `gap` on its own class. A `Title`/`Subtitle` pair rendered loose,
 * without a `<MediaHeader>` around it, gets no gap at all — wrap the pair.
 */
function MediaHeader(props: {
  class?: string,
  children?: JSX.Element,
  marginTop?: boolean,
  marginBottom?: boolean,
  /**
   * The block sits on a coloured `MediaHeader.Backdrop` — the copy turns white and
   * stacks above it. Collectible gifts paint their own backdrop this way.
   */
  onBackdrop?: boolean,
  /** Align the copy to the text start instead of centring it — a confirm box reads as a paragraph. */
  align?: 'start'
}): JSX.Element {
  return (
    <div
      class={classNames(
        styles.container,
        props.align === 'start' && styles.alignStart,
        props.onBackdrop && styles.onBackdrop,
        props.marginTop && styles.marginTop,
        props.marginBottom && styles.marginBottom,
        props.class
      )}
    >
      {props.children}
    </div>
  );
}

/* ------------------------------------------------------------------ */
/* Backdrop                                                           */
/* ------------------------------------------------------------------ */

/**
 * Fills the header behind everything else — pass the painted element (a gift's
 * `StarGiftBackdrop`, say) as the child. Needs `onBackdrop` on the header.
 */
MediaHeader.Backdrop = function MediaHeaderBackdrop(props: {
  class?: string,
  children?: JSX.Element
}): JSX.Element {
  return <div class={classNames(styles.backdrop, props.class)}>{props.children}</div>;
};

/* ------------------------------------------------------------------ */
/* Sticker                                                            */
/* ------------------------------------------------------------------ */

// * MadelineProto: the auth cards hand in their own elements (the monkeys draw themselves), so the built-in
// * lottie assets of tweb's sticker slot are left out
export type MediaHeaderStickerProps = {
  /** Custom JSX (SVG, canvas, monkey container, …). */
  element?: JSX.Element | (() => JSX.Element),
  /** Sticker size in px (drives `--sticker-size` and the lottie's render dimensions). Default 130. */
  size?: number,
  /** Extra class on the sticker wrapper. */
  class?: string,
  /** Fires once the sticker is ready to display: an `element` is ready the moment it is handed over. */
  onReady?: () => void,
  ref?: Ref<HTMLDivElement>
};

MediaHeader.Sticker = function MediaHeaderSticker(props: MediaHeaderStickerProps): JSX.Element {
  const size = () => props.size || 130;

  if(props.element) {
    props.onReady?.();
  }

  return (
    <div
      class={classNames(styles.sticker, props.class)}
      style={{'--sticker-size': size() + 'px'}}
      ref={props.ref}
    >
      {typeof(props.element) === 'function' ? props.element() : props.element}
    </div>
  );
};

/* ------------------------------------------------------------------ */
/* Title                                                              */
/* ------------------------------------------------------------------ */

export type MediaHeaderTitleProps = {
  class?: string,
  /** Semantic element for surfaces where this is the page or dialog heading. */
  tag?: 'div' | 'h1' | 'h2',
  /** Font size in px: 24 is the hero scale (default), 20 the compact one narrow popups use. */
  size?: 20 | 24,
  children?: JSX.Element
};

MediaHeader.Title = function MediaHeaderTitle(props: MediaHeaderTitleProps): JSX.Element {
  return (
    <Dynamic
      component={props.tag || 'div'}
      data-popup-title
      class={classNames(
        styles.title,
        props.size === 20 && styles.title20,
        props.class
      )}
    >
      {props.children}
    </Dynamic>
  );
};

/* ------------------------------------------------------------------ */
/* Subtitle                                                           */
/* ------------------------------------------------------------------ */

export type MediaHeaderSubtitleProps = {
  class?: string,
  /**
   * `secondary` is the smaller grey variant intro popups use for a hint;
   * `danger` reports that the thing is gone or failed. Default is body copy.
   */
  color?: 'secondary' | 'danger',
  children?: JSX.Element
};

MediaHeader.Subtitle = function MediaHeaderSubtitle(props: MediaHeaderSubtitleProps): JSX.Element {
  return (
    <div
      class={classNames(
        styles.subtitle,
        props.color && styles[props.color],
        props.class
      )}
    >
      {props.children}
    </div>
  );
};

export default MediaHeader;
