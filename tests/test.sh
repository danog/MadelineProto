#!/bin/bash -ex

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer

apk add procps git unzip github-cli openssh

rm -rf docs
curl -L https://github.com/danog/MadelineProtoDocs/archive/refs/heads/master.tar.gz | tar -xz
mv MadelineProtoDocs-master/ docs

composer update
composer build

if [ "$(git diff)" != "" ]; then echo "Please run composer build!"; exit 1; fi