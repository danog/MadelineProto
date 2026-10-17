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

apt-get install procps git unzip gh openssh-client -y

cd /tmp

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer

cd $OLDPWD

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
    composer test
    exit 0
fi

if [ "$1" == "phpunit-light" ]; then
    composer test-light
    exit 0
fi

echo "Unknown command!"

exit 1
