/*
 * The choice of the session to log in, when MadelineProto's startAndLoopMulti() starts several that aren't
 * logged in yet: each has its own login pages, picked by a parameter of the query (`page.sessions.param`).
 */

import {For, JSX, Show} from 'solid-js';

import {hasSubmitted, loadPageWith} from '@lib/transport';
import type {Sessions} from '@/types';
import styles from '@/pages/authFlow.module.scss';

export default function SessionSelect(props: {sessions: Sessions | null}): JSX.Element {
  return (
    <Show when={props.sessions} keyed>
      {(sessions) => (
        <div class={styles.sessions}>
          <label for="madeline-session">{sessions.label}</label>
          <select
            id="madeline-session"
            class={styles.sessionsSelect}
            value={sessions.selected}
            onChange={(e) => {
              // never while a form is being submitted: the pending POST would be cancelled in the browser only
              if(hasSubmitted()) {
                e.currentTarget.value = sessions.selected;
                return;
              }
              loadPageWith(sessions.param, e.currentTarget.value);
            }}
          >
            <For each={sessions.options}>
              {(option) => <option value={option.key} selected={option.key === sessions.selected}>{option.name}</option>}
            </For>
          </select>
        </div>
      )}
    </Show>
  );
}
