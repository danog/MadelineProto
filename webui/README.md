# MadelineProto web UI

The React app behind the web pages of MadelineProto: the API ID setup (manual, or automatic through
my.telegram.org), the login (phone, bot token, QR code, 2FA, signup) and the status pages.

## How it fits together

- The PHP side builds each page as a view model (`src/WebUI`: `Page`, `Field`, `Alert`, `Action`, `Step`) and
  renders it with native PHP templates (`src/WebUI/templates`, see `TemplateEngine`): the markup works
  without JavaScript, and the page also embeds the view model as JSON (`#madeline-state`).
- This app (built into `src/WebUI/templates/assets/app.js`, inlined in the pages) renders the page again from
  that state, picking the view named by `Page::$view` from the view registry (`src/views/registry.ts`). New
  surfaces register new views; forms are plain POSTs to the script, handled the same way with or without
  JavaScript.
- The stylesheet, `src/WebUI/templates/assets/madeline.css`, is shared by both renderings.
- Users customize the pages by overriding single templates from a directory of theirs:
  `$settings->getTemplates()->addDirectory(__DIR__.'/templates')` (a `templates/assets/app.js` there replaces
  the app too).

## Development

```sh
npm ci
npm run dev        # sample pages from src/fixtures.ts: /?page=login|password|manual|done
npm test
npm run build      # writes src/WebUI/templates/assets/app.js: commit it
```

The bundle is committed, because Composer and the phar ship it without running npm; CI (`tests/webui.sh`)
fails if it isn't the build of the committed sources.
