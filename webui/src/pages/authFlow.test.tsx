import {cleanup, render, screen, waitFor} from '@solidjs/testing-library';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';

import {state} from '@/fixtures';
import type {State} from '@/types';

// * jsdom has no canvas: the monkeys and the QR code are stand-ins
vi.mock('@lib/lottie/lottieLoader', () => {
  class LottiePlayer {
    public canvas = [document.createElement('div')];
    public direction = 1;
    public curFrame = 0;
    addEventListener() {}
    play() {}
    pause() {}
    stop() {}
    setDirection(direction: number) { this.direction = direction; }
    setSpeed() {}
    remove() {}
  }
  const lottieLoader = {
    setAssetUrl() {},
    loadAnimationFromURLManually: () => Promise.resolve({}),
    loadAnimationAsAsset: () => Promise.resolve(new LottiePlayer()),
    waitForFirstFrame: () => Promise.resolve()
  };
  return {default: lottieLoader, LottiePlayer};
});

const painted: string[] = [];
vi.mock('qr-code-styling', () => ({
  default: class {
    constructor(public options: {data: string}) {
      painted.push(options.data);
    }
    append(host: HTMLElement) {
      host.append(document.createElement('canvas'));
    }
    _drawingPromise = Promise.resolve();
  }
}));

const reloadPage = vi.hoisted(() => vi.fn());
vi.mock('@lib/transport', async(importOriginal) => ({
  ...(await importOriginal<typeof import('@lib/transport')>()),
  reloadPage
}));

import {pageToCardSpec, startAuth} from '@/pages/authFlow';
import AuthCardsHost from '@/pages/AuthCardsHost';

type Answer = State | {loggedIn: true} | string;

/** The server: answers the POSTs in order, records them. */
function server(answers: Answer[]) {
  const posts: Array<Record<string, string>> = [];
  const fetch = vi.fn((url: string, init?: RequestInit) => {
    if(init?.method !== 'POST') {
      return new Promise<Response>(() => {});
    }
    posts.push(Object.fromEntries(new URLSearchParams(String(init.body))));
    const answer = answers.shift();
    if(typeof(answer) === 'string') {
      return Promise.resolve(new Response(answer, {headers: {'Content-Type': 'text/html'}}));
    }
    const body = 'loggedIn' in answer ? answer : {state: answer};
    return Promise.resolve(new Response(JSON.stringify(body), {headers: {'Content-Type': 'application/json'}}));
  });
  vi.stubGlobal('fetch', fetch);
  return posts;
}

function mount(view: string) {
  startAuth(state(view));
  return render(() => <AuthCardsHost />);
}

const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

function type(input: HTMLInputElement, value: string) {
  input.value = value;
  input.dispatchEvent(new Event('input', {bubbles: true}));
}

function clickButton(text: RegExp) {
  const button = screen.getAllByRole('button').find((button) => text.test(button.textContent));
  if(!button) throw new Error('No button ' + text);
  button.click();
}

beforeEach(() => {
  painted.length = 0;
  reloadPage.mockReset();
});

afterEach(() => {
  cleanup();
  vi.unstubAllGlobals();
});

describe('pageToCardSpec', () => {
  it('gives every view its card', () => {
    expect(Object.fromEntries(['login.signIn', 'login.code', 'login.password', 'login.signUp', 'status', 'api.auto', 'api.code', 'api.create', 'api.manual', 'something.else'].map((view) => {
      return [view, pageToCardSpec(state(view).page).name];
    }))).toEqual({
      'login.signIn': 'signIn',
      'login.code': 'authCode',
      'login.password': 'password',
      'login.signUp': 'signUp',
      'status': 'status',
      'api.auto': 'apiForm',
      'api.code': 'apiForm',
      'api.create': 'apiForm',
      'api.manual': 'apiForm',
      'something.else': 'apiForm'
    });
  });
});

