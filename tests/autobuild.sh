#!/bin/bash -e

# Run composer build (usually, to pick up a new layer of the schema), and submit what changed as pull
# requests to danog/schemas, danog/MadelineProtoDocs and danog/MadelineProto, with the bundle of the web UI
# that .github/workflows/autobuild.yml rebuilt (tests/webui.sh --update) in a job without secrets.
#
# Run by tests/test.sh build, once it set up the tools and the dependencies. Needs:
# - GITHUB_TOKEN, able to push branches to the three repositories and to open pull requests (but not to
#   push to their default branches, the pull requests are reviewed),
# - API_ID, API_HASH, USER_SESSION_AUTH (see tests/ci_sessions.php) and TEST_BOT_TOKEN (a bot with
#   business mode enabled), for the fuzzer: both on the test servers of Telegram,
# - GIT_NAME and GIT_EMAIL, the author of the commits, and RUN_URL, linked by the pull requests.

BRANCH=autobuild
export GH_TOKEN="$GITHUB_TOKEN"

git config --global --add safe.directory '*'
git config --global user.name "${GIT_NAME:-MadelineProto autobuild}"
git config --global user.email "${GIT_EMAIL:-autobuild@madelineproto.xyz}"
gh auth setup-git

# Build on top of the latest docs and schemas.
git submodule update --init docs schemas
for submodule in docs schemas; do
    git -C $submodule fetch -q origin
    git -C $submodule checkout -q -B master origin/master
done

# The user accounts of the CI, and the bots they interact with, are on the test servers.
php tests/ci_sessions.php --test unauth fuzz_unauth.madeline
php tests/ci_sessions.php --test bot-token fuzz_bot.madeline TEST_BOT_TOKEN
php tests/ci_sessions.php --test user fuzz_user.madeline USER_SESSION_AUTH

composer build

source tests/submit_pr.sh

layer=$(grep -o 'layer = [0-9]*' src/Settings/TLSchema.php | grep -o '[0-9]*')
title="Automatic build (layer $layer)"
# The release labels of the pull request of MadelineProto (see .github/release.yml): a new layer is a feature.
labels=release:internal
if ! git show HEAD:src/Settings/TLSchema.php | grep -q "layer = $layer\b"; then
    labels=release:layer,release:feature
fi

# The pull requests of this build, and their bodies, to link them to each other once all of them are submitted.
urls=()
bodies=()

for submodule in schemas:danog/schemas docs:danog/MadelineProtoDocs; do
    dir=${submodule%%:*}
    repository=${submodule#*:}
    git -C "$dir" add -A
    if git -C "$dir" diff --cached --quiet; then
        echo "Nothing changed in $repository"
        continue
    fi
    body="The hourly \`composer build\` of MadelineProto: the pull request of danog/MadelineProto points to this one."
    submit_pr "$dir" "$repository" "$BRANCH" "$title" "$body"
    if [ -n "$PR_URL" ]; then
        urls+=("$PR_URL")
        bodies+=("$body")
    fi
done
submodules=${#urls[@]}

# Only the generated files, besides the ones that were already there: not what the CI left around.
git add -u
git add -A src
# Released (see tests/release_check.sh): a new layer as a minor version, other changes that ship (the generated code,
# the web UI) as a patch one.
source tests/release_version.sh
if [ "$labels" != release:internal ] || git diff --cached --name-only | ships; then
    echo "RELEASE: $(bump_release_constant ${labels//,/ } release:fix)"
    git add src/API.php
fi
if git diff --cached --quiet; then
    echo "Nothing changed in danog/MadelineProto"
else
    body="The hourly \`composer build\`, and build of the web UI."
    if [ "$submodules" -ne 0 ]; then
        body="$body

It points to the commits of the other pull requests of this build. Merge them first, with a merge commit (not a squash or a rebase), or the commits this points to would be lost."
    fi
    submit_pr . danog/MadelineProto "$BRANCH" "$title" "$body" "$labels"
    if [ -n "$PR_URL" ]; then
        urls+=("$PR_URL")
        bodies+=("$body")
    fi
fi

if [ ${#urls[@]} -gt 1 ]; then
    for i in "${!urls[@]}"; do
        others=""
        for j in "${!urls[@]}"; do
            if [ "$i" -ne "$j" ]; then
                others="$others
- ${urls[$j]}"
            fi
        done
        gh pr edit "${urls[$i]}" --body "$(pr_body "${bodies[$i]}

The other pull requests of this build:$others")"
    done
fi
