// The JSON the server sends (see PROTOCOL.md), and the few Telegram types the cards of tweb read.

/** Plain text, or trusted HTML built by MadelineProto (`SafeHtml`). */
export type Text = string | {html: string};

export type FieldType = 'text' | 'tel' | 'password' | 'code' | 'url' | 'textarea' | 'hidden' | 'choice';

export interface Field {
  name: string;
  label: string;
  type: FieldType;
  value: string;
  placeholder: string;
  hint: Text;
  required: boolean;
  autocomplete: string | null;
  options: Record<string, string>;
  showWhen: [string, string] | null;
  autofocus: boolean;
  maxLength: number | null;
}

export type AlertLevel = 'info' | 'success' | 'warning' | 'error';

export interface Alert {
  level: AlertLevel;
  message: Text;
  title: Text | null;
  code: Text | null;
}

export interface Action {
  label: string;
  name: string;
  value: string;
}

export interface Page {
  view: string;
  title: string;
  lead: Text;
  step: 'app' | 'account' | 'verify' | 'done' | null;
  fields: Field[];
  submit: string | null;
  alerts: Alert[];
  actions: Action[];
  instructions: Text[];
  qrEndpoint: string | null;
  data: {
    phoneNumber?: string,
    sentCode?: SentCodeInfo,
    hint?: string,
    rpcError?: string,
    [key: string]: unknown
  };
}

export interface State {
  page: Page;
  /** Prefix of the asset URLs: `assetUrl + 'assets/tweb/…'`. */
  assetUrl: string;
  lang?: string;
  dir?: 'ltr' | 'rtl';
  [key: string]: unknown;
}

/** What the server tells of the code it sent (`page.data.sentCode` on `login.code`). */
export interface SentCodeInfo {
  /** `auth.sentCodeTypeApp`, `auth.sentCodeTypeSms`, ... */
  type: string;
  length?: number | null;
  nextType?: string | null;
  timeout?: number | null;
  /** The secret word/phrase types disclose how it starts. */
  beginning?: string | null;
  /** The missed call types: the prefix of the calling number. */
  prefix?: string | null;
  /** The Fragment type: where to get the code. */
  url?: string | null;
  /** The email type. */
  emailPattern?: string | null;
}

// * from tweb's layer.d.ts

export type HelpCountryCode = {
  _?: 'help.countryCode',
  country_code: string,
  prefixes?: string[],
  patterns?: string[]
};

export type HelpCountry = {
  _?: 'help.country',
  pFlags?: Partial<{hidden: true}>,
  iso2: string,
  default_name: string,
  name?: string,
  country_codes: HelpCountryCode[]
};

export type HelpCountriesList = {
  _: 'help.countriesList',
  countries: HelpCountry[],
  hash?: number
};
