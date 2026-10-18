/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto.
 */

import {formatPhoneNumber} from '@helpers/formatPhoneNumber';
import {IS_APPLE_MOBILE} from '@environment/userAgent';
import type {HelpCountry, HelpCountryCode} from '@/types';
import InputField, {InputFieldOptions} from '@components/inputField';

// Longest number the field accepts, in digits — same limit as tdesktop
// (kMaxPhoneCodeLength + kMaxPhoneTailLength). E.164 tops out at 15, so this
// only cuts off junk input, which would otherwise grow the field line by line.
const MAX_DIGITS = 4 + 32;

export default class TelInputField extends InputField {
  private pasted = false;
  // Country-code-aware value computed in the `paste` handler and applied on the
  // `input` that follows (see the paste handler for why we can't do it inline).
  private pastedValue: string;
  public lastValue = '';

  constructor(options: InputFieldOptions & {
    onInput?: (formatted: ReturnType<typeof formatPhoneNumber>) => void
  } = {}) {
    super({
      label: 'Contacts.PhoneNumber.Placeholder',
      // plainText: true,
      name: 'phone',
      ...options
    });

    this.container.classList.add('input-field-phone');

    const telEl = this.input;
    // a phone number reads left to right in any language — the digits, the caret and the pattern
    // still to type after them (`data-left-pattern`); the label keeps the layout's side
    telEl.dir = 'ltr';
    // * tweb's field is contenteditable on some platforms: here it's always an <input>
    telEl.type = 'tel';
    telEl.autocomplete = 'rr55RandomRR55' as AutoFill;

    telEl.addEventListener('input', () => {
      // console.log('input', this.value);
      telEl.classList.remove('error');

      if(this.pastedValue !== undefined) {
        // The browser just inserted the raw clipboard text at the caret; swap it for
        // the country-code-aware merge computed in the paste handler, then format below.
        this.setValueSilently(this.pastedValue);
        this.pastedValue = undefined;
      }

      const digits = this.value.replace(/\D/g, '');
      if(digits.length > MAX_DIGITS) {
        this.setValueSilently('+' + digits.slice(0, MAX_DIGITS));
      }

      const value = this.value;
      const diff = Math.abs(value.length - this.lastValue.length);
      if(diff > 1 && !this.pasted && IS_APPLE_MOBILE) {
        this.setValueSilently(this.lastValue + value);
      }

      this.pasted = false;

      this.setLabel();

      let formattedPhoneNumber: ReturnType<typeof formatPhoneNumber>;
      let formatted: string, country: HelpCountry, countryCode: HelpCountryCode, leftPattern = '';
      if(this.value.replace(/\++/, '+') === '+') {
        this.setValueSilently('+');
      } else {
        formattedPhoneNumber = formatPhoneNumber(this.value);
        formatted = formattedPhoneNumber.formatted;
        country = formattedPhoneNumber.country;
        leftPattern = formattedPhoneNumber.leftPattern;
        countryCode = formattedPhoneNumber.code;
        this.setValueSilently(this.lastValue = formatted ? '+' + formatted : '');
      }

      telEl.dataset.leftPattern = leftPattern/* .replace(/X/g, '0') */;

      // console.log(formatted, country);

      options.onInput && options.onInput(formattedPhoneNumber);
    });

    telEl.addEventListener('paste', (e) => {
      this.pasted = true;

      const clipboard = e.clipboardData?.getData('text/plain');
      const pastedDigits = clipboard?.replace(/\D/g, '');
      if(!pastedDigits) {
        return; // nothing useful (empty clipboard or non-digit text)
      }

      // The field already holds the country code (the sign-in page pre-fills the
      // nearest DC's one), so a naive paste would either double it or keep a stray
      // national trunk '0'. Compute the intended value here, where `this.value` is
      // still the pre-paste content. We can't apply it now: `preventDefault()` does
      // NOT stop a contentEditable from inserting the raw clipboard text, so instead
      // we stash it and overwrite the field in the `input` handler that fires next.
      if(clipboard.trimStart().startsWith('+') || pastedDigits.startsWith('00')) {
        // Full international number — it carries its own country code, so it REPLACES
        // the field. '+66' + paste '+66809716338' -> '+66809716338' (no doubled '66').
        this.pastedValue = '+' + (pastedDigits.startsWith('00') ? pastedDigits.slice(2) : pastedDigits);
      } else {
        // National number — keep the country code in the field and append the pasted
        // part, dropping its leading trunk '0'. '+66' + paste '0809716338' -> '+66809716338'.
        const currentDigits = this.value.replace(/\D/g, '');
        this.pastedValue = '+' + currentDigits + (currentDigits ? pastedDigits.replace(/^0/, '') : pastedDigits);
      }
    });

    /* telEl.addEventListener('change', (e) => {
      console.log('change', telEl.value);
    }); */

    telEl.addEventListener('keypress', (e) => {
      // console.log('keypress', this.value);
      const key = e.key;
      if(/\D/.test(key) && !(e.metaKey || e.ctrlKey) && key !== 'Backspace' && !(key === '+' && e.shiftKey/*  && !this.value */)) {
        e.preventDefault();
        return false;
      }
    });

    /* telEl.addEventListener('focus', function(this: typeof telEl, e) {
      this.removeAttribute('readonly'); // fix autocomplete
    });*/
  }
}
