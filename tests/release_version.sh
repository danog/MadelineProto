#!/bin/bash
# Sourced by the scripts that deal with the version of the next release (the RELEASE constant of src/API.php):
# tests/release_check.sh, tests/prerelease.sh, tests/autobuild.sh, tests/update_docker.sh.
#
# Every pull request bumps the RELEASE constant itself, as its release label requires (see tests/release_check.sh):
# once merged, tests/prerelease.sh releases the version of the constant, if it isn't released yet.

# The RELEASE constant of the checkout.
release_constant()
{
    grep -o "RELEASE = '[^']*'" src/API.php | grep -o "[0-9][^']*"
}

# Set the RELEASE constant of the checkout: set_release_constant <version>.
set_release_constant()
{
    VERSION=$1 perl -pi -e "s/public const RELEASE = '[^']*';/public const RELEASE = '\$ENV{VERSION}';/" src/API.php
}

# The highest of two versions: max_version <a> <b>.
max_version()
{
    printf '%s\n%s\n' "$1" "$2" | sort -V | tail -1
}

# The last version released (the highest tag) of a major version, from the remote origin (without fetching the
# tags: the checkouts of the CI are shallow): last_release <major>. Empty if there's none.
last_release()
{
    git ls-remote -q --tags --refs origin "refs/tags/$1.*" | cut -d/ -f3 | grep -E '^[0-9]+\.[0-9]+\.[0-9]+$' | sort -V | tail -1 || true
}

# The lowest version a pull request must bump the RELEASE constant to, given the last release and the release
# labels of the pull request: required_version <last release> <labels...>.
# release:feature and release:layer need a minor version, release:fix a patch one, release:internal none.
required_version()
{
    local last=$1 x y z bump=none label
    shift
    for label in "$@"; do
        case $label in
            release:feature|release:layer) bump=minor ;;
            release:fix) [ "$bump" == minor ] || bump=patch ;;
        esac
    done
    IFS=. read -r x y z <<< "$last"
    case $bump in
        minor) echo "$x.$((y + 1)).0" ;;
        patch) echo "$x.$y.$((z + 1))" ;;
        *) echo "$last" ;;
    esac
}

# Bump the RELEASE constant of the checkout, if needed, to the version required by release labels (see
# required_version): bump_release_constant <labels...>. Prints the version.
bump_release_constant()
{
    local release last version
    release=$(release_constant)
    last=$(last_release "${release%%.*}")
    version=$(max_version "$release" "$(required_version "${last:-$release}" "$@")")
    if [ "$version" != "$release" ]; then
        set_release_constant "$version"
    fi
    echo "$version"
}
