/*
 * MadelineProto's own cards, in tweb's style: the API ID setup (`api.auto`, `api.code`, `api.create`,
 * `api.manual`, see PROTOCOL.md), and any page without a card of its own. They're drawn from what the
 * server sends: its texts, its fields (tweb's input fields: the country picker and the phone field for a
 * phone number, the eye toggle for a secret), and its secondary actions (tweb's transparent buttons).
 *
 * Built from the pieces of tweb (https://github.com/morethanwords/tweb, GPL-3.0): see SignInCard.tsx.
 */

import {createSignal, For, JSX, onCleanup, onMount, Show} from 'solid-js';

import Button from '@components/buttonTsx';
import InputField from '@components/inputField';
import Logo from '@components/logo';
import MediaHeader from '@components/mediaHeader';
import PasswordMonkey from '@components/monkeys/password';
import {IconTsx} from '@components/iconTsx';
import PasswordInputField from '@components/passwordInputField';
import {createPhoneInput} from '@components/phoneInput';
import IS_TOUCH_SUPPORTED from '@environment/touchSupport';
import cancelEvent from '@helpers/dom/cancelEvent';
import focusWhenSettled from '@helpers/dom/focusWhenSettled';
import whenDetached from '@helpers/dom/whenDetached';
import mediaSizes from '@helpers/mediaSizes';
import I18n, {i18n} from '@lib/langPack';
import type {Field} from '@/types';

import AuthCard from '@/pages/AuthCard';
import {CardSpec, useAuthFlow} from '@/pages/authFlow';
import {PageNotices, RichText, isEmptyText} from '@/pages/pageContent';
import styles from '@/pages/authFlow.module.scss';

/** A field of the page, as tweb's inputs. */
type FieldControl = {
  field: Field,
  /** What goes in the DOM. */
  element: Node,
  /** The input to focus, and to mark invalid. */
  input: HTMLInputElement,
  value(): string,
  setError(): void,
  /** A secret: its field, for the monkey covering its eyes. */
  passwordField?: PasswordInputField
};

/** The secondary actions read as tweb's links (`Log in by QR Code >`): the server's labels get the chevron. */
function ActionLabel(props: {label: string}) {
  return <>{props.label.replace(/\s*>$/, '')} <IconTsx icon="next" class="inline-icon" /></>;
}

function makeControl(field: Field, onValidity: (valid: boolean) => void, phoneInits: Array<() => void>): FieldControl | undefined {
  switch(field.type) {
    case 'hidden':
    case 'choice': {
      // nothing to type: the value goes with the form (a choice keeps the server's default)
      const value = field.value || Object.keys(field.options)[0] || '';
      return {field, element: document.createDocumentFragment(), input: undefined, value: () => value, setError() {}};
    }
    case 'tel': {
      const phone = createPhoneInput(onValidity);
      const fragment = document.createDocumentFragment();
      fragment.append(phone.countryInputField.container, phone.telInputField.container);
      phoneInits.push(() => phone.init(field.value));
      return {
        field,
        element: fragment,
        input: phone.telInputField.input,
        value: () => phone.telInputField.value,
        setError: () => phone.telInputField.setError()
      };
    }
    case 'password':
    case 'code': {
      const input = new PasswordInputField({labelText: field.label, name: field.name, autocomplete: field.autocomplete ?? 'off'});
      if(field.maxLength) input.input.maxLength = field.maxLength;
      input.setValueSilently(field.value);
      return {field, element: input.container, input: input.input, value: () => input.value, setError: () => input.setError(), passwordField: input};
    }
    default: {
      const input = new InputField({
        labelText: field.label + (field.required ? '' : ' (' + I18n.format('Optional', true) + ')'),
        name: field.name,
        autocomplete: field.autocomplete ?? 'off',
        maxLength: field.maxLength ?? undefined
      });
      // * `required` floats the label of an empty field down, as in tweb: the card checks what's required itself
      if(field.type === 'url') input.input.type = 'url';
      input.setValueSilently(field.value);
      return {field, element: input.container, input: input.input, value: () => input.value, setError: () => input.setError()};
    }
  }
}

