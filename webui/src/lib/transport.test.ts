import {afterEach, describe, expect, it, vi} from 'vitest';

import {hasSubmitted, pollQr, submitForm} from '@lib/transport';
import {state} from '@/fixtures';

afterEach(() => {
  vi.unstubAllGlobals();
});

function respond(body: unknown, contentType = 'application/json', status = 200) {
  return Promise.resolve(new Response(typeof(body) === 'string' ? body : JSON.stringify(body), {
    status,
    headers: {'Content-Type': contentType}
  }));
}

describe('submitForm', () => {
  it('POSTs the fields to the page, asking for JSON', async() => {
    const fetch = vi.fn((_url: string, _init: RequestInit) => respond({state: state('login.code')}));
    vi.stubGlobal('fetch', fetch);

    const result = await submitForm({type: 'phone', phone_number: '+39 333 1234567'});

    expect(result).toEqual({state: state('login.code')});
    const [url, init] = fetch.mock.calls[0];
    expect(url).toBe(window.location.href.split('#')[0]);
    expect(init.method).toBe('POST');
    expect((init.headers as Record<string, string>)['X-MadelineProto-WebUI']).toBe('json');
    expect(String(init.body)).toBe('type=phone&phone_number=%2B39+333+1234567');
  });

  it('turns the end of the login into a reload', async() => {
    vi.stubGlobal('fetch', vi.fn(() => respond({loggedIn: true})));
    expect(await submitForm({password: 'hunter2'})).toEqual({loggedIn: true});
  });

  it('reloads on an answer that is not JSON: the script printed its own page', async() => {
    vi.stubGlobal('fetch', vi.fn(() => respond('<html>The bot was started!</html>', 'text/html')));
    expect(await submitForm({app_title: 'x', app_shortname: 'xxxxx'})).toEqual({loggedIn: true});
  });

  it('marks the submission as pending while it runs', async() => {
    let resolve: (response: Response) => void;
    vi.stubGlobal('fetch', vi.fn(() => new Promise<Response>((r) => resolve = r)));
    const promise = submitForm({token: '123:abc'});
    expect(hasSubmitted()).toBe(true);
    resolve(new Response(JSON.stringify({state: state('login.signIn')}), {headers: {'Content-Type': 'application/json'}}));
    await promise;
    expect(hasSubmitted()).toBe(false);
  });
});

describe('pollQr', () => {
  it('asks the endpoint of the page', async() => {
    const fetch = vi.fn((_url: string, _init: RequestInit) => respond({logged_in: false, link: 'tg://login?token=a'}));
    vi.stubGlobal('fetch', fetch);

    expect(await pollQr('/login.php', 'getQrCode')).toEqual({logged_in: false, link: 'tg://login?token=a'});
    expect(fetch.mock.calls[0][0]).toBe(window.location.origin + '/login.php?getQrCode');
    expect(fetch.mock.calls[0][1].redirect).toBe('error');
  });

  it('never asks another origin', async() => {
    const fetch = vi.fn(() => respond({}));
    vi.stubGlobal('fetch', fetch);

    await expect(pollQr('//evil.example/x', 'getQrCode')).rejects.toThrow('another origin');
    await expect(pollQr('https://evil.example/x', 'getQrCode')).rejects.toThrow('another origin');
    expect(fetch).not.toHaveBeenCalled();
  });

  it('keeps a path starting with // on the origin of the page', async() => {
    const fetch = vi.fn((_url: string) => respond({logged_in: true}));
    vi.stubGlobal('fetch', fetch);

    await pollQr(window.location.origin + '//evil.example/x', 'waitQrCodeOrLogin');
    expect(fetch.mock.calls[0][0]).toBe(window.location.origin + '//evil.example/x?waitQrCodeOrLogin');
    expect(new URL(fetch.mock.calls[0][0]).origin).toBe(window.location.origin);
  });

  it('fails on HTTP errors', async() => {
    vi.stubGlobal('fetch', vi.fn(() => respond('', 'text/plain', 502)));
    await expect(pollQr('/login.php', 'getQrCode')).rejects.toThrow('HTTP 502');
  });
});
