<?php

declare(strict_types=1);

/**
 * @author Alexander Pankratov <s.pankratoff@gmail.com>
 * @copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * @license https://opensource.org/licenses/AGPL-3.0 AGPLv3
 */

namespace danog\MadelineProto\MTProtoTools\Crypt;

/**
 * Authenticated transport payload; connection state is checked by ReadLoop.
 * @internal
 */
final readonly class DecodedMessage
{
    public function __construct(
        public string $body,
        public int $messageId,
        public int $seqNo,
        public string $sessionId,
    ) {
    }
}
