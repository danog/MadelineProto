// What every card shows of the page the server sent, in tweb's style: its texts (trusted markup or
// plain text), its error, and its notices (missing extensions, an update...).

import {For, JSX, Show} from 'solid-js';

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

/** Warnings and information of the server (a missing extension, a new version), under the header. */
export function PageNotices(props: {alerts: Alert[], errors?: boolean}): JSX.Element {
  const alerts = () => props.alerts.filter((alert) => props.errors || alert.level !== 'error');
  return (
    <Show when={alerts().length}>
      <div class={styles.notices}>
        <For each={alerts()}>
          {(alert) => (
            <div class={styles.notice} classList={{[styles[alert.level]]: true}} role={alert.level === 'error' ? 'alert' : 'status'}>
              <Show when={!isEmptyText(alert.title)}>
                <div class={styles.noticeTitle}><RichText text={alert.title} /></div>
              </Show>
              <RichText text={alert.message} />
              <Show when={!isEmptyText(alert.code)}>
                <pre class={styles.noticeCode}><RichText text={alert.code} /></pre>
              </Show>
            </div>
          )}
        </For>
      </div>
    </Show>
  );
}
