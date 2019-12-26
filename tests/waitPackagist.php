#!/usr/bin/env php
<?php

while (true) {
    $json = \json_decode(\file_get_contents('https://repo.packagist.org/p/danog/madelineproto.json'), true);
    if ($json["packages"]["danog/madelineproto"]["dev-".\getenv("TRAVIS_BRANCH")]["source"]["reference"] === \getenv('TRAVIS_COMMIT')) {
        return;
    }
    \sleep(1);
}
