---
name: ci-user-session
description: Create or replace the user sessions of MadelineProto's CI (USER_SESSION_AUTH, PEER_USER_SESSION_AUTH) — accounts logged in on Telegram's test servers, stored as exported authorizations.
---

# CI user sessions

All CI user accounts are on Telegram's test servers (and so are the bots they interact with). A session
directory exceeds GitHub's 48 KB secret limit, so the secret holds only the exported authorization,
`<DC>:<base64 auth key>` (`API::exportAuthorization()`), which CI turns into a session with
`php tests/ci_sessions.php --test user <session> <VARIABLE>` (`Conversion::importAuthorization()`; test DCs
are numbered 10001-10003 inside MadelineProto).

- `USER_SESSION_AUTH`: used by the full test suite and the fuzzer of the daily build.
- `PEER_USER_SESSION_AUTH`: a second, different account, for SecretChatResumeTest and CallResumeTest
  (skipped without it).
- `BOT_SESSION_AUTH`: the bot of `BOT_TOKEN` (@danogmailbot), on the **main** servers, used by the full test
  suite and the phar build: 10 authorizations, one per line (`php tests/ci_sessions.php bot <session>
  BOT_SESSION_AUTH <n>`; 0 for the phar build, the others for the lanes of tests/session_tests.sh, each process
  using a session at once needs its own). The script resumes from `~/.cache/madeline-ci-bot-sessions` after a
  flood wait (a few logins per hour). Logging the bot in with
  its token creates a new authorization every time: done by every CI run, it got `FLOOD_WAIT`s on
  `auth.importBotAuthorization` (the 8.8.9 phar build failed this way). `BOT_TOKEN` stays, for the bot API only.
  Create it with `ci_bot_session.php` (next to this skill in the local checkout, never committed: setup-only
  scripts stay out of the repository), run by the user in a terminal: it asks for API_ID,
  API_HASH and the token (not echoed, so they don't end up in the conversation; `--token-file <file>` also
  works). It logs in once with a new authorization (never export the one of a local session such
  as fuzz_bot.madeline: two users of one session), checks it like CI, stores it and deletes everything.

## Creating one

`99966XYYYY` test numbers don't work: the account is a real phone number logged in on the test servers.
`ci_session.php` (next to this skill in the local checkout, never committed: setup-only scripts stay out of the
repository) does it step by
step, so the user can run it through `!` in the conversation:

```
! php .claude/skills/ci-user-session/ci_session.php USER_SESSION_AUTH --phone +<number>
! php .claude/skills/ci-user-session/ci_session.php USER_SESSION_AUTH --code <code>
! php .claude/skills/ci-user-session/ci_session.php USER_SESSION_AUTH --password <password>   # if asked
```

It needs API_ID/API_HASH in the environment (any app works on the test servers; it falls back to
/tmp/pacing/appinfo.env). Once logged in, it exports the authorization, stops the login session, checks in
a separate process that a fresh session logs in with it (as CI does), stores it with
`gh secret set <SECRET> --repo danog/MadelineProto` (through stdin) and deletes every local copy: CI must be
the only one using the session. What the user types after `!` shows up in the conversation.
