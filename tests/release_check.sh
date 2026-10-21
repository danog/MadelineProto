#!/bin/bash -e

# Check that a pull request bumps the RELEASE constant of src/API.php as its release labels require: run by
# pr-labels.yml on the merge commit of the pull request (with the main branch), so a bump already on the main branch
# and not released yet counts too.
# If the last release is 8.20.1, a release:feature or release:layer pull request needs at least 8.21.0, a release:fix
# one at least 8.20.2, a release:internal one nothing. Once merged, tests/prerelease.sh releases the version of the
# RELEASE constant, if it isn't released yet.
#
# Usage: tests/release_check.sh <labels...>.

source tests/release_version.sh

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
required=$(required_version "$last" "$@")

if [ "$(max_version "$release" "$last")" != "$release" ]; then
    echo "The RELEASE constant ($release) is lower than the last release, $last"
    exit 1
fi
if [ "$(max_version "$release" "$required")" != "$release" ]; then
    echo "The labels of the pull request (${*:-none}) need the RELEASE constant of src/API.php bumped to at least $required (the last release is $last, it's $release): run"
    echo
    echo "    sed -i \"s/public const RELEASE = '[^']*';/public const RELEASE = '$required';/\" src/API.php"
    exit 1
fi
echo "RELEASE is $release: the last release is $last, the labels (${*:-none}) need at least $required"
