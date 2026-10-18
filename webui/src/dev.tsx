// `npm run dev`: the cards without MadelineProto. `/?view=login.code` opens a page of src/fixtures.ts, and a
// mock of the script answers the forms (codes: 12345; password: hunter2; my.telegram.org short name "fail"
// fails). The real thing: the interactive preview of PROTOCOL.md's endpoints, served by PHP.

import {pages, state} from '@/fixtures';
import type {State} from '@/types';

(window as any).__MADELINE_DEV__ = true;

const next: Record<string, (fields: Record<string, string>) => State | {loggedIn: true}> = {
  'api.auto': (f) => f.api_setup === 'manual' ? state('api.manual') : state('api.code'),
  'api.code': (f) => f.api_setup === 'manual' ? state('api.manual') :
    f.api_setup === 'reset' ? state('api.auto') :
    f.my_code === '12345' ? state('api.create') : state('api.code', {message: 'Invalid confirmation code!'}),
  'api.create': (f) => f.api_setup === 'manual' ? state('api.manual') :
    f.api_setup === 'reset' ? state('api.auto') :
    f.app_shortname === 'fail' ? state('api.create', {message: 'ERROR. Telegram doesn\'t let VPS, VPN and other datacenter IP ranges create new apps on my.telegram.org'}) :
    state('login.signIn'),
  'api.manual': (f) => f.api_setup === 'reset' ? state('api.auto') :
    /^\d+$/.test(f.api_id) ? state('login.signIn') : state('api.manual', {message: 'The API ID is a number, and the API hash is 32 characters long'}),
  'login.signIn': (f) => f.type === 'bot' ?
    (/^\d+:\w{10,}$/.test(f.token) ? {loggedIn: true} : state('login.signIn', {rpcError: 'ACCESS_TOKEN_INVALID'})) :
    state('login.code'),
  'login.code': (f) => f.phone_code === '12345' ? state('login.password') :
    f.phone_code === '11111' ? state('login.signUp') : state('login.code', {rpcError: 'PHONE_CODE_INVALID'}),
  'login.password': (f) => f.password === 'hunter2' ? {loggedIn: true} : state('login.password', {rpcError: 'PASSWORD_HASH_INVALID'}),
  'login.signUp': () => ({loggedIn: true})
};

let view = new URLSearchParams(location.search).get('view') ?? 'login.signIn';
if(!pages[view]) view = 'login.signIn';

const realFetch = window.fetch.bind(window);
window.fetch = async(input: RequestInfo | URL, init?: RequestInit) => {
  const url = String(input instanceof Request ? input.url : input);
  const json = (body: unknown, delay = 400) => new Promise<Response>((resolve) => setTimeout(() => {
    resolve(new Response(JSON.stringify(body), {headers: {'Content-Type': 'application/json'}}));
  }, delay));

  if(init?.method === 'POST') {
    const fields = Object.fromEntries(new URLSearchParams(String(init.body)));
    const answer = next[view]?.(fields) ?? {loggedIn: true};
    if('loggedIn' in answer) {
      view = 'status';
      return json({state: state('status')});
    }
    view = answer.page.view;
    return json({state: answer});
  }
  if(url.includes('getQrCode')) {
    return json({logged_in: false, link: 'tg://login?token=' + Math.random().toString(36).slice(2)}, 200);
  }
  if(url.includes('waitQrCodeOrLogin')) {
    return json({logged_in: false, link: 'tg://login?token=' + Math.random().toString(36).slice(2)}, 5000);
  }
  if(url.includes('madelineAsset=')) {
    // the monkeys: from the built assets
    return realFetch('/@fs' + decodeURIComponent(url.split('madelineAsset=')[1]).replace(/^assets\//, ASSETS + '/'));
  }
  return realFetch(input, init);
};

declare const ASSETS: string;

const {mount} = await import('./main');
const root = document.getElementById('madeline-app');
mount(root, state(view));
