/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * A lite version of tweb's `lib/langPack.ts`: the same `i18n` / `I18n.format` / `superFormatter`, over
 * the English strings of `src/lang.ts` (seeded synchronously, no language packs to download), and the
 * country list of `src/countries.ts`.
 *
 * Adapted for MadelineProto: the strings come in the page, in the language of the session (applyStrings():
 * MadelineProto translates them in its langs/*.json, as tweb.<key>), with src/lang.ts as the English fallback;
 * the names of the countries are the browser's, in that language (Intl.DisplayNames).
 */

import Countries from '@/countries';
import lang, {LangPackKey} from '@/lang';
import Icon from '@components/icon';
import type {HelpCountry} from '@/types';

export type {LangPackKey};
export type FormatterArgument = string | number | Node | FormatterArgument[];
export type FormatterArguments = FormatterArgument[];

namespace I18n {
  export const strings: Map<string, string> = new Map(Object.entries(lang));
  export const countriesList: HelpCountry[] = Countries.countries;

  /** Apply the strings of the page (the language of the session), and name the countries in that language. */
  export function applyStrings(record: Record<string, unknown>, langCode?: string) {
    for(const [key, value] of Object.entries(record)) {
      if(typeof value === 'string') {
        strings.set(key, value);
      }
    }
    if(langCode && langCode !== 'en') {
      localizeCountries(langCode);
    }
  }

  function localizeCountries(langCode: string) {
    let names: Intl.DisplayNames;
    try {
      names = new Intl.DisplayNames([langCode], {type: 'region', fallback: 'none'});
    } catch(err) {
      // an engine without the language: tweb's English names
      return;
    }
    for(const country of countriesList) {
      try {
        // the virtual countries (anonymous numbers, satellite networks) have no name: their English one
        const name = names.of(country.iso2);
        if(name) {
          country.name = name;
        }
      } catch(err) {}
    }
  }

  function pushNextArgument(out: ReturnType<typeof superFormatter>, args: FormatterArguments, indexHolder: {i: number}, i?: number) {
    const arg = args[i === undefined ? indexHolder.i++ : i];
    if(Array.isArray(arg)) {
      out.push(...arg as any);
    } else {
      out.push(arg);
    }
  }

  const IconMap: Record<string, Parameters<typeof Icon>[0]> = {
    '>': 'next',
    '<': 'previous'
  };

  const iconsNoWhitespace = '><';
  const iconsKeys = Object.keys(IconMap);

  export function superFormatter(input: string, args?: FormatterArguments, indexHolder?: {i: number}): Exclude<FormatterArgument, FormatterArgument[]>[] {
    if(!indexHolder) { // set starting index for arguments without order
      indexHolder = {i: 0};
      const indexes = input.match(/(%|un)\d+/g);
      if(indexes?.length) {
        indexHolder.i = Math.max(...indexes.map((str) => +str.replace(/\D/g, '')));
      }
    }

    const out: ReturnType<typeof superFormatter> = [];
    const regExp = new RegExp(`(\\*\\*|__)(.+?)\\1|(\\n)|(\\[.+?\\]\\(.*?\\))|(?:^|\\s)(${iconsKeys.join('|')})(?:$|\\s)|un\\d|%\\d\\$.|%\\S`, 'g');

    let lastIndex = 0;
    input.replace(regExp, (match, p1: any, p2: any, p3: any, p4: string, p5: string, offset: number, string: string) => {
      if(offset > lastIndex) {
        out.push(string.slice(lastIndex, offset));
      }

      if(p1) {
        const element = document.createElement(p1 === '**' ? 'b' : 'i');
        element.append(...superFormatter(p2, args, indexHolder) as any);
        out.push(element);
      } else if(p3) {
        out.push(document.createElement('br'));
      } else if(p4) {
        const idx = p4.lastIndexOf(']');
        const text = p4.slice(1, idx);

        const url = p4.slice(idx + 2, p4.length - 1);
        let a: HTMLAnchorElement;
        if(url && /^https?:\/\//.test(url)) {
          a = document.createElement('a');
          a.href = url;
          a.target = '_blank';
          a.rel = 'noopener noreferrer';
        } else {
          a = args?.[indexHolder.i++] as HTMLAnchorElement;
          if(typeof(a) !== 'object') {
            a = undefined;
          } else if(a) {
            a.textContent = ''; // reset content
          }
        }

        const formatted = superFormatter(text, args, indexHolder) as any;
        if(!a) {
          out.push(...formatted);
        } else {
          a.append(...formatted);
          out.push(a);
        }
      } else if(p5) {
        const noWhitespace = iconsNoWhitespace.includes(p5);
        if(!noWhitespace && !match.startsWith(p5)) out.push(match[0]);
        out.push(Icon(IconMap[p5], 'inline-icon'));
        if(!noWhitespace && match.startsWith(p5)) out.push(match[match.length - 1]);
      } else if(args) {
        const index = match.replace(/\D/g, '');
        pushNextArgument(
          out,
          args,
          indexHolder,
          !index || Number.isNaN(+index) ? undefined : Math.min(args.length - 1, +index - 1)
        );
      }

      lastIndex = offset + match.length;
      return '';
    });

    if(lastIndex !== input.length) {
      out.push(input.slice(lastIndex));
    }

    return out;
  }

  export function format<T extends boolean>(
    key: LangPackKey | string,
    plain?: T,
    args?: FormatterArguments
  ): T extends true ? string : ReturnType<typeof superFormatter> {
    // a missing key renders as the key itself, as in tweb
    const input = strings.get(key) ?? key;
    const result = superFormatter(input, args);
    if(plain) {
      return result.map((item) => item instanceof HTMLBRElement ? '\n' : (item instanceof Node ? item.textContent : item)).join('') as any;
    }

    return result as any;
  }

  export function i18n(key: LangPackKey | string, args?: FormatterArguments) {
    const element = document.createElement('span');
    element.classList.add('i18n');
    element.append(...format(key, false, args) as any);
    return element;
  }

  export function _i18n(element: HTMLElement, key: LangPackKey | string, args?: FormatterArguments) {
    element.replaceChildren(...format(key, false, args) as any);
    return element;
  }

  export function has(key: string): key is LangPackKey {
    return strings.has(key);
  }
}

export {I18n};
export default I18n;

const i18n = I18n.i18n;
export {i18n};

const _i18n = I18n._i18n;
export {_i18n};
