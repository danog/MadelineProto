<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\EventHandler\Message\GroupMessage;
use danog\MadelineProto\EventHandler\Update;

/**
 * Allow incoming or outgoing group messages made by a certain sender.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterSender extends Filter
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
        return $update instanceof GroupMessage && $update->senderId === $this->peerResolved;
    }
}
