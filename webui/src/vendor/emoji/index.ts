/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * Adapted for MadelineProto: only `getCountryEmoji`, the flag of a country in the country picker.
 */

// Fragment's anonymous numbers have no regional indicators: a gem, as in tweb
const VIRTUAL_COUNTRIES_EMOJIS: Map<string, string> = new Map([
  ['FT', '\u{1F48E}']
]);

export function getCountryEmoji(iso2: string) {
  return VIRTUAL_COUNTRIES_EMOJIS.get(iso2) ??
    String.fromCharCode(55356, 56741 + iso2.charCodeAt(0), 55356, 56741 + iso2.charCodeAt(1));
}
