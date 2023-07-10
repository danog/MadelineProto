<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\EventHandler\AbstractMessage;
use danog\MadelineProto\EventHandler\Update;

/**
 * Allow only messages coming from the admin (defined as the first peer returned by getReportPeers).
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterFromAdmin extends Filter
{
    private readonly int $adminId;
    public function initialize(EventHandler $API): ?Filter
    {
        $this->adminId = $API->getAdmin();
        return null;
    }
    public function apply(Update $update): bool
    {
        return $update instanceof AbstractMessage && $update->senderId === $this->adminId;
    }
}
