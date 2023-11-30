#!/bin/bash

set -ex

export COMPOSER_PROCESS_TIMEOUT=100000

apk add procps git unzip github-cli openssh

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer

php tests/jit.php

composer update
