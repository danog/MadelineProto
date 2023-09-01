#!/bin/bash -ex

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer

apk add procps git unzip github-cli openssh

composer update
composer build

if [ "$(git diff)" != "" ]; then echo "Please run composer build!"; exit 1; fi