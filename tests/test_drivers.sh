#!/bin/bash -e

# The light tests (tests/test.sh phpunit-light) with an event loop driver of Revolt whose extension the docker image
# of MadelineProto doesn't have (it only has ext-uv): ext-ev's or ext-event's. They run in the test image of
# tests/drivers.Dockerfile, built here from the image of the main branch (or BASE_IMAGE) rather than pulled: pull
# requests can't push it (they have no secrets), and so they test their own changes to it.
#
# Usage: tests/test_drivers.sh <ev|event>
#
# Runs as root in the container (tests/test.sh installs packages), with the checkout mounted at its own path.
# COMPOSER_CACHE_DIR, if set, is mounted too. Logs in to Docker Hub with DOCKER_USERNAME and DOCKER_PASSWORD if set
# (anonymous pulls share its rate limit with every other user of the IP); the build passes GITHUB_TOKEN, if set, as
# the github_token secret (see tests/drivers.Dockerfile).

case "$1" in
    ev|event) ;;
    *)
        echo "Usage: $0 <ev|event>"
        exit 1
        ;;
esac

image=madelineproto-drivers

if [ -n "$DOCKER_USERNAME" ]; then
    echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin
fi

args=(--build-arg "BASE_IMAGE=${BASE_IMAGE:-danog/madelineproto:next}")
if [ -n "$GITHUB_TOKEN" ]; then
    args+=(--secret id=github_token,env=GITHUB_TOKEN)
fi
docker build --pull . -f tests/drivers.Dockerfile -t "$image" "${args[@]}"

run=(--rm --init --user root -v "$PWD:$PWD" -w "$PWD" -e MADELINE_EVENT_LOOP="$1"
    # The checkout belongs to the user of the runner, not to the one of the container: trust it.
    -e GIT_CONFIG_COUNT=1 -e GIT_CONFIG_KEY_0=safe.directory -e GIT_CONFIG_VALUE_0="$PWD")
if [ -n "$COMPOSER_CACHE_DIR" ]; then
    mkdir -p "$COMPOSER_CACHE_DIR"
    run+=(-v "$COMPOSER_CACHE_DIR:$COMPOSER_CACHE_DIR" -e COMPOSER_CACHE_DIR="$COMPOSER_CACHE_DIR")
fi
exec docker run "${run[@]}" "$image" tests/test.sh phpunit-light
