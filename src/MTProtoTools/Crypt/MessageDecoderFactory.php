<?php

declare(strict_types=1);

/**
 * @author Alexander Pankratov <s.pankratoff@gmail.com>
 * @copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * @license https://opensource.org/licenses/AGPL-3.0 AGPLv3
 */

namespace danog\MadelineProto\MTProtoTools\Crypt;

use danog\MadelineProto\MTProtoTools\Crypt;

/** @internal */
final class MessageDecoderFactory
{
    public static function create(): MessageDecoder
    {
        // Small messages are decoded synchronously by the extension even without its executor.
        return Crypt::native() ? new NativeMessageDecoder : new PhpMessageDecoder;
    }
}
