/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto: the country picker and the phone field of tweb's sign-in card, wired together
 * (out of `SignInCard`), for every card asking for a phone number.
 */

import CountryInputField from '@components/countryInputField';
import TelInputField from '@components/telInputField';
import placeCaretAtEnd from '@helpers/dom/placeCaretAtEnd';
import type {HelpCountry, HelpCountryCode} from '@/types';

/** The country of the browser's language (`en-GB`: GB), to preselect it (tweb asks `help.getNearestDc`). */
export function guessCountry(): string | undefined {
  for(const language of navigator.languages ?? [navigator.language]) {
    try {
      const region = new Intl.Locale(language).maximize().region;
      if(region) return region;
    } catch(err) {}
  }
}

export type PhoneInput = {
  countryInputField: CountryInputField,
  telInputField: TelInputField,
  /** Set the number (the country follows it), or preselect the country of the browser if there is none. */
  init(value?: string): void
};

/**
 * A country picker and a phone field: picking a country types its code, and typing a number picks its
 * country. `onValidity` tells whether the number looks long enough to send.
 */
export function createPhoneInput(onValidity: (valid: boolean) => void): PhoneInput {
  let lastCountrySelected: HelpCountry | undefined;
  let lastCountryCodeSelected: HelpCountryCode | undefined;
  // True while we're mirroring a phone-driven country detection into the country
  // selector. In that case the phone field is the source of truth and must NOT be
  // reset to the bare '+code' (that would wipe a just-typed/pasted national number).
  let overriding = false;

  const countryInputField = new CountryInputField({
    onCountryChange: (country, code) => {
      lastCountrySelected = country;
      lastCountryCodeSelected = code;

      if(!code || overriding) return;

      telInputField.value = telInputField.lastValue = '+' + code.country_code;
      setTimeout(() => {
        telInputField.input.focus();
        placeCaretAtEnd(telInputField.input, true);
      }, 0);
    }
  });

  const telInputField = new TelInputField({
    onInput: (formatted) => {
      const {country, code} = formatted || {};
      const countryName = country ? country.name || country.default_name : '';
      if(countryName !== countryInputField.value && (
        !lastCountrySelected ||
          !country ||
          !code || (
          lastCountrySelected !== country &&
            lastCountryCodeSelected.country_code !== code.country_code
        )
      )) {
        overriding = true;
        countryInputField.override(country, code, countryName);
        overriding = false;
      }

      onValidity(!!(country || (telInputField.value.length - 1) > 1));
    }
  });

  return {
    countryInputField,
    telInputField,
    init(value) {
      if(value) {
        telInputField.value = value;
        return;
      }

      const iso2 = guessCountry();
      if(iso2) {
        try {
          countryInputField.selectCountryByIso2(iso2);
        } catch(err) {}
      }
    }
  };
}
