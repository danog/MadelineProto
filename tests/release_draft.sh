#!/bin/bash -e

# Keep the draft of the next release up to date with what was pushed to the main branch since the last
# release, for it to be published by hand:
# - its version: a minor one if a pull request merged since then is labelled release:feature, a patch one
#   otherwise (never lower than the one of the draft, which can be set by hand);
# - the RELEASE constant, which must match it for the phar to be built (pushed to the main branch);
# - its notes (see tests/release_notes.sh), below what was written by hand, and its title (unless set by hand).
#
# Needs GITHUB_TOKEN, able to push to the main branch (a token of an admin: see the rulesets), and the tags.
# With DRY_RUN=1, prints what it would do instead.

source tests/release_notes.sh

repository=${GH_REPO:-danog/MadelineProto}
branch=v8
export GH_TOKEN="$GITHUB_TOKEN"

git fetch -q --tags origin "$branch"
git checkout -q -B "$branch" "origin/$branch"
major=$(grep -o "RELEASE = '[0-9]\+" src/API.php | grep -o '[0-9]\+$')

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

draft=$(jq -c --arg major "$major." '[.[] | select(.draft and (.tag_name | startswith($major)))] | sort_by(.created_at) | last // empty' <<< "$releases")
if [ "$draft" != "" ]; then
    drafted=$(jq -r .tag_name <<< "$draft")
    if [ "$(printf '%s\n%s\n' "$version" "$drafted" | sort -V | tail -1)" == "$drafted" ]; then
        version=$drafted
    fi
fi
if git rev-parse -q --verify "refs/tags/$version" > /dev/null; then
    echo "::warning::The tag $version already exists, at $(git rev-parse --short "$version"): publishing the draft releases it, not $branch"
fi

if [ -n "$DRY_RUN" ]; then
    echo "Version: $version (since $last, labels: ${labels:-none}), RELEASE: $(grep -o "RELEASE = '[^']*'" src/API.php)"
elif ! grep -q "public const RELEASE = '$version';" src/API.php; then
    perl -pi -e "s/public const RELEASE = '[^']*';/public const RELEASE = '$version';/" src/API.php
    git config user.name "${GIT_NAME:-MadelineProto release}"
    git config user.email "${GIT_EMAIL:-release@madelineproto.xyz}"
    git commit -q -m "Bump the release version to $version" src/API.php
    git push -q origin "$branch"
fi

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
body="${body:+$body$'\n\n'}$(release_notes "$version" "$branch" "$last")"

if [ -n "$DRY_RUN" ]; then
    printf 'Title: %s\nDraft: %s\n\n%s\n' "$title" "$(jq -r '.tag_name // "new"' <<< "${draft:-{\}}")" "$body"
    exit 0
fi

if [ "$draft" == "" ]; then
    gh api -X POST "repos/$repository/releases" -f tag_name="$version" -f target_commitish="$branch" -f name="$title" -f body="$body" -F draft=true -F prerelease=true > /dev/null
    echo "Drafted $version ($title), since $last"
else
    gh api -X PATCH "repos/$repository/releases/$(jq -r .id <<< "$draft")" -f tag_name="$version" -f target_commitish="$branch" -f name="$title" -f body="$body" > /dev/null
    echo "Updated the draft of $version ($title), since $last"
fi
