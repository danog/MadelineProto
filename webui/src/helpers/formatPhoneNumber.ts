/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto.
 */

import type {HelpCountry, HelpCountryCode} from '@/types';
import I18n from '@lib/langPack';

type PrefixCountry = {country: HelpCountry, code: HelpCountryCode};
const prefixes: Map<string, PrefixCountry> = new Map();
// country calling code (e.g. '7') -> a representative country/code, for grouping a
// number as a national number of a known country (see `defaultCountryCode` below).
const byCountryCode: Map<string, PrefixCountry> = new Map();
let maxPrefixLength = 0;
const setPrefix = (country: HelpCountry, code: HelpCountryCode, prefix: string = '') => {
  prefix = code.country_code + prefix;
  /* if(prefixes.has(prefix)) {
    console.error('asdasdasd', prefixes.get(prefix), country, code);
  } */
  maxPrefixLength = Math.max(maxPrefixLength, prefix.length);
  prefixes.set(prefix, {country, code});
  if(!byCountryCode.has(code.country_code)) {
    byCountryCode.set(code.country_code, {country, code});
  }
};

// Group the raw digits `str` according to `code`'s national patterns.
// When `national` is true, `str` is the significant (national) number alone and the
// country code is NOT prepended to the result; otherwise `str` is expected to start
// with the country code (the international form), which is kept in the output.
function applyPattern(code: HelpCountryCode, str: string, national: boolean) {
  const patterns = code.patterns || [];
  const searchForPattern = national ? str : str.slice(code.country_code.length); // splice country code
  let pattern = '', mostMatchedPatternMatches = 0, mostMatchedPattern = '';
  for(let i = patterns.length - 1; i >= 0; --i) {
    pattern = patterns[i];

    const _pattern = pattern.replace(/ /g, '');
    let patternMatches = 0;
    for(let k = 0, length = Math.min(searchForPattern.length, _pattern.length); k < length; ++k) {
      if(searchForPattern[k] === _pattern[k]) {
        patternMatches += 1.01;
      } else if(_pattern[k] === 'X') {
        ++patternMatches;
      } else {
        patternMatches = 0;
        break;
      }
    }

    if(patternMatches > mostMatchedPatternMatches) {
      mostMatchedPatternMatches = patternMatches;
      mostMatchedPattern = pattern;
    }
  }

  pattern = mostMatchedPattern || pattern;
  pattern = pattern.replace(/\d/g, 'X');

  if(!national) {
    pattern = code.country_code + ' ' + pattern;
  }

  // let pattern = country.pattern || country.phoneCode;
  pattern.split('').forEach((symbol, idx) => {
    if(symbol === ' ' && str[idx] !== ' ' && str.length > idx) {
      str = str.slice(0, idx) + ' ' + str.slice(idx);
    }
  });

  let leftPattern = pattern && pattern.length > str.length ? pattern.slice(str.length) : '';
  if(leftPattern) {
    leftPattern = leftPattern.replace(/X/g, '‒');
  }

  return {formatted: str, leftPattern};
}

export function formatPhoneNumber(originalStr: string, options?: {
  // The viewer's own country calling code (digits only, e.g. '7'). When the input
  // carries NO explicit '+' AND its leading digits do NOT already start with this
  // code, the number is treated as a national number of the viewer's country and
  // grouped accordingly — instead of greedily matching the leading digits against a
  // foreign country code, which mis-rendered e.g. an Indonesian national "8123456789"
  // as Japan (+81) in a shared-contact embed (bugs.telegram.org #30681). Mirrors
  // Android's PhoneFormat, which biases an unprefixed number to the user's own
  // country. `code` is then returned undefined, so the caller omits the '+' sign.
  defaultCountryCode?: string
}): {
  formatted: string,
  country: HelpCountry,
  code: HelpCountryCode,
  leftPattern: string
} {
  originalStr = originalStr || '';

  if(!prefixes.size) {
    I18n.countriesList.forEach((country) => {
      country.country_codes.forEach((code) => {
        if(code.prefixes) {
          code.prefixes.forEach((prefix) => {
            setPrefix(country, code, prefix);
          });
        } else {
          setPrefix(country, code);
        }
      });
    });
  }

  // return {formatted: originalStr, country: undefined as any, leftPattern: ''};
  const str = originalStr.replace(/\D/g, '');

  // The number does not carry an explicit '+' dialing prefix and the viewer's country
  // is known: unless the digits already begin with that country code, treat them as a
  // national number of the viewer's country rather than guessing a (possibly foreign)
  // country from the leading digits (bugs.telegram.org #30681).
  const defaultCountryCode = options?.defaultCountryCode;
  if(defaultCountryCode && !originalStr.trimStart().startsWith('+') && !str.startsWith(defaultCountryCode)) {
    const prefixCountry = byCountryCode.get(defaultCountryCode);
    if(prefixCountry) {
      const {formatted, leftPattern} = applyPattern(prefixCountry.code, str, true);
      return {formatted, country: prefixCountry.country, code: undefined, leftPattern};
    }

    return {formatted: str, country: undefined, code: undefined, leftPattern: ''};
  }

  const phoneCode = str.slice(0, maxPrefixLength);

  let prefixCountry: PrefixCountry;
  for(let i = phoneCode.length - 1; i >= 0; --i) { // lookup for country by prefix
    prefixCountry = prefixes.get(phoneCode.slice(0, i + 1));
    if(prefixCountry) {
      break;
    }
  }

  if(!prefixCountry) {
    return {formatted: str, country: undefined, code: undefined, leftPattern: ''};
  }

  const country = prefixCountry.country;
  const {formatted, leftPattern} = applyPattern(prefixCountry.code, str, false);
  return {formatted, country, code: prefixCountry.code, leftPattern};
}
