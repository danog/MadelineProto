<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use AssertionError;
use JsonSerializable;

/**
 * Inline query peer type
 */
enum InlineQueryPeerType implements JsonSerializable
{
    /** private chat */
    case PM;
    /** [chat](https://core.telegram.org/api/channel) */
    case Chat;
    /** private chat with a bot. */
    case BotPM;
    /** [channel](https://core.telegram.org/api/channel) */
    case Broadcast;
    /** [supergroup](https://core.telegram.org/api/channel) */
    case Megagroup;
    /** private chat with the bot itself */
    case SameBotPM;

    /**
     * Get InlineQueryPeerType from update
     * 
     * @param string Type of the chat from which the inline query was sent.
     * @throws AssertionError
     */
    public static function fromString(string $name)
    {
        $newName = ltrim($name, "inlineQueryPeerType");
        foreach (InlineQueryPeerType::cases() as $case) {
            if ($case->name === $newName)
                return $case;
        }
        throw new AssertionError("Undefined case InlineQueryPeerType::".$name);
    }

    /** @internal */
    public function jsonSerialize(): mixed
    {
        return $this->name;
    }
}