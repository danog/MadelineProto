---
name: live-ipc-tests
description: Run MadelineProto's live test suites (resumable streams/uploads, restart cycles, event handler startup, secret chat and call resume, dead connections) locally with copies of the local sessions.
---

# Live test suites, locally

Offline suites: `ResumablePlaybackTest`, `PlaybackLoopTest`, `IgeStateTest`, `GroupVideoCodecTest`, ...
Live suites (need secrets, skip otherwise): `ResumableStreamTest` (IPC client on `testing.madeline`,
API_ID, API_HASH, BOT_TOKEN, DEST), `ResumeCycleTest` (in-process children via tests/resume_cycle_child.php,
`RESUME_CYCLE_SESSION`), `EventHandlerStartTest`, `DeadConnectionTest` (API_ID/API_HASH only; ~40 s), and
the two-account `SecretChatResumeTest` / `CallResumeTest` (`USER_SESSION`, `PEER_USER_SESSION`).

## Setup

Locally only DEST is exported. Use *copies* of the sessions, never the originals:
- Copy `fuzz_user.madeline` to `/tmp/rt/testing.madeline` (it's a user, but `botLogin()` is a no-op on a
  logged-in session; RESUME_CYCLE_SESSION / USER_SESSION can be copies too). PEER_USER_SESSION: a copy of
  `valerianic.madeline` (user @ValerianicV). valerianic stores the `SecretHandler` event handler of
  examples/secret_bot.php: load the copy once in-process with a stub
  `class SecretHandler extends SimpleEventHandler {}` (in a file starting with
  `declare(strict_types=1);`) and call `unsetEventHandler()`, otherwise it fails static analysis and
  reports to @danogentili.
- API id/hash: read them from the copy (after `Magic::start()`, `SessionPaths::unserialize()`, find the
  Settings object, `getAppInfo()`), or ask the running worker over IPC
  (`$API->getSettings()->getAppInfo()`). Don't write them anywhere persistent.
- Run: `cd /tmp/rt && env API_ID=.. API_HASH=.. BOT_TOKEN=unused DEST=<own id> php <repo>/vendor/bin/phpunit -c <repo>/phpunit.xml <repo>/tests/danog/MadelineProto/<Test>.php`.
- The tests really send messages to DEST: set it to the test account's own ID (Saved Messages), never to
  someone else's chat.
- Delete /tmp/rt afterwards (auth keys).

## Rules

- Never run two copies of the same session at once (testing/cycle/requester copies of fuzz_user share one
  auth key): responses go to the wrong process, which looks like flakiness. Kill every test worker between
  suites and check for strays.
- `pkill` with `/tmp/...` misses workers on macOS (their command line says `/private/tmp/...`) and the old
  worker keeps serving old code; wait ~5 s after killing before the next run.
- The file logger truncates at `maxSize` (1 MB by default; MadelineTestCase sets 100 MB): log-based
  assertions are meaningless below that.
- IPC bugs are timing-dependent: run 3+ times and check `tests/MadelineProto.log` for "Reporting: Surfaced"
  and "Unhandled future". An intermittent failure is a bug to root-cause.
