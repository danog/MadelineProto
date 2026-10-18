# MadelineProto web UI

The web pages of MadelineProto, the API ID setup (manual, or automatic through my.telegram.org), the login
(phone number, bot token, QR code, 2FA password, signup) and the status pages, as a port of the auth pages of
[tweb](https://github.com/morethanwords/tweb) (Telegram Web K): the same cards, the same stack (TypeScript,
Solid, SCSS modules, Vite), MadelineProto's purple instead of Telegram's blue, and MadelineProto (PHP) as
the backend instead of tweb's MTProto worker.

tweb is licensed under the GNU GPL v3, and so is this app (`GPL-3.0-or-later`): it's compatible with
MadelineProto's AGPL v3 (section 13 of both). The files out of tweb keep its copyright header, with
"Adapted for MadelineProto" on the ones that were changed.

## How it fits together

- The PHP side builds each page as a view model (`src/WebUI`: `Page`, `Field`, `Alert`, `Action`) and
  renders it with native PHP templates (`src/WebUI/templates`, see `TemplateEngine`): that markup is for
  browsers without JavaScript. The page also embeds the view model as JSON (`#madeline-state`).
- This app (built into `src/WebUI/templates/assets/tweb/`, served by MadelineProto's asset route
  `?madelineAsset=`) renders the page as a tweb card, picked by `Page::$view`, and submits the same fields
  with `fetch`: the server answers with the next page. [PROTOCOL.md](PROTOCOL.md) is the contract.
- Users customize the pages by overriding single templates from a directory of theirs:
  `$settings->getTemplates()->addDirectory(__DIR__.'/templates')` (an `assets/tweb/app.js` there replaces the
  app too).

## What was ported, what was rewritten

Ported from tweb (`src/`, same paths as in tweb):

- the auth flow: `pages/authFlow.tsx` (router and context), `pages/AuthCardsHost.tsx` (shell, theme button,
  card transitions), `pages/AuthCard.tsx`, `pages/AuthCardError.tsx`, `pages/authFlow.module.scss`,
  `pages/sentCode.ts`;
- the cards: `SignInCard` (country picker and phone), `AuthCodeCard` (digit boxes or a secret word, the
  tracking monkey), `PasswordCard` (the peeking monkey), `SignUpCard`, `SignQRCard` (tweb's styled QR code);
- the components and helpers they use: the input fields (`telInputField`, `countryInputField`,
  `passwordInputField`, `codeInputField`), `mediaHeader`, `buttonTsx`, `ripple`, `autoHeight`,
  `putPreloader`, the monkeys, `formatPhoneNumber`, `paintQrCode`, the DOM and scheduling helpers, the country
  list, and the SCSS (variables, mixins, inputs, buttons, ripple, preloader, Roboto).

Rewritten, because tweb's versions are tied to the rest of its app (its MTProto worker, storage, settings):

- `lib/transport.ts` instead of tweb's managers: the POSTs and the QR code long poll of PROTOCOL.md;
- `lib/langPack.ts` (a lite `I18n`, with tweb's formatter) and `lang.ts` (the English strings of the auth
  flow, out of tweb's `lang.ts` and `langSign.ts`, with MadelineProto's own);
- `components/inputField.ts` (the same DOM and API, on a plain `<input>`), `helpers/theme.ts` and
  `scss/theme.scss` (static day and night palettes derived from one accent, instead of the themeController),
  `lib/lottie/lottieLoader.ts` (lottie-web instead of tweb's WASM player), `components/icon.ts` (the few
  glyphs as SVG instead of the 111 KB icon font), `components/logo.tsx` (MadelineProto's mark instead of
  Telegram's logo);
- MadelineProto's own cards, in tweb's style: `SignBotCard` (bot token), `ApiFormCard` (the API ID setup,
  and any page without a card of its own), `StatusCard`.

Left out, because MadelineProto doesn't offer them through the web login: resending the code, the login email,
editing the phone number, password recovery, account reset, passkeys, the language picker, the avatar upload.

## Development

```sh
npm ci
npm run dev        # the pages of src/fixtures.ts with a mock server: /?view=login.signIn, /?view=api.auto...
npm test
npm run build      # writes src/WebUI/templates/assets/tweb/app.js and app.css: commit them
```

The build is committed, because Composer and the phar ship it without running npm; CI (`tests/webui.sh`)
fails if it isn't the build of the committed sources (the build is deterministic).
