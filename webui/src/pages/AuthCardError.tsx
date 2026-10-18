/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 */

import {createEffect, JSX, Show} from 'solid-js';

import {AutoHeight} from '@components/autoHeight';

import styles from '@/pages/authFlow.module.scss';

let seed = 0;

/**
 * The error line under a code field ("Invalid code", "Code expired").
 *
 * It used to hold a line of height at all times so that an arriving error would
 * not push the card around. It costs that line on every card that has never seen
 * an error, though — so instead the line takes no room until there is something
 * to say, and `AutoHeight` animates the card growing by it rather than letting it
 * jump.
 */
export default function AuthCardError(props: {
  content: JSX.Element,
  /**
   * The field this line is about. While there is an error the field is marked
   * invalid and pointed at this line, so a screen reader reads the reason when
   * the user returns to the field — not only at the moment it appears.
   */
  describes?: HTMLElement
}) {
  const id = 'auth-card-error-' + (++seed);

  createEffect(() => {
    const input = props.describes;
    if(!input) {
      return;
    }

    if(props.content) {
      input.setAttribute('aria-invalid', 'true');
      input.setAttribute('aria-describedby', id);
    } else {
      input.removeAttribute('aria-invalid');
      input.removeAttribute('aria-describedby');
    }
  });

  return (
    <AutoHeight overflowHidden>
      <Show when={props.content}>
        {/* the element is inserted the moment there is an error, which is what makes
            assistive tech read it out */}
        <div id={id} role="alert" class={styles.errorLabel}>{props.content}</div>
      </Show>
    </AutoHeight>
  );
}
