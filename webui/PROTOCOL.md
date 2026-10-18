# Web UI protocol

How the web app (this directory, built into `src/WebUI/templates/assets/tweb/`) talks to MadelineProto. The
pages are served by the user's own PHP script (any URL): every request below goes to that same URL.

## Initial state

The HTML page served by the script contains:

```html
<head>
  <link id="madeline-app-css" rel="stylesheet" media="print" href="...?madelineAsset=assets/tweb/app.css">
</head>
<body>
  <script type="application/json" id="madeline-state">{"page": {...}, "assetUrl": "...", "lang": "en", "dir": "ltr", ...}</script>
  <div id="madeline-app"></div>
  <noscript><!-- server-rendered markup, for browsers without JavaScript --></noscript>
  <script defer src="...?madelineAsset=assets/tweb/app.js"></script>
</body>
```

The stylesheet is `media="print"` so that it doesn't apply to the markup for browsers without JavaScript: the
app sets it to `all` and waits for it to load before rendering into `#madeline-app`.

- `assetUrl`: prefix of the asset URLs: `assetUrl + "assets/tweb/tgs/TwoFactorSetupMonkeyIdle.json"`. It's a
  query string (`/script.php?madelineAsset=`), so the bundle can't rely on relative URLs: fonts are inlined in
  the CSS, and everything else is fetched with `assetUrl`.
- `page`: the current page (`danog\MadelineProto\WebUI\Page`):

```ts
type Text = string | {html: string};      // {html} is trusted markup built by MadelineProto
interface Page {
    view: string;                         // which card to show, see below
    title: string; lead: Text;            // localized texts of the server (fallback, may be ignored by cards)
    step: "app" | "account" | "verify" | "done" | null;
    fields: Field[];                      // the POST fields the server expects (names below)
    submit: string | null;
    alerts: {level: "info" | "success" | "warning" | "error"; message: Text; title: Text | null; code: Text | null}[];
    actions: {label: string; name: string; value: string}[];   // secondary actions: POST name=value
    instructions: Text[];
    qrEndpoint: string | null;
    data: Record<string, unknown>;        // per-view data, see below; data.rpcError: RPC error type of the last POST
}
```

## Views

| `view` | Card | POST fields | `data` |
|---|---|---|---|
| `api.auto` | Get the API ID from my.telegram.org: phone of an existing account | `my_phone`; action `api_setup=manual` | |
| `api.code` | Code that my.telegram.org sent in the Telegram app (alphanumeric) | `my_code`; actions `api_setup=reset`, `api_setup=manual` | `phoneNumber` |
| `api.create` | Create the app | `app_title`, `app_shortname`, `app_url` (optional), `app_desc` (optional) | |
| `api.manual` | Enter API ID and hash | `api_id`, `api_hash`; action `api_setup=reset` | |
| `login.signIn` | Phone number (or bot token), QR code login | `type=phone` + `phone_number`, or `type=bot` + `token` | |
| `login.code` | Login code | `phone_code` | `phoneNumber`, `sentCode: {type, length, nextType, timeout}` (`type` like `auth.sentCodeTypeApp`) |
| `login.password` | 2FA password | `password` | `hint` (may be empty) |
| `login.signUp` | First and last name of a new account | `first_name`, `last_name` | `phoneNumber` |
| `status` | Status message (the bot was started...), no form | | |

Unknown views: render `page` generically (title, lead, alerts, fields, submit, actions).

## Submitting

```js
const response = await fetch(location.href, {
    method: "POST",
    headers: {"X-MadelineProto-WebUI": "json", "Accept": "application/json"},
    body: new URLSearchParams({phone_number: "+39..."}),
});
const result = await response.json();
// {"state": {page, ...}}  the next (or the same, with an error) page
// {"loggedIn": true}       logged in: reload the page with a GET (location.replace(location.href))
```

The POST fields are exactly those of the server-rendered form, so the server handles both the same way. Without
the header, the server answers with HTML (the fallback without JavaScript).

The `loggedIn` answer ends that request: the user's script then runs once, in the GET of the reload.

Any answer that isn't JSON (no `application/json` content type: the user's script printed its own page, for
example after the last step of the API ID setup) also means: reload the page with a GET.

## QR code login

`GET qrEndpoint + "?getQrCode"` returns the current QR code; `GET qrEndpoint + "?waitQrCodeOrLogin"` returns
within ~5 seconds, with a new QR code (the previous one expired) or the login:

```json
{"logged_in": false, "link": "tg://login?token=...", "svg": "<svg>...</svg>"}
{"logged_in": true}
```

`logged_in: true` also when the login moved on to the 2FA password: reload the page with a GET. Never reload
after a form was submitted (the pending POST would be cancelled in the browser, not on the server).

## Assets

`GET <script>?madelineAsset=<path>` serves `<path>` from the template directories (only under `assets/`), with
caching headers.
