<?php

use danog\MadelineProto\Logger;
use danog\MadelineProto\TON\API;

require 'vendor/autoload.php';

$API = new API(
    [
        'logger' => [
            'logger' => Logger::ECHO_LOGGER
        ]
    ]
);

$API->async(true);
$API->loop(
    function () use ($API) {
        yield $API->connect(__DIR__.'/ton-lite-client-test1.config.json');
        $API->logger(yield $API->liteServer->getTime());
    }
);
