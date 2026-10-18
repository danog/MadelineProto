/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * MadelineProto: the phone number goes to the script (`type=phone`, `phone_number`), which sends the
 * code (tweb: `auth.sendCode`). The country is guessed from the language of the browser (see
 * `components/phoneInput.ts`). The bot login (`SignBotCard`) is reached from here, as the QR code is.
 */

import {createSignal, JSX, onCleanup, onMount, Show} from 'solid-js';

import Button from '@components/buttonTsx';
import MediaHeader from '@components/mediaHeader';
import {createPhoneInput} from '@components/phoneInput';
import Logo from '@components/logo';
import IS_TOUCH_SUPPORTED from '@environment/touchSupport';
import cancelEvent from '@helpers/dom/cancelEvent';
import focusWhenSettled from '@helpers/dom/focusWhenSettled';
import replaceContent from '@helpers/dom/replaceContent';
import {i18n} from '@lib/langPack';

import AuthCard from '@/pages/AuthCard';
import {CardSpec, useAuthFlow} from '@/pages/authFlow';
import {PageNotices, pageErrorText, withoutErrors} from '@/pages/pageContent';
import styles from '@/pages/authFlow.module.scss';
import {preloadTrackingMonkey} from '@components/monkeys/tracking';

/**
 * Card variant of `pageSignIn`. Country picker + tel input → the script sends the code → authCode
 * (or password / signUp, as the server says). Also exposes the QR-login and bot-login buttons.
 */
export default function SignInCard(props: {spec: CardSpec}) {
  const {navigate, submit} = useAuthFlow();

  let cancelled = false;

  /* ---------- state ---------- */

  const [submitting, setSubmitting] = createSignal(false);
  const [hasValidInput, setHasValidInput] = createSignal(false);
  const [nextContent, setNextContent] = createSignal<JSX.Element>(i18n('Login.Next'));

  /* ---------- inputs ---------- */

  const {countryInputField, telInputField, init} = createPhoneInput(setHasValidInput);

  const telEl = telInputField.input;
  telEl.addEventListener('keypress', (e) => {
    if(hasValidInput() && !submitting() && e.key === 'Enter') {
      return onSubmit();
    }
  });

  /* ---------- submit ---------- */

  function showError(page: CardSpec['page']) {
    if(page.data?.rpcError === 'PHONE_NUMBER_INVALID') {
      telInputField.setError();
      replaceContent(telInputField.label, i18n('Login.PhoneLabelInvalid'));
      telEl.classList.add('error');
      setNextContent(i18n('Login.Next'));
      return;
    }

    const error = pageErrorText(page);
    setNextContent(error ? error : i18n('Login.Next'));
  }

  function onSubmit(e?: Event) {
    if(e) cancelEvent(e);

    setSubmitting(true);
    setNextContent(
      <>
        {i18n('PleaseWait')}
        <svg xmlns="http://www.w3.org/2000/svg" class="preloader-circular" viewBox="25 25 50 50">
          <circle class="preloader-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
        </svg>
      </>
    );

    // * the code card's sticker is fetched alongside the code itself, not when that card mounts: by
    // * then the card is already open, and the monkey turns up in it a moment later
    preloadTrackingMonkey().catch(() => {});
    submit({type: 'phone', phone_number: telInputField.value}).then((page) => {
      if(!page || cancelled) return;
      setSubmitting(false);
      showError(page);
    }).catch((err) => {
      console.error('sign in error:', err);
      setSubmitting(false);
      setNextContent(i18n('Error.AnError'));
    });
  }

  /* ---------- lifecycle ---------- */

  let cancelFocus: (() => void) | undefined;
  onMount(() => {
    if(!IS_TOUCH_SUPPORTED) {
      cancelFocus = focusWhenSettled(telEl, () => !cancelled);
    }

    init(props.spec.page.fields.find((field) => field.name === 'phone_number')?.value);

    // an error of a submission made without JavaScript, or of the bot card
    if(props.spec.page.data?.rpcError || props.spec.page.alerts.some((alert) => alert.level === 'error')) {
      showError(props.spec.page);
    }
  });

  onCleanup(() => {
    cancelled = true;
    cancelFocus?.();
  });

  /* ---------- render ---------- */

  return (
    <AuthCard
      header={
        <MediaHeader>
          <MediaHeader.Sticker
            class={styles.logoContainer}
            size={120}
            element={<Logo class={styles.logo} />}
          />
          <MediaHeader.Title tag="h1">{i18n('Login.Title')}</MediaHeader.Title>
          <MediaHeader.Subtitle class="secondary">{i18n('Login.StartText')}</MediaHeader.Subtitle>
          <PageNotices alerts={props.spec.page.alerts} />
        </MediaHeader>
      }
      inputWrapper={false}
    >
      <div class="input-wrapper">
        {countryInputField.container}
        {telInputField.container}
        <Button
          class="btn-primary btn-color-primary"
          disabled={!hasValidInput() || submitting()}
          onClick={onSubmit}
        >
          {nextContent()}
        </Button>
      </div>
      <Show when={props.spec.page.qrEndpoint}>
        <Button
          class="btn-primary btn-secondary btn-primary-transparent primary"
          disabled={submitting()}
          onClick={() => navigate({name: 'signQR', page: withoutErrors(props.spec.page)})}
          text="Login.QR.Login"
        />
      </Show>
      <Button
        class="btn-primary btn-secondary btn-primary-transparent primary"
        disabled={submitting()}
        onClick={() => navigate({name: 'signBot', page: withoutErrors(props.spec.page)})}
        text="Login.Bot"
      />
    </AuthCard>
  );
}
