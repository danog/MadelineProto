<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\EventHandler\Message\GroupMessage;
use danog\MadelineProto\EventHandler\Update;

/**
 * Allow incoming or outgoing group messages made by a certain list of senders.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterFromSenders extends Filter
{
    /** @var array<string|int> */
    private readonly array $peers;
    /** @var list<int> */
    private readonly array $peersResolved;
    public function __construct(string|int ...$idOrUsername)
    {
        $this->peers = \array_unique($idOrUsername);
    }
    public function initialize(EventHandler $API): Filter
    {
        if (\count($this->peers) === 1) {
            return (new FilterFromSender(\array_values($this->peers)[0]))->initialize($API);
        }
        $res = [];
        foreach ($this->peers as $peer) {
            $res []= $API->getId($peer);
        }
        /** @psalm-suppress InaccessibleProperty */
        $this->peersResolved = $res;
        return $this;
    }
    public function apply(Update $update): bool
    {
        return $update instanceof GroupMessage && \in_array($update->senderId, $this->peersResolved, true);
    }
}
