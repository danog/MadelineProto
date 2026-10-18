/*
 * https://github.com/morethanwords/tweb
 * Copyright (C) 2019-2026 Eduard Kuzmenko
 * https://github.com/morethanwords/tweb/blob/master/LICENSE
 *
 * MadelineProto: the sent code is what the server tells of it (`page.data.sentCode`), turned into the
 * shape of tweb's `auth.sentCode` (`fromSentCodeInfo`) so the helpers below read it unchanged. The
 * routing to other cards (`sentCodeToCardSpec`) is the server's: it answers with the next page.
 */

import type {SentCodeInfo} from '@/types';

export type AuthSentCodeType = {
  _: string,
  length?: number,
  beginning?: string,
  prefix?: string,
  url?: string,
  email_pattern?: string
};

export namespace AuthSentCode {
  export type authSentCode = {
    _: 'auth.sentCode',
    type: AuthSentCodeType,
    next_type?: {_: string},
    timeout?: number
  };
}

/** The sent code of the server, in the shape of tweb's `auth.sentCode`. */
export function fromSentCodeInfo(info: SentCodeInfo | undefined, phone_number?: string): SentCode {
  return {
    _: 'auth.sentCode',
    type: {
      _: info?.type || 'auth.sentCodeTypeApp',
      length: info?.length ?? undefined,
      beginning: info?.beginning ?? undefined,
      prefix: info?.prefix ?? undefined,
      url: info?.url ?? undefined,
      email_pattern: info?.emailPattern ?? undefined
    },
    next_type: info?.nextType ? {_: info.nextType} : undefined,
    timeout: info?.timeout ?? undefined,
    phone_number
  };
}

/**
 * Everything the auth flow needs to know about an `auth.sentCode`, in one place.
 *
 * `auth.sendCode`, `auth.resendCode`, `auth.resetLoginEmail` and the `sent_code`
 * inside `account.emailVerifiedLogin` all answer with the same type, and each of
 * them can switch the login to a different kind of code mid-flow — so every one
 * of them routes through `sentCodeToCardSpec()` rather than assuming the code
 * card.
 */

export type SentCode = AuthSentCode.authSentCode & {
  phone_number?: string,
  /**
   * When `auth.resendCode` becomes available, in `tsNow(true)` seconds. Fixed the
   * first time the code is shown and stored with it, so a reload carries on with
   * the countdown instead of starting it over.
   */
  resend_deadline?: number
};

/** How the code is typed in: digit boxes, or a free-text field for a word/phrase. */
export type CodeInputKind = 'digits' | 'text';

/** Falls back to 5 the way the other clients do when the type carries no length. */
const DEFAULT_CODE_LENGTH = 5;

export function getCodeInputKind(type: AuthSentCodeType): CodeInputKind {
  return type._ === 'auth.sentCodeTypeSmsWord' || type._ === 'auth.sentCodeTypeSmsPhrase' ?
    'text' :
    'digits';
}

export function getCodeLength(type: AuthSentCodeType): number {
  return type.length || DEFAULT_CODE_LENGTH;
}

/**
 * The word/phrase types carry the start of the secret the SMS holds, so the
 * client can reject a typo before spending a server attempt.
 */
export function getCodeBeginning(type: AuthSentCodeType): string | undefined {
  return type._ === 'auth.sentCodeTypeSmsWord' || type._ === 'auth.sentCodeTypeSmsPhrase' ?
    type.beginning :
    undefined;
}

/** Whether what the user typed can still grow into the expected word/phrase. */
export function beginsOk(value: string, beginning: string | undefined): boolean {
  if(!beginning) return true;
  const typed = value.trimStart().toLowerCase();
  const expected = beginning.toLowerCase();
  const length = Math.min(typed.length, expected.length);
  if(length <= 0) return true;
  return typed.slice(0, length) === expected.slice(0, length);
}

