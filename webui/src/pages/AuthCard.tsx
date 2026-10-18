/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 */

import {JSX, Show} from 'solid-js';

import classNames from '@helpers/string/classNames';

import styles from '@/pages/authFlow.module.scss';

/**
 * Reusable card scaffold for the auth flow.
 *
 * Renders a rounded surface and (optionally) wraps the children in
 * `.input-wrapper`. The header (sticker / title / subtitle) lives in `header`,
 * which sits above the input-wrapper. Most cards pass a `<MediaHeader>` here.
 *
 * ```tsx
 * <AuthCard
 *   class={styles.pageSignIn}
 *   header={
 *     <MediaHeader>
 *       <MediaHeader.Sticker element={<Logo/>} />
 *       <MediaHeader.Title>{i18n('Login.Title')}</MediaHeader.Title>
 *       <MediaHeader.Subtitle>{i18n('Login.StartText')}</MediaHeader.Subtitle>
 *     </MediaHeader>
 *   }
 * >
 *   {countryInput.container}
 *   {telInput.container}
 *   <button>{i18n('Login.Next')}</button>
 * </AuthCard>
 * ```
 */
export type AuthCardProps = {
  /** Page-modifier class — typically `styles.pageSignIn`, etc. */
  class?: string;
  /**
   * Anything above the form fields — sticker, title, subtitle. Renders
   * outside `.input-wrapper`. A `<MediaHeader>` is the typical input.
   */
  header?: JSX.Element;
  /**
   * Whether to wrap `children` in `.input-wrapper`. Default `true` — the
   * typical card has a list of fields/buttons. Set to `false` when the
   * children manage their own layout (e.g. signQR mixes a help list with
   * its own input-wrapper, signImport just shows a preloader).
   */
  inputWrapper?: boolean;
  /** Form fields, buttons, anything that goes after the header. */
  children?: JSX.Element;
};

export default function AuthCard(props: AuthCardProps): JSX.Element {
  const useInputWrapper = () => props.inputWrapper !== false;

  return (
    <div class={classNames(styles.card, props.class)}>
      {props.header}
      <Show when={useInputWrapper()} fallback={props.children}>
        <div class="input-wrapper">{props.children}</div>
      </Show>
    </div>
  );
}
