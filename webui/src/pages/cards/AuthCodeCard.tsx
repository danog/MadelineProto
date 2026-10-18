/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto: the code goes to the script (`phone_code`, tweb: `auth.signIn`), which answers
 * with the next page (password, signup) or the end of the login. What it sent is `page.data.sentCode`. The
 * resend, the login email reset and the way back to the phone number are tweb's own calls, which the script
 * doesn't offer: they're left out.
 */

import {JSX, Show, createSignal, onCleanup, onMount} from 'solid-js';

import Button from '@components/buttonTsx';
import CodeInputFieldCompat from '@components/codeInputField';
import InputField, {InputState} from '@components/inputField';
import TrackingMonkey, {preloadTrackingMonkey} from '@components/monkeys/tracking';
import MediaHeader from '@components/mediaHeader';
import cancelEvent from '@helpers/dom/cancelEvent';
import focusWhenSettled from '@helpers/dom/focusWhenSettled';
import whenDetached from '@helpers/dom/whenDetached';
import mediaSizes from '@helpers/mediaSizes';
import classNames from '@helpers/string/classNames';
import {fastRaf} from '@helpers/schedulers';
import {LangPackKey, i18n} from '@lib/langPack';

import AuthCard from '@/pages/AuthCard';
import AuthCardError from '@/pages/AuthCardError';
import {CardSpec, useAuthFlow} from '@/pages/authFlow';
import {PageNotices, pageError} from '@/pages/pageContent';
import {
  CodeInputKind,
  SentCode,
  beginsOk,
  fromSentCodeInfo,
  getCodeBeginning,
  getCodeInputKind,
  getCodeLength
} from '@/pages/sentCode';
import styles from '@/pages/authFlow.module.scss';

/** A phone number inside a sentence: it reads left to right in any language. */
function wrapLeftToRight(text: string) {
  const span = document.createElement('span');
  span.dir = 'ltr';
  span.textContent = text;
  return span;
}

