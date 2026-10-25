#!/bin/bash -e

# Check that a pull request bumps the RELEASE constant of src/API.php as its release labels require: run by
# pr-labels.yml on the merge commit of the pull request (with the main branch), so a bump already on the main branch
# and not released yet counts too.
# If the last release is 8.20.1, a release:feature or release:layer pull request needs at least 8.21.0, a release:fix
# one at least 8.20.2, a release:internal one nothing if it only changes what doesn't ship (CI, tests, tools, docs:
# see ships in tests/release_version.sh), 8.20.2 otherwise. Once merged, tests/prerelease.sh releases the version of
# the RELEASE constant, if it isn't released yet.
#
# The pull requests of Dependabot (AUTHOR) are exempt: it can't bump the RELEASE constant, tests/dependabot_release.sh
# does once they're merged.
#
# Usage: tests/release_check.sh <labels...>. The changes are the ones of HEAD since BASE, by default its first parent
# (the main branch, for the merge commit of a pull request).

source tests/release_version.sh

if [ "$AUTHOR" == "dependabot[bot]" ]; then
    echo "A pull request of Dependabot: its release is bumped once it's merged, see tests/dependabot_release.sh"
    exit 0
fi

release=$(release_constant)
if ! [[ "$release" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Invalid RELEASE constant: $release"
    exit 1
fi
last=$(last_release "${release%%.*}")
if [ "$last" == "" ]; then
    echo "No release of version ${release%%.*} yet: RELEASE is $release"
    exit 0
fi
files=$(git diff --no-renames --name-only "${BASE:-HEAD^1}" HEAD)
need="the labels (${*:-none})"
if ships <<< "$files"; then
    # At least a patch release.
    need="$need and the files that ship ($(ships_list <<< "$files" | head -5 | paste -sd " "))"
    set -- "$@" release:fix
fi
required=$(required_version "$last" "$@")

if [ "$(max_version "$release" "$last")" != "$release" ]; then
    echo "The RELEASE constant ($release) is lower than the last release, $last"
    exit 1
fi
if [ "$(max_version "$release" "$required")" != "$release" ]; then
    echo "In the pull request, $need need the RELEASE constant of src/API.php bumped to at least $required (the last release is $last, it's $release): run"
    echo
    echo "    sed -i \"s/public const RELEASE = '[^']*';/public const RELEASE = '$required';/\" src/API.php"
    exit 1
fi
echo "RELEASE is $release: the last release is $last, $need need at least $required"
