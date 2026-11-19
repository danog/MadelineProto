#!/bin/bash -e

# Update the versions the docker image pins (tests/dockerfiles/Dockerfile, tests/dockerfiles/Dockerfile.test) to their
# latest releases of the same major version, submitting the changes as one pull request of its own, with a patch
# release (so that the latest image is rebuilt with them too: its changelog is empty, the change is internal, so it's
# not announced): once its checks passed, automerge.yml merges it, and the CI rebuilds the image before running in it.
# Pinned: PHP (new minor versions too), PIE and its sha256, the extensions installed with PIE, danog/MadelineCrypto,
# the Rust toolchain that builds it, and mtg with the sha256 of its archives (pinned by MtProxyTest.php too).
# A new major version is only reported, never applied; nothing is ever downgraded.
#
# Needs GITHUB_TOKEN, able to push branches and to open pull requests (not to push to the main branch), and
# RUN_URL, linked by the pull request. With DRY_RUN=1, only updates the files and prints the pull request.

source tests/submit_pr.sh
export GH_TOKEN="$GITHUB_TOKEN"
dockerfile=tests/dockerfiles/Dockerfile
testfile=tests/dockerfiles/Dockerfile.test
mtproxytest=tests/danog/MadelineProto/MtProxyTest.php

changes=()
summary=()

# replace <old> <new> <files...>: every occurrence of old (literally, not followed by a letter or digit, so that a
# version doesn't match the start of a longer one) becomes new.
replace() {
    local old=$1 new=$2
    shift 2
    OLD=$old NEW=$new perl -pi -e 's/\Q$ENV{OLD}\E(?![0-9A-Za-z])/$ENV{NEW}/g' "$@"
}

# check_version <name> <version>: the version a source returned looks like one.
check_version() {
    if ! [[ "$2" =~ ^v?[0-9]+(\.[0-9]+)*([.-]?[A-Za-z0-9]+)*$ ]]; then
        echo "Unexpected latest version of $1: $2"
        exit 1
    fi
}

# newer <a> <b>: whether version a is newer than b (sort -V, with pre-releases marked by ~ to sort before their
# release, as in Debian's versions).
newer() {
    local a b
    a=$(echo "${1#v}" | sed -E 's/([0-9])(RC|rc|alpha|beta|a|b)/\1~\2/')
    b=$(echo "${2#v}" | sed -E 's/([0-9])(RC|rc|alpha|beta|a|b)/\1~\2/')
    [ "$a" != "$b" ] && [ "$(printf '%s\n%s\n' "$a" "$b" | sort -V | tail -1)" == "$a" ]
}

