<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\EventHandler\AbstractMessage;
use danog\MadelineProto\EventHandler\Query\ButtonQuery;
use danog\MadelineProto\EventHandler\Update;

/**
 * Allow only messages coming from the admin (defined as the first peer returned by getReportPeers).
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterFromAdmin extends Filter
{
    private readonly array $adminIds;
    public function initialize(EventHandler $API): Filter
    {
        /** @psalm-suppress InaccessibleProperty */
        $this->adminIds = $API->getAdminIds();
        return $this;
    }
    public function apply(Update $update): bool
    {
        return ($update instanceof AbstractMessage && \in_array($update->senderId, $this->adminIds, true)) ||
            ($update instanceof ButtonQuery && \in_array($update->userId, $this->adminIds, true));
    }
}
