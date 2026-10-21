#!/bin/bash -e

# Release the RELEASE constant of the main branch once its CI succeeded (see prerelease.yml), if that version isn't
# released yet: every pull request bumps it as its release label requires (see tests/release_check.sh), so a push
# of release:internal pull requests only is usually not released. Its commit is tagged and released as a
# pre-release: the CI of the tag builds and tests the phar, and tests/release_phar.sh makes the release final once
# it passed.
#
# The notes (see tests/release_notes.sh) go below what was written by hand in the draft of the version, if any, whose
# title is kept too.
#
# Needs GITHUB_TOKEN, a token whose events run workflows (not the one of a job: the tag must run the CI), able to
# edit the releases, and the tags. CI_SHA is the commit the CI succeeded on: nothing is done if something was pushed
# since, which is handled once its CI succeeds instead.
# With DRY_RUN=1, prints what it would do instead.

source tests/release_notes.sh
source tests/release_version.sh

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
version=$(release_constant)
# It ends up in code run with the token of an admin.
if ! [[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Invalid RELEASE constant: $version"
    exit 1
fi
major=${version%%.*}
last=$(last_release "$major")
if [ "$last" == "" ]; then
    echo "No release of version $major yet"
    exit 0
fi
if [ "$version" == "$last" ]; then
    echo "$version is already released: the RELEASE constant wasn't bumped since"
    exit 0
fi
if [ "$(max_version "$version" "$last")" != "$version" ]; then
    echo "The RELEASE constant ($version) is lower than the last release, $last"
    exit 1
fi

releases=$(gh api --paginate "repos/$repository/releases?per_page=100" | jq -s 'add')
# A draft of the version, written by hand.
draft=$(jq -c --arg tag "$version" '[.[] | select(.draft and .tag_name == $tag)] | last // empty' <<< "$releases")

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
    printf 'Would release %s as a pre-release, tagging %s (since %s)\nTitle: %s\nDraft: %s\n\n%s\n' \
        "$version" "$head" "$last" "$title" "$(jq -r '.tag_name // "none"' <<< "${draft:-{\}}")" "$body"
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
