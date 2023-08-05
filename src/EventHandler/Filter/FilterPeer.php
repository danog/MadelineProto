<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Update;

/**
 * Allow messages coming from or sent to a certain peer.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterPeer extends Filter
{
    private readonly int $peerResolved;
    public function __construct(private readonly string|int $peer)
    {
    }
    public function initialize(EventHandler $API): Filter
    {
        /** @psalm-suppress InaccessibleProperty */
        $this->peerResolved = $API->getId($this->peer);
        return $this;
    }
    public function apply(Update $update): bool
    {
        return $update instanceof Message && $update->chatId === $this->peerResolved;
    }
}
