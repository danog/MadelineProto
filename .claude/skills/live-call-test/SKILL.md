---
name: live-call-test
description: Verify 1:1, group or E2E conference call changes live with the user's phone (tests/manual_call_features.php), and cross-check E2E conference changes against TDLib's tde2e.
---

# Live call tests

Only the user can answer and join calls: they use their second account, @danogentili (Telegram Android,
tgcalls protocol 8.0.0, H.264 High profile first in its offers), or tdesktop.

## Running

- 1:1: `php tests/manual_call_features.php 1to1 @danogentili <audio|video|screencast> a.webm fuzz_user.madeline`
  — the user answers on the phone and hangs up to end the call.
- Group: `php tests/manual_call_features.php group <chat id> video a.webm fuzz_user.madeline` — the user
  joins the video chat and judges what MadelineProto plays. The user's test group was -5244696618 (Sept
  2026), a private basic group: it has no invite link, so a notice is sent instead.
- Conference: `php tests/manual_call_features.php conference video a.webm fuzz_user.madeline` prints a
  t.me/call link; `conference-join <link> video a.webm <session>` joins by link (or re-attaches, restarting
  the transmission if idle, when the session is already in it).
- Run it in the background under `timeout` (Ctrl-C only detaches), with `CALL_TEST_LOG=/tmp/x.log` for a
  VERBOSE log (the default CLI logger only echoes) and `CALL_TEST_NOTIFY=@danogentili`, which sends the
  group/conference invite links via Telegram (always do: the user joins from the phone). `MP_RTC_DEBUG=1`
  adds php-rtc's debug logs.
- Recordings go to `incoming_1to1_<mode>/` and `conference_recordings_<mode>/`: verify them with ffprobe.

## Reading the log

The logger truncates the file at 1 MB: grep early, or dedupe with `sed -E 's/[0-9]{3,}/N/g' | sort | uniq -c`.
Look for: "offered a new", "accepted our", "Routing the incoming video track", "Recording incoming video
track", "SRTP decryption failed", "Colibri message". Mass SRTP authentication failures after a completed
DTLS handshake mean swapped SRTP keys (they follow the DTLS role).

## Gotchas

- The script runs the call inside the session's IPC worker ("MadelineProto worker <session>"), which
  outlives the script: stop it (SIGTERM, never SIGKILL) to load new code (the call resumes). A call persisted by a killed process is
  re-attached on the next run ("Already joined — attaching") and only re-transmits if nothing is playing; a
  revived group call connection only fails after ~30 s of consent checks before the controller re-joins.
- While the script holds the session, no other process can use it over IPC. Any extra IPC client persists
  *its* logger settings into the session, diverting the worker's logs: give such clients the same
  `FILE_LOGGER` path as `CALL_TEST_LOG`. To leave a group call or conference, use a small IPC client calling
  `->leave()`.
- Loading a copy of `fuzz_user.madeline` re-attaches its persisted group calls.

## E2E conference interop (src/Tgcalls/E2E)

Self-consistent unit tests can't catch interop bugs: cross-validate against td.
- `/tmp/tde2e_harness/harness` (source `harness.cpp`, built against `~/repos/td/build/tde2e/libtde2e.a`) is
  a REPL around tde2e (genkey/zero/selfadd/validate/callcreate/apply/encrypt/decrypt/pull/inbound/vstate);
  `/tmp/e2e_cross.php` drives it from PHP and must stay all-PASS after any change. Rebuild them from
  ~/repos/td if /tmp was wiped.
- tdesktop's call log: `~/Library/Application Support/Telegram Desktop/DebugLogs/last_group_call_log.txt`
  ("Failed parsing payload" = descriptors encrypted).
