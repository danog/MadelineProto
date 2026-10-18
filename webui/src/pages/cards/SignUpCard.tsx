/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto: the name goes to the script (`first_name`, `last_name`, tweb: `auth.signUp`).
 * The avatar picker (tweb's media editor, and an upload after the signup) is left out: the sticker slot
 * shows the initials of the name being typed, where tweb shows the avatar.
 */

import {createSignal, JSX, onMount} from 'solid-js';

import Button from '@components/buttonTsx';
import InputField from '@components/inputField';
import MediaHeader from '@components/mediaHeader';
import {i18n} from '@lib/langPack';

import AuthCard from '@/pages/AuthCard';
import {CardSpec, useAuthFlow} from '@/pages/authFlow';
import {PageNotices, pageErrorText} from '@/pages/pageContent';
import styles from '@/pages/authFlow.module.scss';

/** The first letters of the name, as in the avatars of Telegram. */
function initials(name: string, lastName: string) {
  const first = (s: string) => Array.from(s.trim())[0] ?? '';
  return (first(name) + first(lastName)).toUpperCase();
}

/**
 * Card variant of `pageSignUp`: two name inputs. The card title doubles as a live preview of the entered
 * full name.
 */
export default function SignUpCard(props: {spec: CardSpec}) {
  const {submit} = useAuthFlow();

  let cancelled = false;

  /* ---------- state ---------- */

  const [submitting, setSubmitting] = createSignal(false);
  const [signUpContent, setSignUpContent] = createSignal<JSX.Element>(i18n('StartMessaging'));
  const [avatarText, setAvatarText] = createSignal('');

  /* ---------- inputs ---------- */

  const nameInputField = new InputField({
    label: 'FirstName',
    maxLength: 70,
    name: 'first_name',
    autocomplete: 'given-name'
  });

  const lastNameInputField = new InputField({
    label: 'LastName',
    maxLength: 64,
    name: 'last_name',
    autocomplete: 'family-name'
  });

  /* ---------- live full-name preview (drives MediaHeader.Title) ---------- */

  const [titleContent, setTitleContent] = createSignal<JSX.Element>(i18n('YourName'));

  function handleNameInput() {
    const name = nameInputField.value || '';
    const lastName = lastNameInputField.value || '';

    const fullName = (name || lastName) ? (name + ' ' + lastName).trim() : '';

    setTitleContent(fullName ? fullName : i18n('YourName'));
    setAvatarText(initials(name, lastName));
    setSignUpContent(i18n('StartMessaging'));
  }

  nameInputField.input.addEventListener('input', handleNameInput);
  lastNameInputField.input.addEventListener('input', handleNameInput);

  /* ---------- submit ---------- */

  function onSubmit() {
    if(nameInputField.input.classList.contains('error') || lastNameInputField.input.classList.contains('error')) {
      return;
    }

    if(!nameInputField.value.trim().length) {
      nameInputField.input.classList.add('error');
      return;
    }

    setSubmitting(true);
    setSignUpContent(i18n('PleaseWait'));

    submit({
      first_name: nameInputField.value.trim(),
      last_name: lastNameInputField.value.trim()
    }).then((page) => {
      if(!page || cancelled) return;
      setSubmitting(false);
      setSignUpContent(pageErrorText(page) ?? i18n('Error.AnError'));
    }).catch((err) => {
      console.error('sign up error:', err);
      if(cancelled) return;
      setSubmitting(false);
      setSignUpContent(i18n('Error.AnError'));
    });
  }

  for(const field of [nameInputField, lastNameInputField]) {
    field.input.addEventListener('keypress', (e) => {
      if(e.key === 'Enter') onSubmit();
    });
  }

  /* ---------- lifecycle ---------- */

  onMount(() => {
    const first = props.spec.page.fields.find((field) => field.name === 'first_name')?.value;
    const last = props.spec.page.fields.find((field) => field.name === 'last_name')?.value;
    if(first) nameInputField.value = first;
    if(last) lastNameInputField.value = last;
    nameInputField.input.focus();
  });

  return (
    <AuthCard
      class={styles.pageSignUp}
      header={
        <MediaHeader>
          <MediaHeader.Sticker
            size={120}
            element={
              <div class={styles.avatar} aria-hidden="true">{avatarText()}</div>
            }
          />
          <MediaHeader.Title tag="h1">{titleContent()}</MediaHeader.Title>
          <MediaHeader.Subtitle>{i18n('Login.Register.Subtitle')}</MediaHeader.Subtitle>
          <PageNotices alerts={props.spec.page.alerts} />
        </MediaHeader>
      }
    >
      {nameInputField.container}
      {lastNameInputField.container}
      <Button
        class="btn-primary btn-color-primary"
        disabled={submitting()}
        onClick={onSubmit}
      >
        {signUpContent()}
        {submitting() && (
          <svg xmlns="http://www.w3.org/2000/svg" class="preloader-circular" viewBox="25 25 50 50">
            <circle class="preloader-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
          </svg>
        )}
      </Button>
    </AuthCard>
  );
}
