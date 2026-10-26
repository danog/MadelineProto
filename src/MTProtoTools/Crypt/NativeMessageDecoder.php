<?php

declare(strict_types=1);

/**
 * @author Alexander Pankratov <s.pankratoff@gmail.com>
 * @copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * @license https://opensource.org/licenses/AGPL-3.0 AGPLv3
 */

namespace danog\MadelineProto\MTProtoTools\Crypt;

use danog\MadelineProto\SecurityException;
use danog\MadelineProto\Tools;

/** @internal */
final class NativeMessageDecoder implements MessageDecoder
{
    #[\Override]
    public function decode(string $ciphertext, string $authKey, string $messageKey): DecodedMessage
    {
        if (\strlen($authKey) !== 256 || \strlen($messageKey) !== 16
            || \strlen($ciphertext) < 48 || \strlen($ciphertext) % 16) {
            throw new SecurityException('Invalid encrypted transport message size');
        }
        $executor = NativeExecutor::get(NativeExecutor::DECODE_V2);
        if (!$executor) {
            return (new PhpMessageDecoder)->decode($ciphertext, $authKey, $messageKey);
        }
        $result = $executor->submit(static fn (\FFI $ffi, \FFI\CData $pool): int => (int) $ffi->madeline_crypto_submit_message($pool, $ciphertext, \strlen($ciphertext), $authKey, $messageKey))->await();
        return new DecodedMessage($result['data'], Tools::unpackSignedLong(substr($result['state'], 8, 8)), (int) unpack('V', substr($result['state'], 16, 4))[1], substr($result['state'], 0, 8));
    }
}
