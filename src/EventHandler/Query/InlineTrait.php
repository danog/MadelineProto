<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Query;

use danog\MadelineProto\MTProto;
use danog\MadelineProto\Tools;

/** @internal */
trait InlineTrait
{
    /** Inline message ID */
    public readonly string $inlineMessageId;

    /** @internal */
    public function __construct(MTProto $API, array $rawCallback)
    {
        parent::__construct($API, $rawCallback);
        $this->inlineMessageId = Tools::base64urlEncode(match ($rawCallback['_']) {
            'inputBotInlineMessageID' => (
                Tools::packSignedInt($rawCallback['dc_id']).
                Tools::packSignedLong($rawCallback['id']).
                Tools::packSignedLong($rawCallback['access_hash'])
            ),
            'inputBotInlineMessageID64' => (
                Tools::packSignedInt($rawCallback['dc_id']).
                Tools::packSignedLong($rawCallback['owner_id']).
                Tools::packSignedInt($rawCallback['id']).
                Tools::packSignedLong($rawCallback['access_hash'])
            ),
        });
    }
}
