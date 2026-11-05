---
name: php-rtc-release
description: Test, release and roll out changes to the danog/php-rtc packages (~/repos/php-rtc/php-rtc-*) into MadelineProto.
---

# Releasing php-rtc packages

Each package (`~/repos/php-rtc/php-rtc-<name>`, remote `origin`, branch `master`) has its own GitHub
Actions CI, with no tracked composer.lock: CI installs the latest *tags* from packagist.

## Before pushing

- Run phpunit and psalm in the package (`vendor/bin/phpunit`, `vendor/bin/psalm --no-cache`). If the change
  depends on unreleased changes of another package, rsync that package's `src/` into this package's
  vendor to test (and into MadelineProto's vendor), then restore with composer once released.
- CI's psalm has flagged things a local run didn't (e.g. float*int InvalidOperand): write `1000.0`-style
  literals and watch CI after pushing.
- Windows ICE tests pin host candidates to 127.0.0.1; tests needing two paths use `['127.0.0.1', '[::1]']`.
  PHPUnit 13.4 deprecates `executionOrder="depends"`: use `executionOrder="defects"` +
  `resolveDependencies="true"` in phpunit.xml *and* phpunit-skiptolerant.xml.
- Tests that hard-code another package's data (e.g. RTCRtpReceiverTest's header extensions) break when
  that package is released: update them in the same round.

## Releasing

- Find the real latest tag with `git tag -l '[0-9]*' --sort=-v:refname | head -1` (old `v1.0.x` tags sort
  wrongly otherwise); bump the patch version unless asked otherwise.
- Release in dependency order (e.g. mixin → stun → turn → ice → rtcp/codecs → rtp → dtls → webrtc), bumping
  the dependents' composer.json constraints when they need the new version (an interface they implement,
  say), and wait for packagist between dependents:
  `curl -s https://repo.packagist.org/p2/danog/php-rtc-<name>.json | grep -q '"version":"<v>"'`.
- `git push -q origin master && git tag <v> && git push -q origin <v>`, then watch
  `gh run list -R danog/php-rtc-<name> -L 1`. A red CI is a bug to fix, never a flake to rerun.
- In MadelineProto: bump composer.json's constraints, `composer update 'danog/php-rtc-*'` (the local lock
  may need all of them at once), rerun the call tests (PlaybackLoopTest, ResumablePlaybackTest,
  GroupVideoCodecTest, TgcallsSignalingTest, E2EConferenceTest, GroupSdpTest), commit and push v8.

## Notes

- amphp `Queue::push()` blocks until consumed: never call it from a stop/close path.
- Removing public API of a base class (e.g. `MediaStreamTrack::getConsumer()`) is still released as a
  patch version when only MadelineProto uses it, if the user agrees.
