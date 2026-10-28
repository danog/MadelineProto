// Sample pages of every view, as MadelineProto sends them (see PROTOCOL.md and src/Wrappers/Start.php,
// src/ApiWrappers/Start.php): for the tests, and for `npm run dev`.

import type {Alert, Field, Page, State} from '@/types';

const field = (name: string, label: string, extra: Partial<Field> = {}): Field => ({
  name,
  label,
  type: 'text',
  value: '',
  placeholder: '',
  hint: '',
  required: true,
  autocomplete: null,
  options: {},
  showWhen: null,
  autofocus: false,
  maxLength: null,
  ...extra
});

const gmp: Alert = {level: 'warning', message: 'Warning: the gmp extension is not installed, please install it to speed up MadelineProto!', title: null, code: null};

const base = (view: string, title: string, extra: Partial<Page> = {}): Page => ({
  view,
  title,
  lead: '',
  step: null,
  fields: [],
  submit: null,
  alerts: [gmp],
  actions: [],
  instructions: [],
  qrEndpoint: null,
  data: {},
  sessions: null,
  ...extra
});

export const pages: Record<string, Page> = {
  'api.auto': base('api.auto', 'Get your API ID and hash', {
    lead: 'Every Telegram app needs an API ID and hash. MadelineProto can get them for you from my.telegram.org, with the phone number of an existing Telegram account.',
    step: 'app',
    fields: [field('my_phone', 'Phone number', {type: 'tel', hint: 'my.telegram.org sends the login code to your Telegram app, not by SMS.'})],
    submit: 'Send code',
    actions: [{label: 'I already have an API ID', name: 'api_setup', value: 'manual'}]
  }),
  'api.code': base('api.code', 'Enter the code from Telegram', {
    lead: 'my.telegram.org sent a login code to your Telegram app, in the chat with Telegram (not by SMS).',
    step: 'app',
    fields: [field('my_code', 'Login code', {type: 'password', autocomplete: 'one-time-code'})],
    submit: 'Continue',
    actions: [
      {label: 'Start over', name: 'api_setup', value: 'reset'},
      {label: 'I already have an API ID', name: 'api_setup', value: 'manual'}
    ],
    data: {phoneNumber: '+39 333 1234567'}
  }),
  'api.create': base('api.create', 'Create your app', {
    lead: 'This account has no app on my.telegram.org yet. These details are only shown to you, and can be changed later.',
    step: 'app',
    fields: [
      field('app_title', 'App title', {hint: 'Your app\'s name, can be anything'}),
      field('app_shortname', 'Short name', {hint: 'Your app\'s short name, alphanumeric, 5-32 characters', maxLength: 32}),
      field('app_url', 'URL', {hint: 'Your app/website\'s URL, or t.me/yourusername', required: false}),
      field('app_desc', 'Description', {type: 'textarea', required: false})
    ],
    submit: 'Create app',
    actions: [
      {label: 'Start over', name: 'api_setup', value: 'reset'},
      {label: 'I already have an API ID', name: 'api_setup', value: 'manual'}
    ]
  }),
  'api.manual': base('api.manual', 'Enter your API ID and API hash', {
    lead: {html: 'Note that you can also provide the API ID/hash directly in the code using the <a target="_blank" href="https://docs.madelineproto.xyz/docs/SETTINGS.html">settings</a>.'},
    step: 'app',
    fields: [
      field('api_id', 'API ID', {placeholder: '1234567'}),
      field('api_hash', 'API hash', {type: 'password', maxLength: 32, autocomplete: 'off'})
    ],
    submit: 'Save and continue',
    actions: [{label: 'Get them automatically', name: 'api_setup', value: 'reset'}],
    instructions: [
      {html: 'Login to <a href="https://my.telegram.org" target="_blank" rel="noopener noreferrer">https://my.telegram.org</a>'},
      'Go to API development tools',
      {html: 'Enter API information:<ul><li>App title: your app&apos;s name, can be anything</li></ul>'},
      'Click on create application'
    ]
  }),
  'login.signIn': base('login.signIn', 'Log in to Telegram', {
    step: 'account',
    fields: [
      field('type', 'Log in as', {type: 'choice', value: 'phone', options: {phone: 'User', bot: 'Bot'}}),
      field('phone_number', 'Phone number', {type: 'tel', showWhen: ['type', 'phone']}),
      field('token', 'Bot token', {type: 'password', showWhen: ['type', 'bot']})
    ],
    submit: 'Continue',
    qrEndpoint: '/'
  }),
  'login.code': base('login.code', 'Enter the login code', {
    step: 'verify',
    fields: [field('phone_code', 'Code', {type: 'code', maxLength: 12})],
    submit: 'Log in',
    data: {phoneNumber: '+39 333 1234567', sentCode: {type: 'auth.sentCodeTypeApp', length: 5, nextType: 'auth.codeTypeSms', timeout: 60}}
  }),
  'login.password': base('login.password', 'Two-step verification', {
    step: 'verify',
    fields: [field('password', 'Password', {type: 'password'})],
    submit: 'Log in',
    data: {hint: 'the usual one'}
  }),
  'login.signUp': base('login.signUp', 'Create your account', {
    step: 'verify',
    fields: [
      field('first_name', 'First name', {autocomplete: 'given-name'}),
      field('last_name', 'Last name', {required: false, autocomplete: 'family-name'})
    ],
    submit: 'Continue',
    data: {phoneNumber: '+39 333 1234567'}
  }),
  'status': base('status', 'The bot was started!', {
    lead: 'MadelineProto keeps running on the server: you can close this page.',
    step: 'done',
    alerts: [{
      level: 'error',
      message: 'Warning: no report peers are set, please add the following method to your event handler',
      title: null,
      code: 'public function getReportPeers() { return \'@yourtelegramusername\'; }'
    }]
  })
};

/** The state of a page, with an error of the last submission if any. */
export function state(view: string, error?: {rpcError?: string, message?: string}): State {
  const page: Page = structuredClone(pages[view] ?? base(view, 'Unknown page ' + view));
  if(error?.rpcError) page.data.rpcError = error.rpcError;
  if(error?.message || error?.rpcError) {
    page.alerts.push({level: 'error', message: (error.message ?? error.rpcError) + '. Check it and try again.', title: null, code: null});
  }
  return {page, assetUrl: '/?madelineAsset=', lang: 'en', dir: 'ltr'};
}
