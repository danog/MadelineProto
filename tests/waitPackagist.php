#!/usr/bin/env php
<?php

$commit = \getenv('GITHUB_SHA');
$branch = \trim(\shell_exec("git rev-parse --abbrev-ref HEAD"));
$tag = \trim(\shell_exec("git tag --points-at HEAD"));

echo "Waiting for commit $commit on branch $branch (tag $tag)...".PHP_EOL;

if (\substr($tag, -5) === '.9999' || \substr($tag, -5) === '.9998') {
    die;
}

$branch = $tag ? $tag : "dev-$branch";

$cur = 0;
while (true) {
    $json = \json_decode(\file_get_contents("https://repo.packagist.org/p/danog/madelineproto.json?v=$cur"), true);
    if ($json["packages"]["danog/madelineproto"][$branch]["source"]["reference"] === $commit) {
        return;
    }
    \sleep(1);
    $cur++;
}
