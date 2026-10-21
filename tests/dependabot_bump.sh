#!/bin/bash -e

# Bump the RELEASE constant in the pull request of Dependabot whose version check (tests/release_check.sh) just
# failed on HEAD_SHA, because it changes what ships (the dependencies of composer.json): Dependabot can't bump it
# itself. Run by automerge.yml, which merges it once the checks of the bump passed. Only if it's still at that
# commit, it only changes composer.json, and the bump is what's missing.
#
# Only git runs on the code of the pull request (in a worktree), not the code itself: the scripts are the ones of the
# main branch.
#
# Needs GH_TOKEN, whose pushes run workflows (not the token of a job: the checks must run on the bump), able to push to
# the branches of Dependabot, the remote origin with its credentials, and HEAD_SHA.

set -o pipefail
source tests/release_version.sh

repository=${GH_REPO:-danog/MadelineProto}
export GH_REPO="$repository"

read -r pr branch < <(gh pr list --author app/dependabot --state open --json number,headRefOid,headRefName --jq ".[] | select(.headRefOid == \"$HEAD_SHA\") | \"\(.number) \(.headRefName)\"") || true
if [ -z "$pr" ]; then
    echo "No open pull request of Dependabot at $HEAD_SHA (pushed again or closed since)"
    exit 0
fi

git fetch -q origin "+refs/heads/v8:refs/remotes/origin/v8" "$HEAD_SHA"
base=$(git merge-base origin/v8 "$HEAD_SHA")
files=$(git diff --no-renames --name-only "$base" "$HEAD_SHA")
if [ "$files" != composer.json ]; then
    echo "Not bumping #$pr: it changes more than composer.json ($(paste -sd ' ' <<< "$files"))"
    exit 0
fi

worktree=$(mktemp -d)
git worktree add -q --detach "$worktree" "$HEAD_SHA"
cd "$worktree"
release=$(release_constant)
version=$(bump_release_constant release:fix)
if [ "$version" == "$release" ]; then
    echo "Not bumping #$pr: RELEASE ($release) is already enough, its check failed for something else"
    exit 0
fi
git -c user.name="MadelineProto release" -c user.email=release@madelineproto.xyz commit -q -m "Bump the release version to $version" src/API.php
git push -q --force-with-lease="refs/heads/$branch:$HEAD_SHA" origin "HEAD:refs/heads/$branch"
echo "Bumped RELEASE to $version in #$pr ($branch)"