describe('the cards', () => {
  it('sign in: the country picker types the prefix, and the number goes to the server', async() => {
    const posts = server([state('login.code')]);
    mount('login.signIn');

    const country = screen.getByLabelText('Country') as HTMLInputElement;
    const phone = screen.getByLabelText('Phone Number') as HTMLInputElement;
    country.dispatchEvent(new MouseEvent('mousedown', {bubbles: true}));
    country.focus();
    const italy = [...document.querySelectorAll('.select-wrapper li')].find((li) => li.textContent.includes('Italy')) as HTMLElement;
    italy.dispatchEvent(new MouseEvent('mousedown', {bubbles: true}));
    italy.click();
    await sleep(10);
    expect(phone.value).toBe('+39');
    expect(country.value).toBe('Italy');

    type(phone, '+39 333 1234567');
    clickButton(/^Next/);
    await waitFor(() => expect(document.querySelector('h4')?.textContent).toBe('+39 333 1234567'));
    expect(posts).toEqual([{type: 'phone', phone_number: '+39 333 123 4567'}]);
  });

  it('sign in: a number the server rejects stays, with tweb\'s label', async() => {
    server([state('login.signIn', {rpcError: 'PHONE_NUMBER_INVALID'})]);
    mount('login.signIn');
    const phone = screen.getByLabelText('Phone Number') as HTMLInputElement;
    type(phone, '+39 1');
    clickButton(/^Next/);
    await waitFor(() => expect(screen.getByText('Phone Number Invalid')).toBeTruthy());
    expect(phone.classList.contains('error')).toBe(true);
  });

  it('bot: the token goes to the server, its error stays on the bot card', async() => {
    const posts = server([state('login.signIn', {rpcError: 'ACCESS_TOKEN_INVALID'}), {loggedIn: true}]);
    mount('login.signIn');
    clickButton(/Log in as a bot/);
    await waitFor(() => expect(screen.getByText('Log in as a Bot')).toBeTruthy());

    const token = document.querySelector('#auth-pages .input-field-input') as HTMLInputElement;
    type(token, '123:wrong');
    clickButton(/^Next/);
    await waitFor(() => expect(screen.getByText('Invalid bot token')).toBeTruthy());
    expect(screen.getByText('Log in as a Bot')).toBeTruthy();

    type(token, '123:right');
    clickButton(/^Next/);
    await waitFor(() => expect(reloadPage).toHaveBeenCalledOnce());
    expect(posts).toEqual([{type: 'bot', token: '123:wrong'}, {type: 'bot', token: '123:right'}]);
  });

  it('code: submits itself once every digit is typed, and shows the error of the server', async() => {
    const posts = server([state('login.code', {rpcError: 'PHONE_CODE_INVALID'}), state('login.password')]);
    mount('login.code');
    expect(screen.getByText(/We have sent you a message in Telegram/)).toBeTruthy();

    const code = document.querySelector('#auth-pages input') as HTMLInputElement;
    type(code, '1234');
    await sleep(10);
    expect(posts).toEqual([]);
    type(code, '12345');
    await waitFor(() => expect(screen.getByText('Invalid code')).toBeTruthy());

    type(code, '54321');
    await waitFor(() => expect(screen.getByText('Enter Your Password')).toBeTruthy());
    expect(posts).toEqual([{phone_code: '12345'}, {phone_code: '54321'}]);
  });

  it('password: the hint is the label, the eye shows the password', async() => {
    const posts = server([state('login.password', {rpcError: 'PASSWORD_HASH_INVALID'}), {loggedIn: true}]);
    mount('login.password');
    expect(screen.getByText('the usual one')).toBeTruthy();

    const password = document.querySelector('#auth-pages .input-field-password input.input-field-input') as HTMLInputElement;
    expect(password.type).toBe('password');
    (document.querySelector('.toggle-visible') as HTMLElement).click();
    expect(password.type).toBe('text');

    type(password, 'wrong');
    clickButton(/^Next/);
    await waitFor(() => expect(screen.getByText('Incorrect password')).toBeTruthy());

    type(password, 'hunter2');
    clickButton(/Incorrect password|^Next/);
    await waitFor(() => expect(reloadPage).toHaveBeenCalledOnce());
    expect(posts).toEqual([{password: 'wrong'}, {password: 'hunter2'}]);
  });

  it('sign up: the name goes to the server', async() => {
    const posts = server(['<html>The bot was started!</html>']);
    mount('login.signUp');
    type(screen.getByLabelText('First name (required)') as HTMLInputElement, 'Daniil');
    expect(screen.getByRole('heading', {level: 1}).textContent).toBe('Daniil');
    clickButton(/Start Messaging/);
    await waitFor(() => expect(reloadPage).toHaveBeenCalledOnce());
    expect(posts).toEqual([{first_name: 'Daniil', last_name: ''}]);
  });

  it('API ID setup: the fields, the actions, and the errors of the server', async() => {
    const vps = 'ERROR. Telegram doesn\'t let VPS, VPN and other datacenter IP ranges create new apps';
    const posts = server([state('api.create', {message: vps}), state('api.manual')]);
    mount('api.create');
    expect(screen.getByText('Create your app')).toBeTruthy();

    // the required fields are checked before anything is sent
    clickButton(/^Create app/);
    await sleep(10);
    expect(posts).toEqual([]);
    expect(screen.getByLabelText('App title').classList.contains('error')).toBe(true);

    type(screen.getByLabelText('App title') as HTMLInputElement, 'My bot');
    type(screen.getByLabelText('Short name') as HTMLInputElement, 'fail');
    clickButton(/^Create app/);
    await waitFor(() => expect(screen.getByText(/VPS, VPN and other datacenter IP ranges/)).toBeTruthy());

    clickButton(/I already have an API ID/);
    await waitFor(() => expect(screen.getByText('Enter your API ID and API hash')).toBeTruthy());
    expect(posts).toEqual([
      {app_title: 'My bot', app_shortname: 'fail', app_url: '', app_desc: ''},
      {api_setup: 'manual'}
    ]);
    expect(screen.getByText('settings').getAttribute('href')).toBe('https://docs.madelineproto.xyz/docs/SETTINGS.html');
  });

  it('status: the notices of the server, and their code', () => {
    mount('status');
    expect(screen.getByText('The bot was started!')).toBeTruthy();
    expect(screen.getByText(/getReportPeers/).closest('pre')).toBeTruthy();
    expect(document.querySelector('form, input')).toBeNull();
  });

  it('never renders the server\'s plain text as markup', () => {
    const s = state('status');
    s.page.title = '<img src=x onerror=alert(1)>';
    startAuth(s);
    render(() => <AuthCardsHost />);
    expect(document.querySelector('img')).toBeNull();
    expect(screen.getByText('<img src=x onerror=alert(1)>')).toBeTruthy();
  });
});

