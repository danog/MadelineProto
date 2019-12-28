#!/usr/bin/env php
<?php

$tag = \getenv('TRAVIS_TAG');
$branch = $tag ? $tag : "dev-".\getenv("TRAVIS_BRANCH");

while (true) {
    $json = \json_decode(\file_get_contents('https://repo.packagist.org/p/danog/madelineproto.json'), true);
    \sleep(1);
    if ($json["packages"]["danog/madelineproto"][$branch]["source"]["reference"] === \getenv('TRAVIS_COMMIT')) {
        return;
    }
}