/** The subtitle: how the code was sent. */
function sentTypeContent(sentCode: SentCode): JSX.Element {
  let key: LangPackKey;
  let args: any[] | undefined;
  const type = sentCode.type;
  switch(type._) {
    case 'auth.sentCodeTypeSms':
    case 'auth.sentCodeTypeFirebaseSms':
      key = 'Login.Code.SentSms';
      break;
    case 'auth.sentCodeTypeApp':
      key = 'Login.Code.SentInApp';
      break;
    case 'auth.sentCodeTypeCall':
    case 'auth.sentCodeTypeFlashCall':
      key = 'Login.Code.SentCall';
      break;
    case 'auth.sentCodeTypeMissedCall':
      key = 'Login.Code.SentMissedCall';
      args = [wrapLeftToRight(type.prefix ?? '')];
      break;
    case 'auth.sentCodeTypeSmsWord':
    case 'auth.sentCodeTypeSmsPhrase': {
      const isWord = type._ === 'auth.sentCodeTypeSmsWord';
      const phone = wrapLeftToRight(sentCode.phone_number ?? '');
      if(type.beginning) {
        key = isWord ? 'Login.Code.SentSmsWordBeginning' : 'Login.Code.SentSmsPhraseBeginning';
        args = [phone, type.beginning];
      } else {
        key = isWord ? 'Login.Code.SentSmsWord' : 'Login.Code.SentSmsPhrase';
        args = [phone];
      }
      break;
    }
    case 'auth.sentCodeTypeFragmentSms': {
      key = 'PhoneNumber.Code.Fragment.Info';
      const a = document.createElement('a');
      a.target = '_blank';
      a.rel = 'noopener noreferrer';
      if(type.url && /^https:\/\//.test(type.url)) a.href = type.url;
      args = [a];
      break;
    }
    case 'auth.sentCodeTypeEmailCode':
      key = 'Login.Code.SentEmail';
      args = [type.email_pattern ?? ''];
      break;
    default:
      key = 'Login.Code.SentUnknown';
      args = [type._];
      break;
  }

  return i18n(key, args);
}

/**
 * Card variant of `pageAuthCode`. Digit codes submit themselves once the last box is filled; a secret word
 * or phrase (`auth.sentCodeTypeSmsWord` / `…SmsPhrase`) is free text, with a Next button, checked against
 * the `beginning` the server disclosed before an attempt is spent.
 */
export default function AuthCodeCard(props: {spec: CardSpec}) {
  const {submit} = useAuthFlow();

  const sentCode = fromSentCodeInfo(props.spec.page.data?.sentCode, props.spec.page.data?.phoneNumber);
  const kind: CodeInputKind = getCodeInputKind(sentCode.type);

  const [submitting, setSubmitting] = createSignal(false);
  const [errorContent, setErrorContent] = createSignal<JSX.Element>();

  const stickerHost = document.createElement('div');
  const stickerSize = mediaSizes.isMobile ? 100 : 130;
  let monkey: TrackingMonkey | undefined;
  let cancelled = false;

  /* ---------- header ---------- */

  const phoneEl = document.createElement('h4');
  phoneEl.classList.add(styles.phone);
  phoneEl.dir = 'ltr';
  phoneEl.textContent = sentCode.phone_number ?? '';

  /* ---------- code input ---------- */

  const inputHost = document.createElement('div');
  inputHost.classList.add(styles.codeInputHost);

  let codeInputField: CodeInputFieldCompat | undefined;
  let textInputField: InputField | undefined;

  const activeInput = () => codeInputField?.input ?? textInputField?.input;

  function clearError() {
    if(codeInputField) codeInputField.error = false;
    textInputField?.setState(InputState.Neutral);
    setErrorContent(undefined);
  }

  function markError(content: JSX.Element) {
    if(codeInputField) codeInputField.error = true;
    textInputField?.setState(InputState.Error);
    setErrorContent(content);
  }

  if(kind === 'digits') {
    codeInputField = new CodeInputFieldCompat({
      length: getCodeLength(sentCode.type),
      onChange: clearError,
      onFill: (code) => submitCode(code),
      class: styles.codeInputField
    });
    inputHost.append(codeInputField.container);
  } else {
    textInputField = new InputField({
      plainText: true,
      label: sentCode.type._ === 'auth.sentCodeTypeSmsWord' ? 'Login.Code.SecretWord' : 'Login.Code.SecretPhrase',
      name: 'sms-word',
      onRawInput: clearError
    });
    textInputField.container.classList.add(styles.wordInputField);
    textInputField.input.addEventListener('keydown', (e: KeyboardEvent) => {
      if(e.key !== 'Enter') return;
      cancelEvent(e);
      submitTextCode();
    });
    inputHost.append(textInputField.container);
  }

  /* ---------- submission ---------- */

  function submitTextCode() {
    const value = textInputField?.value.trim();
    if(!value || submitting()) return;
    submitCode(value);
  }

  function setInputDisabled(disabled: boolean) {
    if(codeInputField) codeInputField.disabled = disabled;
    textInputField?.input.toggleAttribute('disabled', disabled);
  }

  function submitCode(code: string) {
    // the word/phrase types disclose how the secret starts: a typo is caught here, not by the server
    if(kind === 'text' && !beginsOk(code, getCodeBeginning(sentCode.type))) {
      markError(i18n('Login.Code.WordBeginningInvalid'));
      return;
    }

    setSubmitting(true);
    setInputDisabled(true);

    submit({phone_code: code}).then((page) => {
      if(!page || cancelled) return;
      // still here: the code was wrong
      const rpcError = page.data?.rpcError;
      markError(
        kind === 'text' && (rpcError === 'PHONE_CODE_INVALID' || rpcError === 'PHONE_CODE_EMPTY') ?
          i18n('Login.Code.WordInvalid') :
          pageError(page) ?? i18n('PHONE_CODE_INVALID')
      );
      afterError();
    }).catch((err) => {
      console.error('code error:', err);
      if(cancelled) return;
      markError(i18n('Error.AnError'));
      afterError();
    });
  }

  function afterError() {
    setSubmitting(false);
    setInputDisabled(false);
    // a digit code is retyped from scratch; a secret word or phrase is kept for editing
    if(codeInputField) codeInputField.value = '';
    fastRaf(() => activeInput()?.focus());
  }

  /* ---------- lifecycle ---------- */

  let cancelFocus: (() => void) | undefined;
  onMount(() => {
    monkey = new TrackingMonkey(codeInputField ?? textInputField, stickerSize);
    stickerHost.append(monkey.container);
    monkey.load().catch(() => {});
    cancelFocus = focusWhenSettled(activeInput(), () => !cancelled);
  });

  onCleanup(() => {
    cancelled = true;
    cancelFocus?.();
    codeInputField?.cleanup();

    // * the card is kept for its exit animation after this scope is gone: the sticker goes with it
    const leaving = monkey;
    monkey = undefined;
    whenDetached(stickerHost.closest<HTMLElement>('.' + styles.card), () => leaving?.remove());
  });

  preloadTrackingMonkey().catch(() => {});

  return (
    <AuthCard
      class={styles.pageAuthCode}
      header={
        <MediaHeader>
          <MediaHeader.Sticker element={stickerHost} size={stickerSize}/>
          <MediaHeader.Title>
            <div class={styles.phoneWrapper}>{phoneEl}</div>
          </MediaHeader.Title>
          <MediaHeader.Subtitle class="secondary">{sentTypeContent(sentCode)}</MediaHeader.Subtitle>
          <PageNotices alerts={props.spec.page.alerts} />
        </MediaHeader>
      }
      inputWrapper={false}
    >
      {inputHost}
      <AuthCardError content={errorContent()} describes={activeInput()} />
      <Show when={kind === 'text'}>
        <Button
          class={classNames('btn-primary btn-color-primary', styles.wordSubmit)}
          disabled={submitting()}
          onClick={submitTextCode}
          text="Login.Next"
        />
      </Show>
    </AuthCard>
  );
}
