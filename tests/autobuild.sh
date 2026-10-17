#!/bin/bash -e

# Run composer build (usually, to pick up a new layer of the schema), and submit what changed as pull
# requests to danog/schemas, danog/MadelineProtoDocs and danog/MadelineProto.
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
php tests/ci_sessions.php --test bot fuzz_bot.madeline TEST_BOT_TOKEN
php tests/ci_sessions.php --test user fuzz_user.madeline USER_SESSION_AUTH

composer build

layer=$(grep -o 'layer = [0-9]*' src/Settings/TLSchema.php | grep -o '[0-9]*')
title="Automatic build (layer $layer)"

# Commit the staged changes of a repository to the autobuild branch, opening a pull request if there's none.
# Not called in a condition: there, errors would not stop the script.
submit()
{
    local dir=$1 repository=$2 body=$3
    git -C "$dir" status --short
    git -C "$dir" checkout -q -B "$BRANCH"
    git -C "$dir" commit -q -m "$title"
    git -C "$dir" push -q --force origin "$BRANCH"
    if [ "$(gh pr list --repo "$repository" --head "$BRANCH" --state open --json number --jq length)" == "0" ]; then
        base=$(gh repo view "$repository" --json defaultBranchRef --jq .defaultBranchRef.name)
        gh pr create --repo "$repository" --base "$base" --head "$BRANCH" --title "$title" --body "$body

Built by $RUN_URL"
    else
        echo "Updated the pull request of $repository"
    fi
}

changed=""
for submodule in schemas:danog/schemas docs:danog/MadelineProtoDocs; do
    dir=${submodule%%:*}
    repository=${submodule#*:}
    git -C "$dir" add -A
    if git -C "$dir" diff --cached --quiet; then
        echo "Nothing changed in $repository"
        continue
    fi
    submit "$dir" "$repository" "The daily \`composer build\` of MadelineProto, see the pull request with the same title in danog/MadelineProto."
    changed="$changed $repository"
done

# Only the generated files, besides the ones that were already there: not what the CI left around.
git add -u
git add -A src
if git diff --cached --quiet; then
    echo "Nothing changed in danog/MadelineProto"
    exit 0
fi
body="The daily \`composer build\`."
if [ "$changed" != "" ]; then
    body="$body

It points to the commits of the pull requests with the same title in:$changed. Merge them first, with a merge commit (not a squash or a rebase), or the commits this points to would be lost."
fi
submit . danog/MadelineProto "$body"
