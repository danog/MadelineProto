---
name: magnaluna-deploy
description: Deploy MadelineProto or magnaluna changes to the magnaluna bot (@magicalcrazypony) running in docker on the pi (this machine), roll a MadelineProto release out to the other bots there, and read their logs.
---

# Deploying magnaluna on the pi

The pi is **this machine** (`daniil-nas`, 192.168.69.4): run everything locally, never `ssh pi`.

magnaluna runs in the docker container `homedaniilit-magnaluna-1` (image `danog/madelineproto:next`, with
FFI + ffmpeg; compose file `~/repos/home.daniil.it/docker-compose-daniil-nas.yml`), command
`php /app/magna.php`. `/app` is bind-mounted from `/media/storage/persist/magnaluna`, a git checkout of
danog/magnaluna with **uncommitted pi-only changes** — keep them, never commit or copy them (composer.json
requires `danog/madelineproto: v8.x-dev`; magna.php adds the database settings, prometheus metrics and
logger level 4).

## Which MadelineProto it runs

magna.php loads `vendor/autoload.php` if it exists, else `madeline.php`. Normally there is **no
`vendor/`**: the bot runs the latest *released* phar (`/app/madeline-X.Y.Z.phar`; the log's "Revision:"
line and every trace say which), which `madeline.php` fetches when the process starts. So:

- **Released fixes** (the usual case): just restart the container (below), then check that the new
  process loaded the new phar (`grep -oE "madeline-8\.[0-9.]+phar" | sort | uniq -c` on its log).
- **Unreleased v8 fixes**: push them to v8, wait for packagist, then install the composer dependencies so
  `vendor/` exists. Stop the bot first, so it doesn't autoload a mix of old and new files:
  `docker stop homedaniilit-magnaluna-1`, then
  `docker run --rm --user $(id -u):$(id -g) -e COMPOSER_HOME=/tmp/composer -v /media/storage/persist/magnaluna:/app -w /app danog/madelineproto:next composer update danog/madelineproto --with-dependencies --no-interaction`
  (`--with-dependencies` to pick up new php-rtc releases), then `docker start homedaniilit-magnaluna-1`.
  To go back to the released phar once the fix is released, stop the bot and remove `vendor/` **and
  `madeline.version`**: that file still names the phar loaded before (`madeline.php` loads it without checking for a
  newer one), which can't load a session the newer code saved (e.g. `Class 'danog\MadelineProto\RtmpServers' does
  not exist`): the bot crash-loops.

## Every bot loads the phar

All the MadelineProto bots on the pi (magnaluna, dump, backup, the stories bots in php-fpm) load MadelineProto
through `madeline.php`, the self-updating phar, never a composer `vendor/`. The only exception is fantasiaNew
(`/www/fantasia`, composer, `vendor/` owned by root). "Make sure the bots run X" means *at least* X, the latest
release. If a bot directory has a `vendor/autoload.php` its script prefers, move it aside.

## Rolling a release out

1. Wait until `curl -fsSL https://phar.madelineproto.xyz/release81` reports the new version: before, the bots just
   load the old phar again.
2. Restart magnaluna and dump (`docker restart homedaniilit-magnaluna-1 homedaniilit-dump-1`). `madeline.php`
   downloads the phar of `releases/latest`, retrying for 10 seconds: right after a release GitHub may still serve the
   old asset, and the bot falls back to the old phar. Check the *new* process's banner (`MadelineProto X.Y.Z`, after
   the restart), and restart again if needed.
3. stories (`homedaniilit-stories-1`, php-fpm): its `madeline.version` pins the phar until the running process'
   garbage collector deletes it: delete it (`docker exec homedaniilit-stories-1 rm /www/stories/madeline.version`),
   restart the container (opcache doesn't revalidate), and kill its stale IPC worker
   (`MadelineProto worker /www/stories/stories_user.madeline`) if any: it starts again on the next request.
4. fantasia: `docker exec -w /www/fantasia -e COMPOSER_HOME=/tmp/composer homedaniilit-fantasia-1 composer update
   danog/madelineproto --with-dependencies --no-interaction`, then restart the container.

## Restarting

1. If the magnaluna checkout is behind: `git fetch && git status -sb`, then
   `git stash && git pull --ff-only && git stash pop` (resolve conflicts with the pi-only lines, e.g. a
   `use function ...` line clashing with new `use` lines, then `git reset` + `git stash drop`).
2. `docker restart homedaniilit-magnaluna-1`, wait a minute, check `docker ps` and the log since the
   restart. One SIGTERM UncaughtThrowable from the old process is expected; it should resume its calls
   ("Resumed group call ... after a restart of the process").

## Logs

- `docker logs -t --since 2026-10-19T12:09:10Z homedaniilit-magnaluna-1`: pass UTC timestamps **with a
  trailing `Z`**; docker reads a zone-less `--since` as local time (CEST), two hours off.
- The json-file log keeps only 3 × 1 MB (`max-size: 1m`), about 50 minutes at logger level 4: anything
  older is gone, so the first occurrence of an error in the log is not necessarily when it started.
- The bulk is per-frame noise ("Releasing camera frame", "Got the chunk", "Got the segment"): filter it
  out and group errors with `sed -E "s/[0-9]+/N/g" | sort | uniq -c | sort -rn`.
- To query the running bot's state, a read-only script inside the container that includes `madeline.php`
  and opens `new API('/app/magna.madeline')` is an IPC client of the running process (the session lock is
  held, so it doesn't start another copy): `docker cp` it in, `docker exec ... php`, delete it afterwards.

The user also redeploys magnaluna themselves: a recent restart with exit 0 and restart count 0 is them,
not a crash. Never run another copy of the bot's session; for profiling use a copy of a *local* session
(pi-call-profiling skill).

magnaluna streams to RTMP servers (`/play <RTMP URL><key> <file>`) only through `RtmpServers::Telegram`, for
everyone, admins included: never anywhere else (into the pi's network, say). By default (`SELF_STREAM`) it keeps a
live story on its own profile, looping the default video when nothing else is played.

Screen sessions and every Claude in them dying together is systemd-oomd, killing the whole SSH session scope when
RAM and swap are both over 90% (`journalctl -u systemd-oomd | grep Marked`), not a crash: magnaluna takes ~850 MB.

magnaluna must never download a file or URL in order to play it: stream Telegram files with
`$media->getStream()` (DjLoop snapshots resumable streams, so they loop and survive restarts) and pass URLs
as `RemoteUrl`.
