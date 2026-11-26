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

if [ "$1" == "prerelease" ]; then
    tests/prerelease.sh
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

if [ "$1" == "fpm" ]; then
    # The IPC worker of a script run by php-fpm (the image's), behind Caddy (Debian's): started by the web runner and
    # by the process runner, and restarted by itself (tests/danog/MadelineProto/FpmIpcTest.php).
    apt-get install -y caddy
    MADELINE_FPM_TEST=1 php -dzend.assertions=1 vendor/bin/phpunit -c phpunit-light.xml tests/danog/MadelineProto/FpmIpcTest.php
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
    # The event loop to test with: native is the image's, revolt-rs (built into danog/mongo-php-async-driver's
    # mongodb extension, see tests/dockerfiles/Dockerfile); the others are revolt/event-loop's drivers, of the
    # installations without it. The extensions of uv, ev and event aren't in the image: they're in the test image
    # (tests/dockerfiles/Dockerfile.test, danog/madelineproto:next-test).
    case "${MADELINE_EVENT_LOOP:-native}" in
        native) driver=NativeDriver; extension=mongodb ;;
        uv) driver=UvDriver; extension=uv ;;
        ev) driver=EvDriver; extension=ev ;;
        event) driver=EventDriver; extension=event ;;
        select) driver=StreamSelectDriver; extension= ;;
        *) echo "Unknown event loop: $MADELINE_EVENT_LOOP"; exit 1 ;;
    esac
    if [ "$driver" != NativeDriver ]; then
        # revolt-rs replaces revolt/event-loop's drivers (EventLoop::setDriver() throws): leave out the file of
        # conf.d enabling the mongodb extension, for this process and the ones it starts.
        scan=$(php -r 'echo PHP_CONFIG_FILE_SCAN_DIR;')
        filtered=$(mktemp -d)
        for ini in "$scan"/*.ini; do
            [ "$(basename "$ini")" == mongodb.ini ] || ln -s "$ini" "$filtered/"
        done
        export PHP_INI_SCAN_DIR=$filtered
        if php -r "exit(extension_loaded('mongodb') ? 0 : 1);"; then
            echo "The mongodb extension is still loaded: it must be enabled by $scan/mongodb.ini"
            exit 1
        fi
        export REVOLT_DRIVER="Revolt\\EventLoop\\Driver\\$driver"
    fi
    if [ -n "$extension" ] && ! php -r "exit(extension_loaded('$extension') ? 0 : 1);"; then
        echo "ext-$extension isn't loaded: run the tests in danog/madelineproto:next-test"
        exit 1
    fi
    # The driver in use.
    php -r 'require "vendor/autoload.php"; echo get_class(Revolt\EventLoop::getDriver()), PHP_EOL;'
    composer test-light
    exit 0
fi

echo "Unknown command!"

exit 1
