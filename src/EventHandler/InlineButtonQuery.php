<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\MTProto;

final class InlineButtonQuery extends AbstractButtonQuery
{
    /** @var int Message ID */
    private readonly int $messageId;

    /** @internal */
    public function __construct(MTProto $API, array $rawCallback)
    {
        parent::__construct($API, $rawCallback);
        $this->messageId = $rawCallback['msg_id'];
    }
}
