# MadelineProto

Async PHP MTProto client for Telegram (amphp v3 / Revolt), with voice, video, group and E2E conference
calls built on the danog/php-rtc stack (checkouts in `~/repos/php-rtc/php-rtc-*`). The main branch is `v8`
(remote `o` in the local clone). Reference sources: `~/repos/td` (TDLib/tde2e), `~/repos/tdesktop`,
`~/repos/coreCrawlerV2/Telegram-android`, `~/repos/psalm` (for CI/release conventions).

Procedures live in skills (`.claude/skills/`): live call tests, IPC debugging and live IPC tests, magnaluna
deploys and profiling on the pi, php-rtc releases, the MadelineProto release flow, CI user sessions.

## How to work

- **Root causes, not descriptions.** When explaining a fix, lead with the mechanism: which invariant was
  violated, by which code path, and whether the same flaw exists elsewhere. Describing the change or the
  symptom is not enough.
- **There is no flakiness.** An intermittent failure (CI or local, even if a rerun passes or the baseline
  fails too) is a bug: gather evidence (logs, repeated runs, timing, platform), find the mechanism, fix it.
  Never "rerun until green", never call something a pre-existing flake, never release on that basis.
- **No backward compatibility for unreleased work.** What's on `v8` is unreleased: change persisted formats
  freely, don't add migration or discard shims.
- **Skip psalm unless asked** (on MadelineProto: ~3 min, mostly pre-existing errors). For php-rtc packages,
  run psalm + phpunit locally per package before releasing.
- **Don't commit one-off scripts** that don't run in CI and only exist to create secrets: keep them outside
  the repository and have the user run them.
- **Push branches and open pull requests yourself**, without asking: against `v8`, with their `release:*` label.
  Never push to `v8` or `master`, never force-push someone else's branch. **Confirm the other outward-facing
  actions** (merging, tagging, releasing, deploying, messaging, repo settings) unless the user asked for that step.
- **Branch from a fresh `v8`**: fetch it (and merge it into the branch, rather than rebasing, before the first push).
  A push runs the ci.yml *of the pushed commit*: a branch cut from a v8 older than the push trigger's restriction to
  v8 and tags ran the secret jobs (test, build) on a branch push, competing for the CI sessions.
- **Pushing**: the `gh` token lacks the `workflow` scope, so HTTPS pushes of commits touching `.github/workflows/`
  are refused. Push those over SSH (in background jobs `SSH_AUTH_SOCK` is unset: use the live forwarded agent socket
  in `~/.ssh/agent/`, the one `ssh-add -l` accepts), or write just the workflow file with the GitHub connector on top
  of a branch pushed over HTTPS. Otherwise:
  `git -c credential.helper= -c 'credential.helper=!gh auth git-credential' push https://github.com/danog/MadelineProto.git HEAD:<branch>`.
- **Read a script fully before running it.** `tools/layerdiff.php` sends its diff to @danogentili through
  `fuzz_bot.madeline` unless given `--no-send`: never run it without the flag while testing.

## Sessions

- **Never run two copies of the same session at once** (copies share one auth key): responses go to the
  wrong process ("Still missing ... sending state request", "Seqno mismatch"), which looks like flakiness.
  Kill test workers between runs, check for strays, and only use *copies* of sessions in `/tmp`.
- Local sessions in the repo root: `fuzz_user.madeline` (user account), `fuzz_bot.madeline` (@danogmailbot,
  business mode), `fuzz_unauth.madeline`; `valerianic.madeline` is a second user account. Their IPC workers
  may be running: scope `pkill` to one session path (macOS resolves `/tmp` to `/private/tmp` in the worker's
  command line), never a bare `MadelineProto worker`.
- A plain `new API('x.madeline')` script is an IPC *client*: the worker's exceptions don't reach the
  client's output, so they look like hangs (see the debug-ipc-hang skill).
- **`fuzz_user.madeline` is @magicalcrazypony** (ID 783732906, production DCs), the account the live magnaluna bot
  runs on (magnaluna-deploy skill): even a copy of it is a second copy of the bot's session, never use it while the
  bot runs. To test magnaluna's commands live, message it from another account (a copy of `valerianic.madeline`),
  after checking `getSelf()` of the session: messages "to" the bot from its own session land in its Saved Messages.
