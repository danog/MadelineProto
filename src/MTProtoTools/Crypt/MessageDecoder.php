<?php

declare(strict_types=1);

/**
 * @author Alexander Pankratov <s.pankratoff@gmail.com>
 * @copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * @license https://opensource.org/licenses/AGPL-3.0 AGPLv3
 */

namespace danog\MadelineProto\MTProtoTools\Crypt;

/** Stateless decoding of inbound encrypted MTProto v2 messages. @internal */
interface MessageDecoder
{
    public function decode(string $ciphertext, string $authKey, string $messageKey): DecodedMessage;
}
