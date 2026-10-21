#!/bin/bash -e

# Builds and pushes the docker image (tests/dockerfiles/Dockerfile: the PHP runtime the CI runs in, and the one of
# the bots), as danog/madelineproto:
# - next and debian: every push to the main branch;
# - <version> (e.g. 8.11.2): the CI of a tag, before its tests, which run in it;
# - latest, <major>.<minor> and <major> (e.g. 8.11 and 8): once the release of a tag passed and was published, if it's
#   the newest release (of its minor/major version).
# Each is a manifest of the images of each architecture, <tag>-debian-<arch>, built by the jobs of each architecture.
#
# Usage:
#   tests/docker_image.sh build <arch>  Build and push the image of an architecture (amd64, arm64).
#   tests/docker_image.sh merge         Push the manifest of the images of the architectures: next and debian, or the
#                                       version of the tag.
#   tests/docker_image.sh release       Point latest, <major>.<minor> and <major> to the version of the tag.
#
# CI_COMMIT_TAG is the tag being built, empty for the main branch. Logs in with DOCKER_USERNAME and DOCKER_PASSWORD
# if set. The build passes GITHUB_TOKEN, if set, as the github_token secret (see the Dockerfile).
# With DRY_RUN=1, prints the docker commands instead of running them.

image=danog/madelineproto
arches=(amd64 arm64)

run() {
    if [ -n "$DRY_RUN" ]; then
        echo "$*"
    else
        "$@"
    fi
}

tag=$CI_COMMIT_TAG
# It ends up in the names of the images: [A-Za-z0-9_.-], not starting with . or -, at most 128 characters,
# minus the -debian-<arch> suffix.
if [ -n "$tag" ] && ! [[ "$tag" =~ ^[A-Za-z0-9_][A-Za-z0-9_.-]{0,100}$ ]]; then
    echo "Invalid tag for a docker image: $tag"
    exit 1
fi
# The images of the architectures: of the version for a tag, so that a push to the main branch while the tag is being
# built or tested can't replace them.
base=${tag:-next}

if [ -n "$DOCKER_USERNAME" ] && [ -z "$DRY_RUN" ]; then
    echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin
fi

# Pushes $1 as the manifest of the images of the architectures of $base.
manifest() {
    local images=()
    for arch in "${arches[@]}"; do
        images+=("$image:$base-debian-$arch")
    done
    # With docker manifest, which reads the images as the account logged in: docker buildx imagetools create read
    # some of them anonymously, failing with the rate limit of anonymous pulls (429).
    run docker manifest create --amend "$image:$1" "${images[@]}"
    run docker manifest push --purge "$image:$1"
}

# Whether $tag is the newest X.Y.Z tag of the repository starting with $1 (of any version if empty).
newest() {
    local newest
    newest=$(git ls-remote --tags --refs "https://github.com/${GH_REPO:-danog/MadelineProto}" | sed 's|.*refs/tags/||' | grep -E '^[0-9]+\.[0-9]+\.[0-9]+$' \
        | while read -r version; do [[ "$version" == "$1"* ]] && echo "$version"; done | sort -V | tail -1)
    [ "$newest" == "$tag" ]
}

case "$1" in
    build)
        arch=$2
        if ! [[ " ${arches[*]} " == *" $arch "* ]]; then
            echo "Unknown architecture: $arch"
            exit 1
        fi
        args=(--cache-from "$image:$base-debian-$arch")
        if [ "$base" != next ]; then
            args+=(--cache-from "$image:next-debian-$arch")
        fi
        if [ -n "$GITHUB_TOKEN" ]; then
            args+=(--secret id=github_token,env=GITHUB_TOKEN)
        fi
        # Without attestations: a plain image (rather than an index), for docker manifest. The cache of a tag is the
        # image of the main branch, which is usually the same commit.
        run docker buildx build --platform "linux/$arch" . -f tests/dockerfiles/Dockerfile \
            -t "$image:$base-debian-$arch" "${args[@]}" --cache-to type=inline \
            --provenance=false --sbom=false --push
        ;;
    merge)
        if [ -n "$tag" ]; then
            manifest "$tag"
        else
            manifest next
            manifest debian
        fi
        ;;
    release)
        if ! [[ "$tag" =~ ^([0-9]+)\.([0-9]+)\.[0-9]+$ ]]; then
            echo "Not a release version, not moving latest: ${tag:-(no tag)}"
            exit 0
        fi
        major=${BASH_REMATCH[1]}
        minor=$major.${BASH_REMATCH[2]}
        # Not back to an older version, if an older one was (re)built after a newer one.
        for alias in "$minor" "$major" latest; do
            prefix=$alias.
            [ "$alias" == latest ] && prefix=
            if newest "$prefix"; then
                manifest "$alias"
            else
                echo "$tag isn't the newest ${prefix:+$alias.x }release: not moving $alias"
            fi
        done
        ;;
    *)
        echo "Usage: $0 build <arch> | merge | release"
        exit 1
        ;;
esac