- When live tests send messages, send them to the test account's own Saved Messages or to @danogentili
  (the user's account, Telegram ID 101374607), never to someone else's chat. Group/conference call invite
  links go to @danogentili as Telegram messages.

## Code invariants

- **No `foreach (array_keys($arr) as $k)`**: write `foreach ($arr as $k => $_)`; over two arrays combined (`$a + $b`,
  `array_merge()`), two separate foreaches. array_keys (and the union) copies them for nothing.
- **Exceptions are built where they're thrown**: a `reply(static fn () => ...)` closure (or any closure that builds an
  exception for a waiting fiber) constructs it inside, capturing only plain values, so that its trace is the one of
  the awaiting fiber. Rethrowing one that already exists (`static fn () => throw $e`) is fine.
- **Durations and deadlines use `hrtime(true)`, as int64 nanoseconds**: no floats (constants and properties holding
  them are suffixed with `Ns`), seconds only where an API takes them (`delay()`, `EventLoop::delay()`) or for
  printing. `microtime()` only for Unix times (Telegram's dates, ttls, livestream chunk timestamps) and what another
  process compares (`IpcState`). hrtime readings saved in the session mean nothing after a reboot: reset them on
  unserialize.
- **Secrets passed as parameters are `#[\SensitiveParameter]`** (API hash, tokens, codes, passwords, sessions, auth
  keys, crypto keys and seeds, proxy settings, stream keys), so that they never reach stack traces, logs or reports.
  `tools/AnnotationsBuilder.php` copies the attribute into the `API` wrappers (InternalDoc.php).
- **The IPC startup redundancy is deliberate** (webhost compatibility): `WebRunner::selfStart` sends the self-restart
  request to every combination of address, port and protocol (even plain HTTP to 443), without stopping at the first
  that accepts, and `AbstractServer::startMe` starts the ProcessRunner *and* the WebRunner together (on some hosts
  the worker proc_open starts can't serve, and only the web runner works). To fix restart floods, bound how often
  restarts happen (no restart while a server listens, backoff, give up), never what a single startup tries.
- **Autogenerated, never hand-edit** (run `composer docs` instead, which also downloads the TL schema):
  `src/InternalDoc.php`, `src/Namespace/*.php`, `src/RPCError/*.php`, `src/RPCErrorException.php`,
  `src/EventHandler/SimpleFilters.php`, `src/Lang.php`.
- `Tools::getTimeoutCancellation` uses a *referenced* timer on purpose: some callers await futures that only
  complete through the timeout, and an unreferenced timer lets Revolt end the loop under the suspended
  fiber. To stop a lingering timer, cancel it when the cancellation is dropped
  (`src/ReferencedTimeoutCancellation.php`); never unreference it. Check who relies on a watcher keeping the
  loop alive before unreferencing anything.
- amp assumes `Cancellation::subscribe/isRequested/throwIfRequested` never suspend: IPC proxies must not do
  blocking round trips there.
- A connection whose `msgs_state_req` goes unanswered is dead (half-open TCP): it's reconnected
  (`Loop/Connection/WriteLoop.php`, regression test `DeadConnectionTest`).
- On the test servers MadelineProto numbers the DCs 10001-10003 (an exported authorization says 10002).

## Calls (tgcalls / php-rtc)

- **1:1 channel model** (verified against tgcalls and tdesktop): each party offers only its outgoing
  channels (own SSRC and payload types) and the other echoes them; a peer creates its outgoing channels only
  from our answer and can't re-offer while its offer is unanswered; one incoming video channel (the last
  video content wins); a screen share replaces the camera channel, flagged only via
  MediaState.screencastState. `Tgcalls/Controller` mirrors this with send-only and receive-only
  transceivers: keep one outgoing video at a time, answer every peer offer, never let a peer offer touch our
  send m-lines, and only echo payload types php-rtc can receive (the H.264 profile matters).
- **SRTP key direction** follows the DTLS role, not the ICE role (RFC 5764: a DTLS server sends with the
  server key). Mass `SRTP authentication failed!` after a completed handshake means swapped keys. Group
  calls are ICE controlling + DTLS server.
- **E2E conferences** (`src/Tgcalls/E2E`) must match TDLib's tde2e, not the API docs: td's TL ids
  (groupParticipant 18f3971f, changeSetValue fe0139cc); the kv_hash of a call that never sets values is
  sha256("\0\0\0\0"); a block with a group-state change omits group_state and shared_key from the proof;
  the group key is HMAC-derived only when min(version) >= 1; frames are encrypted whole before
  packetization, everything on channel 0 with one shared seqno counter, Opus frames carry a trailing
  0x01 + audio-level byte, H.264 keeps SPS/PPS/SEI and the slice header up to the PPS id in the clear (with
  4-byte start codes, re-encrypting if the ciphertext contains one), VP8 keeps 10 bytes (keyframe) or 1.
  Cross-check every change with the tde2e harness (live-call-test skill): self-consistent unit tests can't
  catch interop bugs.
- **transport-cc**: we send transport-wide sequence numbers (the DTLS transport numbers the packets of all
  its senders; the SFU answers with feedback, which we ignore). Receive-side feedback is not implemented
  and won't be: transport-cc is stripped from both offered and answered 1:1 contents
  (`V2Sdp::withoutTransportCc()`), or Telegram Android throttles to 320x180 waiting for feedback.
- Playback (`Loop/VoIP/DjLoop`, `Tgcalls/*PlaybackTrack`) must read each track's next frame before it's
  due whatever the lookahead, wake the tracks when frames are queued (no polling), and resynchronize instead
  of bursting when more than a second behind. Tracks push frames to their sink (`MediaStreamTrackSink`, the
  RTP sender, held weakly); only `RemoteStreamTrack` keeps a queue, for the consumers of received media.
- magnaluna (@magicalcrazypony) must never download a file or URL in order to play it: stream Telegram
  files with `$media->getStream()` and pass URLs as `RemoteUrl`.

## CI and releases (GitHub Actions)

- The logic lives in scripts (`tests/test.sh <mode>`, `tests/makephar.sh`, `tests/*.sh|php`); the YAML
  stays thin, so the CI can move provider again. Jobs run in `danog/madelineproto:next`, built in `ci.yml`
  (v8/tag pushes) before the jobs that run in it; the first step installs git and trusts the workspace.
- Pushes run the full suite with the secrets; pull requests only run what needs no secret (cs,
  phpunit-light, handshake). Every PR needs a `release:{fix,feature,layer,internal}` label and bumps
  `RELEASE` in `src/API.php` itself (`tests/release_check.sh`): feature/layer → next minor of the last tag,
  fix → next patch, internal → none unless it changes what ships (anything but CI/tests/tools/docs paths:
  `ships` in `tests/release_version.sh`), then next patch. An unreleased bump already on v8 counts.
- **All CI user accounts are on Telegram's test servers** (the same as production, just newer), and so are
  the bots they interact with (`TEST_BOT_TOKEN` for the fuzzer). The production `BOT_TOKEN`
  (@danogmailbot) is only used without user accounts. `99966XYYYY` test numbers don't work: CI accounts are
  real phone numbers logged in on the test servers (ci-user-session skill).
- The daily build (`autobuild.yml`) and the docker image update (automerged) open PRs with the "MadelineProto autobuild"
  GitHub App, which rulesets ("Default branch: admins only") keep off `v8` and `master`. The docs and
  schemas repositories only publish from `master`.
- After a v8 push passes CI, `tests/prerelease.sh` tags `RELEASE` if it isn't released yet; see the
  release-flow skill.
- **Tests download nothing per run**: what only the tests use (e.g. `mtg` for MtProxyTest) goes into the test image,
  `tests/dockerfiles/Dockerfile.test` (built on the published image as `<tag>-test` by tests/docker_image.sh), never
  into the published image: a GitHub 504 during a download failed the v8 CI and blocked a release.
- **Generated docs**: `tools/build_docs.php` (what `composer docs` runs) starts with `git checkout src/InternalDoc.php`,
  so the README's method index reflects the *committed* InternalDoc.php: commit the regenerated InternalDoc.php, then
  build again for the README. The `cs` job checks the README against the docs repository's master (so merging a docs
  pull request that adds a section changes the README of every open pull request). Leave the RPC error database and
  `Lang.php` it also refreshes to the daily build.
- **The web UI bundle** (`src/WebUI/templates/assets/tweb/`) must be rebuilt (`npm ci && npm run build` in `webui/`)
  when its dependencies change: a Dependabot bump of vite merged with its `webui` check failing broke the v8 CI.
