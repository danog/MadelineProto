<?php

declare(strict_types=1);

/**
 * @author Alexander Pankratov <s.pankratoff@gmail.com>
 * @copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * @license https://opensource.org/licenses/AGPL-3.0 AGPLv3
 */

namespace danog\MadelineProto\MTProtoTools\Crypt;

/** Authenticated transport payload; connection state is checked by ReadLoop. @internal */
final class DecodedMessage
{
    public function __construct(
        public readonly string $body,
        public readonly int $messageId,
        public readonly int $seqNo,
        public readonly string $sessionId,
    ) {
    }
}
