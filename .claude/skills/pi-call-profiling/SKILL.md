---
name: pi-call-profiling
description: Profile and verify outgoing call media (CPU, event-loop lag, frame pacing/stutter) on the pi with every group call of an account joined, using a copy of a local session; plus the local playback pacing harness.
---

# Profiling calls on the pi

Use a copy of the local `fuzz_user.madeline`, never the session of the running magnaluna bot (check that
no local worker holds fuzz_user while the copy runs: never two copies of a session).

## Setup (in a work dir on the pi, e.g. /tmp/prof)

- `cp -a /media/storage/persist/magnaluna/vendor` once per variant (base/new), and overlay local `src`
  directories into the copies with `tar -czf - src | ssh pi 'tar -C <vendor>/danog/<package> -xzf -'`.
- magna.php that autojoins every group call: `git -C examples/magnaluna show 895900b:magna.php`.
- The session copy: `tar -czf - fuzz_user.madeline | ssh pi 'tar -C /tmp/prof -xzf -'`, renamed to
  `magna.madeline`.
- Run in the image `madelineproto:php85-test` (built on the pi, has sodium), with an `excimer.so` built in
  that image (`pecl install excimer`; EXCIMER_CPU only exists on Linux). A wrapper profile.php starts an
  `ExcimerProfiler` (1 ms CPU samples, flushed every minute as collapsed stacks), a 50 ms timer measuring
  loop lag, RSS/CPU every 10 s, then `require magna.php`; run it with `timeout -s TERM 300..480`.
- Files created by the container are root-owned: delete the work dir with `docker run ... rm -rf`.

## Metrics

- **Frame lateness** (release time − due time, from a temporary patch in the copied vendors in
  `VideoPlaybackTrack::produce` / `OpusPlaybackTrack::receiveWebm`) is the stutter metric; summarize with
  `late.py` (in this skill). Telegram's SFU RTCP receiver reports always say ~0 jitter: useless.
- CPU: aggregate the collapsed stacks by self/inclusive time over the steady-state minutes.
- The 30 s session save stalls the loop ~100-150 ms.

## Afterwards

Leave every group call joined by the copy (a script using magnaluna 17ce9cc's magna.php with startAndLoop
replaced by `new API` + `getAllGroupCalls()` → `->leave()`), then delete the work dir and session copy.
Joining every group repeatedly in one evening hits FLOOD_WAIT on phone.joinGroupCall.

## Local pacing harness

`php .claude/skills/pi-call-profiling/measure.php <file> <lookahead ms> <seconds> [busy ms per 10 ms]`
plays a file through DjLoop and both playback tracks (attached to sinks — tracks hold them weakly, keep a
reference) and prints lateness percentiles. Healthy: p99 under ~10 ms, and the same frame count as the
file's duration.
