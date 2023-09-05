<?php declare(strict_types=1);

namespace danog\MadelineProto\Stream;

use IteratorAggregate;
use Traversable;

/**
 * @internal
 *
 * @implements IteratorAggregate<int, ConnectionContext>
 */
final class ContextIterator implements IteratorAggregate
{
    public function __construct(
        /** @var non-empty-list<ConnectionContext> */
        private readonly array $ctxs
    ) {
    }

    public function getDc(): int
    {
        return $this->ctxs[0]->getDc();
    }
    public function isMedia(): bool
    {
        return $this->ctxs[0]->isMedia();
    }
    public function isCDN(): bool
    {
        return $this->ctxs[0]->isCDN();
    }
    public function getIterator(): Traversable
    {
        foreach ($this->ctxs as $ctx) {
            yield $ctx->clone();
        }
    }
}
