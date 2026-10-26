<?php

declare(strict_types=1);

/**
 * @author Alexander Pankratov <s.pankratoff@gmail.com>
 * @copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * @license https://opensource.org/licenses/AGPL-3.0 AGPLv3
 */

namespace danog\MadelineProto\MTProtoTools\Crypt;

use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\SecurityException;
use danog\MadelineProto\Tools;

/** @internal */
final class PhpMessageDecoder implements MessageDecoder
{
    #[\Override]
    public function decode(string $ciphertext, string $authKey, string $messageKey): DecodedMessage
    {
        if (\strlen($authKey) !== 256 || \strlen($messageKey) !== 16
            || \strlen($ciphertext) < 48 || \strlen($ciphertext) % 16) {
            throw new SecurityException('Invalid encrypted transport message size');
        }
        [$key, $iv] = Crypt::kdf($messageKey, $authKey, false);
        $plain = Crypt::igeDecrypt($ciphertext, $key, $iv);
        if (!hash_equals($messageKey, substr(Crypt::sha256(substr($authKey, 96, 32).$plain), 8, 16))) {
            throw new SecurityException('msg_key mismatch');
        }
        $length = unpack('V', substr($plain, 28, 4))[1];
        if ($length % 4 || $length > \strlen($plain) - 32) {
            throw new SecurityException('Invalid message_data_length');
        }
        $padding = \strlen($plain) - 32 - $length;
        if ($padding < 12 || $padding > 1024) {
            throw new SecurityException('Invalid message padding');
        }
        return new DecodedMessage(
            substr($plain, 32, $length),
            Tools::unpackSignedLong(substr($plain, 16, 8)),
            unpack('V', substr($plain, 24, 4))[1],
            substr($plain, 8, 8),
        );
    }
}
