<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Text;

/**
 * A TON address detected by Telegram (sent as plain text, and detected again unless entity detection is skipped; not supported by the Bot API).
 */
final class TonAddress extends StyledText
{
}
