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
        $executor = NativeExecutor::get();
        if (!$executor) {
            return (new PhpMessageDecoder)->decode($ciphertext, $authKey, $messageKey);
        }
        try {
            $result = $executor->run(static fn (): array => \danog\MadelineProto\Native\Crypto::decode($ciphertext, $authKey, $messageKey));
        } catch (\Exception $e) {
            if (str_starts_with($e->getMessage(), 'Invalid MTProto')) {
                throw new SecurityException($e->getMessage(), 0, $e);
            }
            throw $e;
        }
        return new DecodedMessage($result[0], Tools::unpackSignedLong(substr($result[1], 8, 8)), (int) unpack('V', substr($result[1], 16, 4))[1], substr($result[1], 0, 8));
    }
}
