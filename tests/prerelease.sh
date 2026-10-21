#!/bin/bash -e

# Release every push to the main branch whose CI succeeded, in two steps, each once the CI of the main branch
# succeeded (see prerelease.yml):
# 1. the RELEASE constant (which must match the tag for the phar to be built) is bumped to the next version, pushed
#    to the main branch: a minor version if a pull request merged since the last release is labelled
#    release:feature, a patch one otherwise (a draft of a higher version, created by hand, sets it);
# 2. once the CI of that push succeeded too, its commit is tagged and released as a pre-release: the CI of the tag
#    builds and tests the phar, and tests/release_phar.sh makes the release final once it passed.
# If something was pushed in between (cancelling the CI of the bump), its own CI releases it, bumping the RELEASE
# constant again first if the version changed (a release:feature pull request).
#
# The notes (see tests/release_notes.sh) go below what was written by hand in the draft of the version, if any, whose
# title is kept too.
#
# Needs GITHUB_TOKEN, a token whose events run workflows (not the one of a job: the push and the tag must run the
# CI), able to push to the main branch (a token of an admin: see the rulesets) and to edit the releases, and the
# tags. CI_SHA is the commit the CI succeeded on: nothing is done if something was pushed since, which is handled
# once its CI succeeds instead.
# With DRY_RUN=1, prints what it would do instead.

source tests/release_notes.sh

repository=${GH_REPO:-danog/MadelineProto}
branch=v8
export GH_TOKEN="$GITHUB_TOKEN"

git fetch -q --tags origin "$branch"
git checkout -q -B "$branch" "origin/$branch"
head=$(git rev-parse HEAD)
if [ -n "$CI_SHA" ] && [ "$head" != "$CI_SHA" ]; then
    echo "$branch moved since $CI_SHA, to $head: handled once its CI succeeds"
    exit 0
fi
release=$(grep -o "RELEASE = '[^']*'" src/API.php | grep -o "[0-9][^']*")
major=${release%%.*}

releases=$(gh api --paginate "repos/$repository/releases?per_page=100" | jq -s 'add')
# The last release (pre-releases too) of the current major version.
last=$(jq -r --arg major "$major." '[.[] | select((.draft | not) and (.tag_name | startswith($major)))] | sort_by(.published_at) | last | .tag_name // empty' <<< "$releases")
if [ "$last" == "" ]; then
    echo "No release of version $major yet"
    exit 0
fi
if [ "$(git rev-list --count "$last..HEAD")" == "0" ]; then
    echo "Nothing was pushed since $last"
    exit 0
fi
since=$(jq -r --arg tag "$last" '.[] | select(.tag_name == $tag and (.draft | not)) | .published_at' <<< "$releases")

labels=$(gh pr list --repo "$repository" --base "$branch" --state merged --search "merged:>=$since" --limit 500 --json labels --jq '[.[].labels[].name] | unique | join(" ")')
IFS=. read -r x y z <<< "$last"
if [[ " $labels " == *" release:feature "* ]]; then
    version="$x.$((y + 1)).0"
else
    version="$x.$y.$((z + 1))"
fi

# Never lower than a version set by hand: in a draft, or in the RELEASE constant.
draft=$(jq -c --arg major "$major." '[.[] | select(.draft and (.tag_name | startswith($major)))] | sort_by(.created_at) | last // empty' <<< "$releases")
for set in "$(jq -r '.tag_name // empty' <<< "${draft:-{\}}")" "$release"; do
    if [ "$set" != "" ] && [ "$(printf '%s\n%s\n' "$version" "$set" | sort -V | tail -1)" == "$set" ]; then
        version=$set
    fi
done
# It ends up in code run with the token of an admin.
if ! [[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Invalid version: $version"
    exit 1
fi
if git rev-parse -q --verify "refs/tags/$version" > /dev/null; then
    echo "The tag $version already exists, at $(git rev-parse --short "$version"), but isn't the last release ($last)"
    exit 1
fi

git config user.name "${GIT_NAME:-MadelineProto release}"
git config user.email "${GIT_EMAIL:-release@madelineproto.xyz}"

# Step 1: bump the RELEASE constant, and wait for the CI of the bump.
if [ "$release" != "$version" ]; then
    if [ -n "$DRY_RUN" ]; then
        echo "Would bump the RELEASE constant from $release to $version (since $last, labels: ${labels:-none})"
        exit 0
    fi
    VERSION=$version perl -pi -e "s/public const RELEASE = '[^']*';/public const RELEASE = '\$ENV{VERSION}';/" src/API.php
    git commit -q -m "Bump the release version to $version" src/API.php
    if ! git push -q origin "$branch"; then
        if [ "$(git ls-remote origin "refs/heads/$branch" | cut -f1)" != "$head" ]; then
            echo "$branch moved since $head: handled once its CI succeeds"
            exit 0
        fi
        exit 1
    fi
    echo "Bumped the RELEASE constant to $version: released once its CI succeeds"
    exit 0
fi

# Step 2: release the commit, whose CI succeeded with the RELEASE constant of the version.
title=$version
if [ "$(layer "$last")" != "$(layer HEAD)" ]; then
    title="Layer $(layer HEAD)"
fi
body=""
if [ "$draft" != "" ]; then
    body=$(jq -r '.body // ""' <<< "$draft")
    body=${body%%"$RELEASE_NOTES_MARKER"*}
    name=$(jq -r '.name // ""' <<< "$draft")
    # A title set by hand is kept.
    if [ "$name" != "" ] && ! [[ "$name" =~ ^([0-9]+\.[0-9]+\.[0-9]+|Layer\ [0-9]+)$ ]]; then
        title=$name
    fi
fi
# Without the blank lines at the end.
body="${body%"${body##*[![:space:]]}"}"
body="${body:+$body$'\n\n'}$(release_notes "$version" "$head" "$last")"

if [ -n "$DRY_RUN" ]; then
    printf 'Would release %s as a pre-release, tagging %s (since %s, labels: %s)\nTitle: %s\nDraft: %s\n\n%s\n' \
        "$version" "$head" "$last" "${labels:-none}" "$title" "$(jq -r '.tag_name // "none"' <<< "${draft:-{\}}")" "$body"
    exit 0
fi

# Pushing the tag, rather than letting the release create it, so that the CI of the tag runs: a pre-release, which
# tests/release_phar.sh makes final and the latest once it passed (not the latest release yet: make_latest defaults
# to true).
git tag "$version" "$head"
git push -q origin "refs/tags/$version"
args=(-f tag_name="$version" -f name="$title" -f body="$body" -F draft=false -F prerelease=true -f make_latest=false)
if [ "$draft" == "" ]; then
    gh api -X POST "repos/$repository/releases" "${args[@]}" > /dev/null
else
    gh api -X PATCH "repos/$repository/releases/$(jq -r .id <<< "$draft")" "${args[@]}" > /dev/null
fi
echo "Released $version ($title) as a pre-release, tagging $head, since $last"
