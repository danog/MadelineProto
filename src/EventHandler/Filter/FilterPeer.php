<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Update;

/**
 * Filter messages coming from or sent to a certain peer.
 */
final class FilterPeer extends Filter
{
    private readonly int $peerResolved;
    public function __construct(private readonly string|int $peer)
    {
    }
    public function initialize(EventHandler|API $API): void
    {
        $this->peerResolved = $API->getId($this->peer);
    }
    public function apply(Update $update): bool
    {
        return $update instanceof Message && $update->chatId === $this->peerResolved;
    }
}
