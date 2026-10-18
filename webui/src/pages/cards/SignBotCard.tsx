/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * MadelineProto's bot login, built from tweb's pieces: the sign-in card's layout with the token in a
 * password field (hidden, with tweb's eye toggle), posted as `type=bot`, `token`.
 */

import {createSignal, JSX, onCleanup, onMount} from 'solid-js';

import Button from '@components/buttonTsx';
import MediaHeader from '@components/mediaHeader';
import PasswordInputField from '@components/passwordInputField';
import Logo from '@components/logo';
import cancelEvent from '@helpers/dom/cancelEvent';
import focusWhenSettled from '@helpers/dom/focusWhenSettled';
import {i18n} from '@lib/langPack';

import AuthCard from '@/pages/AuthCard';
import {CardSpec, useAuthFlow} from '@/pages/authFlow';
import {PageNotices, pageErrorText, withoutErrors} from '@/pages/pageContent';
import styles from '@/pages/authFlow.module.scss';

export default function SignBotCard(props: {spec: CardSpec}) {
  const {navigate, submit} = useAuthFlow();

  let cancelled = false;

  const [submitting, setSubmitting] = createSignal(false);
  const [nextContent, setNextContent] = createSignal<JSX.Element>(i18n('Login.Next'));

  const tokenInputField = new PasswordInputField({
    label: 'Login.Bot.Token',
    name: 'token'
  });
  const tokenInput = tokenInputField.input;

  function onSubmit(e?: Event) {
    if(e) cancelEvent(e);

    const token = tokenInput.value.trim();
    if(!token.length) {
      tokenInput.classList.add('error');
      return;
    }

    setSubmitting(true);
    tokenInput.disabled = true;
    setNextContent(i18n('PleaseWait'));

    submit({type: 'bot', token}).then((page) => {
      if(!page || cancelled) return;
      setSubmitting(false);
      tokenInput.disabled = false;
      tokenInputField.setError(pageErrorText(page) ?? 'Error.AnError');
      setNextContent(i18n('Login.Next'));
      tokenInput.select();
    }).catch((err) => {
      console.error('bot login error:', err);
      setSubmitting(false);
      tokenInput.disabled = false;
      setNextContent(i18n('Error.AnError'));
    });
  }

  tokenInput.addEventListener('keypress', (e) => {
    tokenInput.classList.remove('error');
    tokenInputField.setLabel();
    setNextContent(i18n('Login.Next'));

    if(e.key === 'Enter') {
      return onSubmit();
    }
  });

  let cancelFocus: (() => void) | undefined;
  onMount(() => {
    cancelFocus = focusWhenSettled(tokenInput, () => !cancelled);
  });

  onCleanup(() => {
    cancelled = true;
    cancelFocus?.();
  });

  return (
    <AuthCard
      header={
        <MediaHeader>
          <MediaHeader.Sticker
            class={styles.logoContainer}
            size={120}
            element={<Logo class={styles.logo} />}
          />
          <MediaHeader.Title tag="h1">{i18n('Login.Bot.Title')}</MediaHeader.Title>
          <MediaHeader.Subtitle class="secondary">{i18n('Login.Bot.Subtitle')}</MediaHeader.Subtitle>
          <PageNotices alerts={props.spec.page.alerts} />
        </MediaHeader>
      }
      inputWrapper={false}
    >
      <div class="input-wrapper">
        {tokenInputField.container}
        <Button
          class="btn-primary btn-color-primary"
          disabled={submitting()}
          onClick={onSubmit}
        >
          {nextContent()}
        </Button>
      </div>
      <Button
        class="btn-primary btn-secondary btn-primary-transparent primary"
        disabled={submitting()}
        onClick={() => navigate({name: 'signIn', page: withoutErrors(props.spec.page)})}
        text="Login.Bot.Cancel"
      />
    </AuthCard>
  );
}
