<?php

declare(strict_types=1);

namespace danog\Loop\Generic {
    if (\defined('MADELINE_POLYFILLED_LOOP')) {
        return;
    }

    \define('MADELINE_POLYFILLED_LOOP', true);

    use danog\Loop\GenericLoop as LoopGenericLoop;
    use danog\Loop\PeriodicLoop as LoopPeriodicLoop;
    use danog\MadelineProto\Tools;
    use Generator;

    /**
     * @deprecated Please use danog\Loop\PeriodicLoop instead
     */
    class PeriodicLoop extends LoopPeriodicLoop
    {
        public function __construct(callable $callback, string $name, ?int $interval)
        {
            if ($callback instanceof \Closure) {
                try {
                    $callback = $callback->bindTo($this);
                } catch (\Throwable) {
                    // Might cause an error for wrapped object methods
                }
            }
            /** @psalm-suppress InvalidArgument */
            parent::__construct(
                function ($_) use ($callback) {
                    $result = $callback();
                    if ($result instanceof Generator) {
                        $result = Tools::consumeGenerator($result);
                    }
                    return $result;
                },
                $name,
                $interval ? $interval/1000 : null
            );
        }
    }

    /**
     * @deprecated Please use danog\Loop\GenericLoop instead
     */
    class GenericLoop extends LoopGenericLoop
    {
        public function __construct(callable $callback, string $name)
        {
            if ($callback instanceof \Closure) {
                try {
                    $callback = $callback->bindTo($this);
                } catch (\Throwable) {
                    // Might cause an error for wrapped object methods
                }
            }
            /** @psalm-suppress InvalidArgument */
            parent::__construct(
                function ($_) use ($callback) {
                    $result = $callback();
                    if ($result instanceof Generator) {
                        $result = Tools::consumeGenerator($result);
                    }
                    if (\is_int($result) || \is_float($result)) {
                        $result /= 1000;
                    }
                    return $result;
                },
                $name
            );
        }
    }
}
