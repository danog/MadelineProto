<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

/**
 * Inline query peer type
 */
enum InlineQueryPeerType
{
    /** private chat */
    case InlineQueryPeerTypePM;
    /** [chat](https://core.telegram.org/api/channel) */
    case InlineQueryPeerTypeChat;
    /** private chat with a bot. */
    case InlineQueryPeerTypeBotPM;
    /** [channel](https://core.telegram.org/api/channel) */
    case InlineQueryPeerTypeBroadcast;
    /** [supergroup](https://core.telegram.org/api/channel) */
    case InlineQueryPeerTypeMegagroup;
    /** private chat with the bot itself */
    case InlineQueryPeerTypeSameBotPM;

    /**
     * Get InlineQueryPeerType from update
     * 
     * @param string Type of the chat from which the inline query was sent.
     */
    public static function fromString(string $name): ?InlineQueryPeerType
    {
        $name = ucfirst($name);
        foreach(InlineQueryPeerType::cases() as $status)
        {
            if($status->name === $name)
                return $status;
        }
        return null;
    }
}