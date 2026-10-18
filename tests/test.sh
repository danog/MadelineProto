#!/bin/bash

set -ex

export COMPOSER_PROCESS_TIMEOUT=100000
apt-get update && apt-get install -y wget

mkdir -p -m 755 /etc/apt/keyrings
out=$(mktemp) && wget -nv -O$out https://cli.github.com/packages/githubcli-archive-keyring.gpg
cat $out | tee /etc/apt/keyrings/githubcli-archive-keyring.gpg > /dev/null
chmod go+r /etc/apt/keyrings/githubcli-archive-keyring.gpg
mkdir -p -m 755 /etc/apt/sources.list.d
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | tee /etc/apt/sources.list.d/github-cli.list > /dev/null
apt update

apt-get install procps git unzip gh openssh-client rsync -y

# Composer comes with the image (tests/dockerfiles/Dockerfile): not downloaded from getcomposer.org, which failed
# a run when it was unreachable.

#php tests/jit.php

php tests/lock_setup.php

if [ "$1" == "cs" ]; then
    rmdir docs
    curl -L https://github.com/danog/MadelineProtoDocs/archive/refs/heads/master.tar.gz | tar -xz
    mv MadelineProtoDocs-master/ docs

    git submodule init schemas
    git submodule update schemas

    composer docs
    composer docs-fix
    composer cs-fix

    if [ "$(git diff)" != "" ]; then echo "Please run composer build!"; exit 1; fi

    exit 0
fi

if [ "$1" == "release-notes" ]; then
    tests/release_notes.sh "$2"
    exit 0
fi

if [ "$1" == "release-draft" ]; then
    tests/release_draft.sh
    exit 0
fi

if [ "$1" == "announce" ]; then
    php tests/announce_release.php "$2"
    exit 0
fi

if [ "$1" == "build" ]; then
    tests/autobuild.sh
    exit 0
fi

if [ "$1" == "handshake" ]; then
    php tests/handshake.php
    exit 0
fi

if [ "$1" == "psalm" ]; then
    composer psalm
    exit 0
fi

if [ "$1" == "phpunit" ]; then
    # The secret chat and call tests need two user sessions (skipped without), on the test servers: see tests/ci_sessions.php.
    php tests/ci_sessions.php --test user "$PWD/ci_user.madeline" USER_SESSION_AUTH && export USER_SESSION="$PWD/ci_user.madeline" || [ $? -eq 2 ]
    php tests/ci_sessions.php --test user "$PWD/ci_peer.madeline" PEER_USER_SESSION_AUTH && export PEER_USER_SESSION="$PWD/ci_peer.madeline" || [ $? -eq 2 ]
    # The sessions of the bot, on the main servers, are created by tests/session_tests.sh, one per lane, from the
    # authorizations in BOT_SESSION_AUTH (logging in with the token in every run gets FLOOD_WAITs, see
    # tests/ci_sessions.php). BOT_TOKEN is only used for the bot API.
    # The tests in the session group (using the accounts of the CI) in lanes, at once with the others (in parallel).
    composer paratest > paratest.log 2>&1 &
    paratest=$!
    status=0
    tests/session_tests.sh || status=1
    wait $paratest || status=1
    echo "=== Tests without the accounts of the CI"
    # Blocking again, see tests/session_tests.sh.
    php -r 'stream_set_blocking(STDOUT, true);'
    cat paratest.log
    exit $status
fi

if [ "$1" == "phpunit-light" ]; then
    composer test-light
    exit 0
fi

echo "Unknown command!"

exit 1
