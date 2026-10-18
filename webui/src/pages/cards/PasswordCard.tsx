/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto: the password goes to the script (`password`, tweb: `passwordManager.check`),
 * and the hint is `page.data.hint` (tweb polls `passwordManager.getState`). The password recovery and
 * the account reset are tweb's own calls, which the script doesn't offer: they're left out.
 */

import {createSignal, JSX, onCleanup, onMount} from 'solid-js';

import Button from '@components/buttonTsx';
import PasswordInputField from '@components/passwordInputField';
import PasswordMonkey from '@components/monkeys/password';
import MediaHeader from '@components/mediaHeader';
import cancelEvent from '@helpers/dom/cancelEvent';
import focusWhenSettled from '@helpers/dom/focusWhenSettled';
import whenDetached from '@helpers/dom/whenDetached';
import mediaSizes from '@helpers/mediaSizes';
import {i18n} from '@lib/langPack';

import AuthCard from '@/pages/AuthCard';
import {CardSpec, useAuthFlow} from '@/pages/authFlow';
import {PageNotices, pageErrorText} from '@/pages/pageContent';
import styles from '@/pages/authFlow.module.scss';

/**
 * Card variant of `pagePassword`. Single password input over a `PasswordMonkey` animation, which covers
 * its eyes and peeks when the password is shown.
 */
export default function PasswordCard(props: {spec: CardSpec}) {
  const {submit} = useAuthFlow();

  let monkey: PasswordMonkey | undefined;
  const monkeyContainer = document.createElement('div');
  const monkeySize = mediaSizes.isMobile ? 100 : 130;
  let cancelled = false;

  /* ---------- state ---------- */

  const [submitting, setSubmitting] = createSignal(false);
  const [nextContent, setNextContent] = createSignal<JSX.Element>(i18n('Login.Next'));

  /* ---------- input ---------- */

  const hint = props.spec.page.data?.hint;
  const passwordInputField = new PasswordInputField({
    label: 'LoginPassword',
    name: 'password'
  });
  const passwordInput = passwordInputField.input as HTMLInputElement;

  function setHintLabel() {
    if(hint) {
      // the user's own words: they order by their own direction
      const span = document.createElement('span');
      span.dir = 'auto';
      span.textContent = hint;
      passwordInputField.label.replaceChildren(span);
      passwordInputField.label.style.visibility = 'visible';
    } else {
      passwordInputField.setLabel();
    }
  }
  setHintLabel();

  /* ---------- submit ---------- */

  function onSubmit(e?: Event) {
    if(e) cancelEvent(e);

    if(!passwordInput.value.length) {
      passwordInput.classList.add('error');
      return;
    }

    setSubmitting(true);
    passwordInput.disabled = true;
    setNextContent(i18n('PleaseWait'));

    const value = passwordInput.value;
    passwordInputField.setValueSilently('' + Math.random()); // prevent saving suggestion
    passwordInputField.setValueSilently(value);

    submit({password: value}).then((page) => {
      if(!page || cancelled) return;
      // still here: the password was wrong
      setSubmitting(false);
      passwordInput.disabled = false;
      passwordInput.classList.add('error');
      setNextContent(pageErrorText(page) ?? i18n('PASSWORD_HASH_INVALID'));
      passwordInput.select();
    }).catch((err) => {
      console.error('password error:', err);
      if(cancelled) return;
      setSubmitting(false);
      passwordInput.disabled = false;
      setNextContent(i18n('Error.AnError'));
    });
  }

  passwordInput.addEventListener('keypress', function(this: HTMLInputElement, e) {
    this.classList.remove('error');
    setNextContent(i18n('Login.Next'));

    if(e.key === 'Enter') {
      return onSubmit();
    }
  });

  /* ---------- lifecycle ---------- */

  let cancelFocus: (() => void) | undefined;
  onMount(() => {
    monkey = new PasswordMonkey(passwordInputField, monkeySize);
    monkeyContainer.append(monkey.container);
    monkey.load().catch(() => {});

    cancelFocus = focusWhenSettled(passwordInput, () => !cancelled);
  });

  onCleanup(() => {
    cancelled = true;
    cancelFocus?.();

    // * as in the code card: the card is kept for its exit animation after this scope is gone
    const leaving = monkey;
    monkey = undefined;
    whenDetached(monkeyContainer.closest<HTMLElement>('.' + styles.card), () => leaving?.remove());
  });

  return (
    <AuthCard
      class={styles.pagePassword}
      header={
        <MediaHeader>
          <MediaHeader.Sticker element={monkeyContainer} size={monkeySize}/>
          <MediaHeader.Title tag="h1">{i18n('Login.Password.Title')}</MediaHeader.Title>
          <MediaHeader.Subtitle>{i18n('Login.Password.Subtitle')}</MediaHeader.Subtitle>
          <PageNotices alerts={props.spec.page.alerts} />
        </MediaHeader>
      }
    >
      {passwordInputField.container}
      <Button
        class="btn-primary btn-color-primary"
        disabled={submitting()}
        onClick={onSubmit}
      >
        {nextContent()}
        {submitting() && (
          <svg xmlns="http://www.w3.org/2000/svg" class="preloader-circular" viewBox="25 25 50 50">
            <circle class="preloader-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
          </svg>
        )}
      </Button>
    </AuthCard>
  );
}