describe('QR code login', () => {
  function qrServer(answers: Array<{logged_in: boolean, link?: string}>) {
    const queries: string[] = [];
    vi.stubGlobal('fetch', vi.fn((url: string, init?: RequestInit) => {
      if(init?.method === 'POST') {
        return new Promise<Response>(() => {});
      }
      queries.push(url.split('?')[1]);
      const answer = answers.shift();
      if(!answer) return new Promise<Response>(() => {});
      return Promise.resolve(new Response(JSON.stringify(answer), {headers: {'Content-Type': 'application/json'}}));
    }));
    return queries;
  }

  it('paints the link, long-polls, and reloads once scanned', async() => {
    const queries = qrServer([
      {logged_in: false, link: 'tg://login?token=one'},
      {logged_in: false, link: 'tg://login?token=two'},
      {logged_in: true}
    ]);
    mount('login.signIn');
    clickButton(/Log in by QR Code/);
    await waitFor(() => expect(reloadPage).toHaveBeenCalledOnce());
    expect(painted).toEqual(['tg://login?token=one', 'tg://login?token=two']);
    expect(queries).toEqual(['getQrCode', 'waitQrCodeOrLogin', 'waitQrCodeOrLogin']);
  });

  it('repaints for the other theme in place, without two codes in the slot at once', async() => {
    qrServer([{logged_in: false, link: 'tg://login?token=one'}]);
    mount('login.signIn');
    clickButton(/Log in by QR Code/);
    await waitFor(() => expect(document.querySelectorAll('#auth-pages canvas')).toHaveLength(1));
    const slot = document.querySelector('#auth-pages canvas')!.parentElement!;
    const first = slot.querySelector('canvas');

    // the most canvases the slot ever holds while the code is repainted
    let most = 0;
    const observer = new MutationObserver(() => {
      most = Math.max(most, slot.querySelectorAll('canvas').length);
    });
    observer.observe(slot, {childList: true});
    window.dispatchEvent(new CustomEvent('madeline-theme-change'));
    await waitFor(() => expect(painted).toEqual(['tg://login?token=one', 'tg://login?token=one']));
    await waitFor(() => expect(slot.querySelector('canvas')).not.toBe(first));
    observer.disconnect();
    expect(slot.querySelectorAll('canvas')).toHaveLength(1);
    expect(most).toBe(1);
  });

  it('never reloads while a form is being submitted', async() => {
    let release: () => void;
    const pending = new Promise<void>((resolve) => release = resolve);
    vi.stubGlobal('fetch', vi.fn(async(url: string, init?: RequestInit) => {
      if(init?.method === 'POST') {
        return new Promise<Response>(() => {});
      }
      if(url.endsWith('getQrCode')) {
        return new Response(JSON.stringify({logged_in: false, link: 'tg://login?token=one'}), {headers: {'Content-Type': 'application/json'}});
      }
      await pending;
      return new Response(JSON.stringify({logged_in: true}), {headers: {'Content-Type': 'application/json'}});
    }));
    mount('login.signIn');
    clickButton(/Log in by QR Code/);
    await waitFor(() => expect(painted).toEqual(['tg://login?token=one']));

    // a bot login submitted from another tab of the flow would change the authorization: here, a pending POST
    const {submitForm} = await import('@lib/transport');
    submitForm({type: 'bot', token: '123:abc'});
    release();
    await sleep(50);
    expect(reloadPage).not.toHaveBeenCalled();
  });
});

describe('the theme', () => {
  it('toggles night mode', () => {
    mount('status');
    const before = document.documentElement.classList.contains('night');
    (document.querySelector('#auth-pages > button, #auth-pages .btn-icon') as HTMLElement).click();
    expect(document.documentElement.classList.contains('night')).toBe(!before);
  });
});
