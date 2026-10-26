---
name: release-flow
description: How MadelineProto releases work on GitHub Actions — PR release labels, the release draft, publishing, the phar build, generated notes with the layer diff, and the Telegram changelog; and how to test the release scripts safely.
---

# MadelineProto releases

## The flow

1. Every PR carries one of `release:fix`, `release:feature`, `release:layer`, `release:internal`
   (`pr-labels.yml`; `.github/release.yml` makes them the sections of GitHub's generated notes), and bumps the
   `RELEASE` constant of `src/API.php` itself as the label requires (`tests/release_check.sh`, job `version` of
   `pr-labels.yml`, run on the merge commit): feature/layer → at least the next minor of the last tag, fix → the
   next patch, internal → nothing, unless it changes files that ship (`ships` in `tests/release_version.sh`: all but
   CI, tests, tools and docs paths), then the next patch. An unreleased bump already on v8 counts.
2. The daily build (`tests/autobuild.sh`) labels layer changes `release:layer` + `release:feature` and bumps the
   minor version; its internal builds bump the patch version when they change `src/`. `tests/update_docker.sh` opens a `docker-php` PR (internal, patch bump), merged by `automerge.yml`
   (`tests/automerge.sh`, also Dependabot's) once all checks passed and only if the diff changes nothing but versions.
   Dependabot's PRs are exempt from the version check (automerged only if all commits are Dependabot's); once one is
   merged, `dependabot-release.yml` (`tests/dependabot_release.sh`) pushes the bump it needs to v8 by the same rules.
3. `prerelease.yml` (`tests/prerelease.sh`): once the CI of a v8 push passed, if `RELEASE` isn't tagged yet, it tags
   it and creates the pre-release (using a hand-written draft of the same version for title/notes). No bump commit.
4. The tag push builds and tests the phar (`tests/makephar.sh` checks `RELEASE` against the tag); once all of them
   passed, the release job (`tests/release_phar.sh`) uploads it, makes the release final, and dispatches
   release.yml, which sends the phar to @madelineprotophar.
5. `release.yml`: on `published`, `tests/release_notes.sh` appends the generated notes (and, below a
   `<!-- layer-diff -->` marker, `tools/layerdiff.php --no-send` between the layers of the previous and new
   release); once final, `tests/announce_release.php` sends the changelog without the layer diff to
   @danogentili through the Bot API (PHAR_BOT_TOKEN, the bot of the phars), marking the release `<!-- announced -->` (an empty changelog, internal changes only, is only marked). release_phar.sh makes
   the release final with the job token, whose events don't run workflows, so the release job dispatches
   release.yml itself. Both steps run only once per release (markers in the body).

## Testing the scripts

- `DRY_RUN=1` on `tests/release_notes.sh <tag>`, `tests/prerelease.sh` and
  `tests/announce_release.php <tag>` prints instead of writing anything (`GITHUB_TOKEN=$(gh auth token)`).
  The prerelease script needs a checkout whose remote is `origin`: use a blobless clone in /tmp
  (`git clone --filter=blob:none`) with the new scripts copied in and a symlink to the local vendor.
- `releases/tags/<tag>` doesn't return drafts (404).
- **Never run `tools/layerdiff.php` without `--no-send`**: it messages @danogentili with fuzz_bot.madeline.

## Telegram formatting (announcements)

Release markdown is converted with danog/telegram-entities: drop GitHub's backslash escapes before
re-escaping, keep only links (bare URLs too), bold and code, headings → bold, bullets → "•"; split under
4096 characters as Telegram counts them. If sending through MadelineProto's markdown parse mode instead:
bold is a single `*` (GitHub's `**` silently produces no entity), a link overlapping code loses the link,
and editing with unchanged content throws MESSAGE_NOT_MODIFIED.
