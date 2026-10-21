#!/bin/bash -e

# Bump the RELEASE constant for a pull request of Dependabot merged by PUSH_SHA, a push to the main branch (see
# dependabot-release.yml): Dependabot can't bump it in the pull request, so tests/release_check.sh exempts them. The
# rules are the ones of tests/release_check.sh: a minor version for release:feature and release:layer, a patch one
# for release:fix or if it changes what ships (the dependencies of composer.json, not the GitHub actions nor the tools
# of the CI), nothing otherwise. The bump is pushed to the main branch, whose CI then releases it (tests/prerelease.sh).
#
# Needs GITHUB_TOKEN, a token whose events run workflows (not the one of a job: the push must run the CI), able to push
# to the main branch (a token of an admin: see the rulesets), and PUSH_SHA, with its parent.
# With DRY_RUN=1, prints what it would do instead.

set -o pipefail
source tests/release_version.sh

repository=${GH_REPO:-danog/MadelineProto}
branch=v8
export GH_TOKEN="$GITHUB_TOKEN"

pr=$(gh api "repos/$repository/commits/$PUSH_SHA/pulls" --jq "[.[] | select(.merged_at != null and .merge_commit_sha == \"$PUSH_SHA\" and .user.login == \"dependabot[bot]\")] | first | .number // empty")
if [ -z "$pr" ]; then
    echo "$PUSH_SHA doesn't merge a pull request of Dependabot"
    exit 0
fi
# Word splitting on purpose: the release labels have no spaces.
labels=($(gh api "repos/$repository/pulls/$pr" --jq '[.labels[].name] | join(" ")'))
files=$(git diff --no-renames --name-only "$PUSH_SHA^1" "$PUSH_SHA")
if ships <<< "$files"; then
    labels+=(release:fix)
fi
echo "#$pr: labels ${labels[*]:-none}, changes $(paste -sd ' ' <<< "$files")"

# Retried if the main branch moved meanwhile.
for attempt in 1 2 3 4 5; do
    git fetch -q origin "$branch"
    git checkout -q --detach FETCH_HEAD
    release=$(release_constant)
    version=$(bump_release_constant "${labels[@]}")
    if [ "$version" == "$release" ]; then
        echo "RELEASE ($release) is already enough for #$pr"
        exit 0
    fi
    # It ends up in code run with the token of an admin.
    if ! [[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        echo "Invalid version: $version"
        exit 1
    fi
    if [ -n "$DRY_RUN" ]; then
        echo "Would bump RELEASE from $release to $version for #$pr"
        git checkout -q src/API.php
        exit 0
    fi
    git -c user.name="MadelineProto release" -c user.email=release@madelineproto.xyz commit -q -m "Bump the release version to $version for #$pr" src/API.php
    if git push -q origin "HEAD:refs/heads/$branch"; then
        echo "Bumped RELEASE from $release to $version for #$pr: released once its CI succeeds"
        exit 0
    fi
    echo "$branch moved, trying again"
done
exit 1