export default function ApiFormCard(props: {spec: CardSpec}) {
  const {submit} = useAuthFlow();
  const page = props.spec.page;

  let cancelled = false;

  const [submitting, setSubmitting] = createSignal<string | null>(null);
  const [nextContent, setNextContent] = createSignal<JSX.Element>(page.submit ?? '');
  const [hasValidPhone, setHasValidPhone] = createSignal(true);

  /* ---------- fields ---------- */

  const phoneInits: Array<() => void> = [];
  const controls = page.fields
  .map((field) => makeControl(field, setHasValidPhone, phoneInits))
  .filter(Boolean);
  const firstInput = () => controls.find((control) => control.input)?.input;

  for(const control of controls) {
    control.input?.addEventListener('keypress', (e) => {
      control.input.classList.remove('error');
      setNextContent(page.submit ?? '');
      if(e.key === 'Enter' && !submitting()) {
        return onSubmit();
      }
    });
  }

  /* ---------- the sticker: the monkey over the (hidden) code of my.telegram.org, the mark elsewhere ---------- */

  const stickerSize = mediaSizes.isMobile ? 100 : 130;
  const codeControl = page.view === 'api.code' ? controls.find((control) => control.passwordField) : undefined;
  let monkey: PasswordMonkey | undefined;
  const stickerHost = document.createElement('div');

  /* ---------- submit ---------- */

  function onSubmit(e?: Event) {
    if(e) cancelEvent(e);

    // the browser's own validation, drawn as tweb's: the empty required fields turn red
    const empty = controls.filter((control) => control.input && control.field.required && !control.value().trim());
    if(empty.length) {
      empty.forEach((control) => control.setError());
      empty[0].input.focus();
      return;
    }

    const fields: Record<string, string> = {};
    for(const control of controls) {
      fields[control.field.name] = control.value().trim();
    }

    send(fields, 'submit');
  }

  function send(fields: Record<string, string>, key: string) {
    setSubmitting(key);
    if(key === 'submit') {
      setNextContent(
        <>
          {i18n('PleaseWait')}
          <svg xmlns="http://www.w3.org/2000/svg" class="preloader-circular" viewBox="25 25 50 50">
            <circle class="preloader-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
          </svg>
        </>
      );
    }

    submit(fields).then((next) => {
      if(!next || cancelled) return;
      // still here, with an error: shown in the notices of the page it brought
      setSubmitting(null);
      setNextContent(page.submit ?? '');
      setErrors(next.alerts.filter((alert) => alert.level === 'error'));
      firstInput()?.select();
    }).catch((err) => {
      console.error('form error:', err);
      if(cancelled) return;
      setSubmitting(null);
      setNextContent(i18n('Error.AnError'));
    });
  }

  const [errors, setErrors] = createSignal(page.alerts.filter((alert) => alert.level === 'error'));
  const notices = page.alerts.filter((alert) => alert.level !== 'error');

  /* ---------- lifecycle ---------- */

  let cancelFocus: (() => void) | undefined;
  onMount(() => {
    phoneInits.forEach((init) => init());

    if(codeControl) {
      monkey = new PasswordMonkey(codeControl.passwordField, stickerSize);
      stickerHost.append(monkey.container);
      monkey.load().catch(() => {});
    }

    const input = firstInput();
    if(input && !IS_TOUCH_SUPPORTED) {
      cancelFocus = focusWhenSettled(input, () => !cancelled);
    }
  });

  onCleanup(() => {
    cancelled = true;
    cancelFocus?.();
    const leaving = monkey;
    monkey = undefined;
    whenDetached(stickerHost.closest<HTMLElement>('.' + styles.card), () => leaving?.remove());
  });

  /* ---------- render ---------- */

  return (
    <AuthCard
      class={styles.pageApi}
      header={
        <MediaHeader>
          <MediaHeader.Sticker
            size={codeControl ? stickerSize : 120}
            element={codeControl ? stickerHost : <Logo class={styles.logo} />}
            class={codeControl ? undefined : styles.logoContainer}
          />
          <MediaHeader.Title tag="h1">{page.title}</MediaHeader.Title>
          <Show when={!isEmptyText(page.lead)}>
            <MediaHeader.Subtitle class="secondary"><RichText text={page.lead} /></MediaHeader.Subtitle>
          </Show>
          <PageNotices alerts={[...notices, ...errors()]} errors />
          <Show when={page.instructions.length}>
            <ol class={styles.instructions}>
              <For each={page.instructions}>
                {(instruction, idx) => (
                  <li class={styles.qrDescriptionItem}>
                    <span class={styles.qrDescriptionMarker}>{idx() + 1}</span>
                    <RichText class={styles.instructionText} text={instruction} />
                  </li>
                )}
              </For>
            </ol>
          </Show>
        </MediaHeader>
      }
      inputWrapper={false}
    >
      <Show when={page.submit !== null}>
        <div class="input-wrapper">
          <For each={controls}>
            {(control) => (
              <>
                {control.element}
                <Show when={!isEmptyText(control.field.hint)}>
                  <div class={styles.fieldHint}><RichText text={control.field.hint} /></div>
                </Show>
              </>
            )}
          </For>
          <Button
            class="btn-primary btn-color-primary"
            disabled={submitting() !== null || !hasValidPhone()}
            onClick={onSubmit}
          >
            {nextContent()}
          </Button>
        </div>
      </Show>
      <For each={page.actions}>
        {(action) => (
          <Button
            class="btn-primary btn-secondary btn-primary-transparent primary"
            disabled={submitting() !== null}
            onClick={() => send({[action.name]: action.value}, action.name + '=' + action.value)}
          >
            <ActionLabel label={action.label} />
          </Button>
        )}
      </For>
    </AuthCard>
  );
}
