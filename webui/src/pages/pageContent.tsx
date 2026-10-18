// What every card shows of the page the server sent, in tweb's style: its texts (trusted markup or
// plain text), its error, and its notices (missing extensions, an update...).

import {For, JSX, Show, createMemo, createSignal} from 'solid-js';

import I18n, {i18n} from '@lib/langPack';
import type {Alert, Page, Text} from '@/types';

import styles from '@/pages/authFlow.module.scss';

/** A text of the server: plain text, or `{html}` (trusted markup built by MadelineProto, never user input). */
export function RichText(props: {text: Text, class?: string}): JSX.Element {
  return (
    <Show when={typeof(props.text) !== 'string'} fallback={<span class={props.class}>{props.text as string}</span>}>
      <span class={props.class} innerHTML={(props.text as {html: string}).html} />
    </Show>
  );
}

/**
 * The page without the error of its last submission: the sign-in, bot and QR cards are the same page
 * drawn differently, and an error of one (a wrong token) doesn't belong on another.
 */
export function withoutErrors(page: Page): Page {
  const data = {...page.data};
  delete data.rpcError;
  return {...page, data, alerts: page.alerts.filter((alert) => alert.level !== 'error')};
}

export function isEmptyText(text: Text | null | undefined): boolean {
  return text == null || (typeof(text) === 'string' ? text === '' : text.html === '');
}

function textContent(text: Text): string {
  if(typeof(text) === 'string') return text;
  const div = document.createElement('div');
  div.innerHTML = text.html;
  return div.textContent;
}

/**
 * The error of the last submission: the RPC error through tweb's lang keys when there is one for it
 * (`PHONE_CODE_INVALID`: "Invalid code"), else the first error alert of the server.
 */
export function pageError(page: Page | undefined): JSX.Element | undefined {
  if(!page) return;
  const rpcError = page.data?.rpcError;
  if(rpcError && I18n.has(rpcError)) {
    return i18n(rpcError);
  }

  const alert = page.alerts.find((alert) => alert.level === 'error');
  if(alert) {
    return <RichText text={alert.message} />;
  }

  if(rpcError) {
    return rpcError;
  }
}

/** The same, as plain text: for the label of a field or the text of a button. */
export function pageErrorText(page: Page | undefined): string | undefined {
  if(!page) return;
  const rpcError = page.data?.rpcError;
  if(rpcError && I18n.has(rpcError)) {
    return I18n.format(rpcError, true);
  }

  const alert = page.alerts.find((alert) => alert.level === 'error');
  if(alert) {
    return textContent(alert.message);
  }

  return rpcError;
}

/** The icon of a notice: a disc of its color, with a glyph cut out of it. */
function NoticeIcon(props: {level: Alert['level']}): JSX.Element {
  const glyph = () => {
    switch(props.level) {
      case 'success':
        return <path d="M6.5 10.4l2.3 2.3 4.7-5.2" fill="none" stroke="var(--notice-glyph)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />;
      case 'info':
        return <><circle cx="10" cy="6.4" r="1.15" fill="var(--notice-glyph)" /><rect x="9" y="8.6" width="2" height="5.6" rx="1" fill="var(--notice-glyph)" /></>;
      default:
        return <><rect x="9" y="5.2" width="2" height="6.4" rx="1" fill="var(--notice-glyph)" /><circle cx="10" cy="14" r="1.15" fill="var(--notice-glyph)" /></>;
    }
  };
  return (
    <svg class={styles.noticeIcon} viewBox="0 0 20 20" aria-hidden="true">
      <circle cx="10" cy="10" r="10" fill="currentColor" />
      {glyph()}
    </svg>
  );
}

function Notice(props: {alert: Alert, pager?: JSX.Element}): JSX.Element {
  return (
    <div class={styles.notice} classList={{[styles[props.alert.level]]: true}} role={props.alert.level === 'error' ? 'alert' : 'status'}>
      <NoticeIcon level={props.alert.level} />
      <div class={styles.noticeBody}>
        <Show when={!isEmptyText(props.alert.title)}>
          <div class={styles.noticeTitle}><RichText text={props.alert.title} /></div>
        </Show>
        <RichText text={props.alert.message} />
        <Show when={!isEmptyText(props.alert.code)}>
          <pre class={styles.noticeCode}><RichText text={props.alert.code} /></pre>
        </Show>
      </div>
      {props.pager}
    </div>
  );
}

/**
 * Warnings and information of the server (a missing extension, a new version), under the header: the errors
 * one under the other (they are what to act on), the rest in a single notice, one at a time ("1/3 >").
 */
export function PageNotices(props: {alerts: Alert[], errors?: boolean}): JSX.Element {
  const errors = () => props.errors ? props.alerts.filter((alert) => alert.level === 'error') : [];
  const others = () => props.alerts.filter((alert) => alert.level !== 'error');
  const [index, setIndex] = createSignal(0);
  const current = createMemo(() => others()[index() % Math.max(others().length, 1)]);

  return (
    <Show when={errors().length || others().length}>
      <div class={styles.notices}>
        <For each={errors()}>
          {(alert) => <Notice alert={alert} />}
        </For>
        <Show when={current()} keyed>
          {(alert) => (
            <Notice
              alert={alert}
              pager={
                <Show when={others().length > 1}>
                  <button
                    type="button"
                    class={styles.noticePager}
                    aria-label={I18n.format('Login.Next', true).replace(/\s*>$/, '')}
                    onClick={() => setIndex((index() + 1) % others().length)}
                  >
                    {(index() % others().length) + 1}/{others().length}
                    <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M6 3.5l4.5 4.5L6 12.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                  </button>
                </Show>
              }
            />
          )}
        </Show>
      </div>
    </Show>
  );
}