# bump <name> <current> <latest> <url>: whether latest is newer than current and of the same major version, recording
# the change for the pull request.
bump() {
    local name=$1 current=${2#v} latest=${3#v} url=$4
    check_version "$name" "$latest"
    if ! newer "$latest" "$current"; then
        echo "$name $current is up to date (latest release: $latest)"
        return 1
    fi
    if [ "${latest%%.*}" != "${current%%.*}" ]; then
        echo "$name $latest is a new major version: not updated from $current"
        return 1
    fi
    echo "$name: $current -> $latest"
    changes+=("- $name: $current → [$latest]($url)")
    summary+=("$name $latest")
}

# sha256 <url>
sha256() {
    curl -fsSL --retry 5 "$1" | sha256sum | cut -d' ' -f1
}

# PHP: the latest release of its major version.
current=$(grep -o 'php-[0-9]\+\.[0-9]\+\.[0-9]\+' "$dockerfile" | head -1 | cut -d- -f2)
latest=$(curl -fsS "https://www.php.net/releases/?json&version=${current%%.*}" | jq -r .version)
if bump PHP "$current" "$latest" "https://www.php.net/ChangeLog-${latest%%.*}.php#$latest"; then
    replace "php-$current" "php-$latest" "$dockerfile"
    # The docker-library Dockerfile it's copied from, per minor version.
    replace "php/tree/master/${current%.*}/" "php/tree/master/${latest%.*}/" "$dockerfile"
fi

# PIE.
current=$(sed -n 's/^ENV PIE_VERSION=//p' "$dockerfile")
latest=$(gh api repos/php/pie/releases/latest --jq .tag_name)
if bump PIE "$current" "$latest" "https://github.com/php/pie/releases/tag/$latest"; then
    old_sha=$(sed -n 's/^ENV PIE_SHA256=//p' "$dockerfile")
    new_sha=$(sha256 "https://github.com/php/pie/releases/download/$latest/pie.phar")
    replace "PIE_VERSION=$current" "PIE_VERSION=$latest" "$dockerfile"
    replace "$old_sha" "$new_sha" "$dockerfile"
fi

# The extensions installed with PIE (vendor/package:version): the latest release on Packagist, a pre-release only to
# replace a pre-release.
for file in "$dockerfile" "$testfile"; do
    for package in $(grep 'for package in' "$file" | grep -oE '[a-z0-9_.-]+/[a-z0-9_.-]+:[^ ;]+'); do
        name=${package%%:*}
        current=${package#*:}
        pre=0
        [[ "${current#v}" =~ [A-Za-z] ]] && pre=1
        latest=$(curl -fsS "https://repo.packagist.org/p2/$name.json" | jq -r --arg p "$name" --argjson pre "$pre" \
            '.packages[$p] | map(select($pre == 1 or (.version_normalized | contains("-") | not))) | .[0].version')
        if bump "$name" "$current" "$latest" "https://packagist.org/packages/$name#$latest"; then
            # As pinned: with or without the v.
            [[ "$current" == v* ]] || latest=${latest#v}
            replace "$package" "$name:$latest" "$file"
        fi
    done
done

# danog/MadelineCrypto.
current=$(sed -n 's/^ARG MADELINE_CRYPTO_VERSION=//p' "$dockerfile")
latest=$(gh api repos/danog/MadelineCrypto/releases/latest --jq .tag_name)
if bump MadelineCrypto "$current" "$latest" "https://github.com/danog/MadelineCrypto/releases/tag/$latest"; then
    replace "MADELINE_CRYPTO_VERSION=$current" "MADELINE_CRYPTO_VERSION=$latest" "$dockerfile"
fi

# The Rust toolchain building MadelineCrypto: the latest stable release.
current=$(grep -o -- '--default-toolchain [0-9.]\+' "$dockerfile" | cut -d' ' -f2)
channel=$(curl -fsS https://static.rust-lang.org/dist/channel-rust-stable.toml)
latest=$(echo "$channel" | awk '/^\[pkg\.rust\]/ { f = 1 } f && /^version/ { print; exit }' | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)
if bump Rust "$current" "$latest" "https://blog.rust-lang.org/releases/$latest/"; then
    replace "--default-toolchain $current" "--default-toolchain $latest" "$dockerfile"
fi

# mtg, with the sha256 of its archives (it publishes no checksums), also pinned by MtProxyTest.php.
current=$(sed -n 's/^ENV MTG_VERSION=//p' "$testfile")
latest=$(gh api repos/9seconds/mtg/releases/latest --jq .tag_name)
if bump mtg "$current" "$latest" "https://github.com/9seconds/mtg/releases/tag/$latest"; then
    latest=${latest#v}
    for arch in amd64 arm64; do
        old_sha=$(sed -n "s/^[[:space:]]*$arch) sha256=\([0-9a-f]\{64\}\) ;;.*/\1/p" "$testfile")
        new_sha=$(sha256 "https://github.com/9seconds/mtg/releases/download/v$latest/mtg-$latest-linux-$arch.tar.gz")
        replace "$old_sha" "$new_sha" "$testfile" "$mtproxytest"
    done
    replace "MTG_VERSION=$current" "MTG_VERSION=$latest" "$testfile"
    replace "MTG_VERSION = '$current'" "MTG_VERSION = '$latest'" "$mtproxytest"
fi

if [ ${#changes[@]} -eq 0 ]; then
    echo "The docker image pins the latest versions"
    exit 0
fi

title="Update the docker image: $(IFS=,; echo "${summary[*]}" | sed 's/,/, /g')"
body="New releases of what the docker image pins:
$(printf '%s\n' "${changes[@]}")"
if [ -n "$DRY_RUN" ]; then
    echo "$title"
    echo "$body"
    git diff --stat
    exit 0
fi

git config --global user.name "${GIT_NAME:-MadelineProto autobuild}"
git config --global user.email "${GIT_EMAIL:-autobuild@madelineproto.xyz}"
gh auth setup-git

source tests/release_version.sh
echo "RELEASE: $(bump_release_constant release:fix)"
git add "$dockerfile" "$testfile" "$mtproxytest" src/API.php
submit_pr . "${GH_REPO:-danog/MadelineProto}" docker-update "$title" "$body" release:internal
