<?php

declare(strict_types=1);

// A context script of WebContextTest: echoes values (null included) until it gets "exit".
// With "throw", throws; with "nested", starts a context of its own, and reports it with the context factory and the
// error handler it got.

use Amp\Sync\Channel;

use function Amp\Parallel\Context\contextFactory;
use function Amp\Parallel\Context\startContext;

return static function (Channel $channel) use ($argv): array {
    if (($argv[1] ?? null) === 'throw') {
        throw new RuntimeException('The context failed');
    }
    if (($argv[1] ?? null) === 'nested') {
        $context = startContext([__FILE__]);
        $context->send('exit');
        $errorHandler = set_error_handler(null);
        restore_error_handler();
        return [
            'nested' => $context->join(),
            'pid' => getmypid(),
            'factory' => contextFactory()::class,
            'errorHandler' => $errorHandler instanceof Closure
                ? (new ReflectionFunction($errorHandler))->getClosureScopeClass()?->getName()
                : null,
        ];
    }
    while (($value = $channel->receive()) !== 'exit') {
        $channel->send($value);
    }
    return ['pid' => getmypid(), 'sapi' => PHP_SAPI, 'cwd' => getcwd()];
};
