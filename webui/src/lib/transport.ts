// How the cards talk to the PHP script that serves them (PROTOCOL.md): what tweb's cards ask its
// managers (auth.sendCode, auth.signIn, checkPassword, exportLoginToken...), MadelineProto does on the
// server, from the fields of the forms.

import type {State} from '@/types';

export type SubmitResult = {state: State} | {loggedIn: true};

export type QrResult = {logged_in: true} | {logged_in: false, link?: string, svg?: string};

let submitted = false;

/** Whether a form was submitted: a pending POST must never be cancelled by a reload (PROTOCOL.md). */
export function hasSubmitted() {
  return submitted;
}

/** The URL of the page, without its fragment: the script serves everything from there. */
function pageUrl() {
  return window.location.href.split('#')[0];
}

/**
 * POST the fields to the script, as the form of the server-rendered page would.
 *
 * `{state}`: the next page (or the same one, with an error). `{loggedIn}`: the login is over, and the
 * user's script runs in the GET of the reload; any successful answer that isn't JSON means the same (the
 * script printed its own page). A failed one (an error of PHP, of a proxy) is an error, not a login.
 */
export async function submitForm(fields: Record<string, string>): Promise<SubmitResult> {
  submitted = true;
  try {
    const response = await fetch(pageUrl(), {
      method: 'POST',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: {
        'X-MadelineProto-WebUI': 'json',
        'Accept': 'application/json'
      },
      body: new URLSearchParams(fields)
    });

    if(!response.ok) {
      throw new Error('HTTP ' + response.status);
    }

    if(!(response.headers.get('Content-Type') || '').includes('application/json')) {
      return {loggedIn: true};
    }

    const result = await response.json();
    if(result.loggedIn) {
      return {loggedIn: true};
    }

    if(!result.state?.page) {
      throw new Error('Unexpected answer');
    }

    return {state: result.state};
  } finally {
    submitted = false;
  }
}

/** Reload the page with a GET request (a reload could submit a POST again). */
export function reloadPage() {
  window.location.replace(pageUrl());
}

/**
 * One request of the QR code login long poll: `getQrCode` answers at once, `waitQrCodeOrLogin` within
 * ~5 seconds, with a new code or the login. Only ever asks the script that served the page: the answer
 * is drawn into it.
 */
export async function pollQr(endpoint: string, query: 'getQrCode' | 'waitQrCodeOrLogin', signal?: AbortSignal): Promise<QrResult> {
  const url = new URL(endpoint, window.location.href);
  if(url.origin !== window.location.origin) {
    throw new Error('The QR code endpoint is on another origin');
  }

  // the validated URL itself: a path starting with `//`, fetched on its own, would be another host. With the
  // query of the page, which may select the session or the bot (index.php?bot=x)
  url.search = url.search ? url.search + '&' + query : '?' + query;
  url.hash = '';
  const response = await fetch(url.href, {credentials: 'same-origin', cache: 'no-store', redirect: 'error', signal});
  if(!response.ok) {
    throw new Error('HTTP ' + response.status);
  }

  return response.json();
}
