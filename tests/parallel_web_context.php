<?php

declare(strict_types=1);

// A context script of WebContextTest: echoes values (null included) until it gets "exit".

use Amp\Sync\Channel;

return static function (Channel $channel) use ($argv): array {
    if (($argv[1] ?? null) === 'throw') {
        throw new RuntimeException('The context failed');
    }
    while (($value = $channel->receive()) !== 'exit') {
        $channel->send($value);
    }
    return ['pid' => getmypid(), 'sapi' => PHP_SAPI, 'cwd' => getcwd()];
};
