<?php declare(strict_types=1);

namespace danog\MadelineProto\MTProtoTools;

use AssertionError;
use danog\MadelineProto\Magic;
use Webmozart\Assert\Assert;

/**
 * Represents the type of a bot API dialog ID.
 */
enum DialogId
{
    case USER;
    case CHAT;
    case CHANNEL_OR_SUPERGROUP;
    case SECRET_CHAT;

    /**
     * Get the type of a dialog using just its bot API dialog ID.
     *
     * For more detailed types, use API::getType, instead.
     *
     * @param integer $id Bot API ID.
     */
    public static function getType(int $id): self
    {
        if ($id < 0) {
            if (-Magic::MAX_CHAT_ID <= $id) {
                return DialogId::CHAT;
            }
            if (Magic::ZERO_CHANNEL_ID - Magic::MAX_CHANNEL_ID <= $id && $id !== Magic::ZERO_CHANNEL_ID) {
                return DialogId::CHANNEL_OR_SUPERGROUP;
            }
            if (Magic::ZERO_SECRET_CHAT_ID + Magic::MIN_INT32 <= $id && $id !== Magic::ZERO_SECRET_CHAT_ID) {
                return DialogId::SECRET_CHAT;
            }
        } elseif (0 < $id && $id <= Magic::MAX_USER_ID) {
            return DialogId::USER;
        }
        throw new AssertionError("Invalid ID $id provided!");
    }

    /**
     * Convert MTProto secret chat ID to bot API secret chat ID.
     *
     * @internal
     *
     * @param int $id MTProto secret chat ID
     *
     * @return int Bot API secret chat ID
     */
    public static function fromSecretChatId(int $id): int
    {
        return Magic::ZERO_SECRET_CHAT_ID + $id;
    }
    /**
     * Convert bot API secret chat ID to MTProto secret chat ID.
     *
     * @internal
     *
     * @param int $id Bot API secret chat ID
     *
     * @return int MTProto secret chat ID
     */
    public static function toSecretChatId(int $id): int
    {
        Assert::eq(self::getType($id), self::SECRET_CHAT);
        return $id - Magic::ZERO_SECRET_CHAT_ID;
    }

    /**
     * Convert MTProto channel ID to bot API channel ID.
     *
     * @internal
     *
     * @param int $id MTProto channel ID
     */
    public static function fromSupergroupOrChannel(int $id): int
    {
        Assert::true($id > 0);
        return Magic::ZERO_CHANNEL_ID - $id;
    }
    /**
     * Convert bot API channel ID to MTProto channel ID.
     *
     * @internal
     *
     * @param int $id Bot API channel ID
     */
    public static function toSupergroupOrChannel(int $id): int
    {
        Assert::eq(self::getType($id), self::CHANNEL_OR_SUPERGROUP, "The provided ID is not a bot API channel ID");
        return (-$id) + Magic::ZERO_CHANNEL_ID;
    }

    /**
     * Checks whether the provided bot API ID is a supergroup or channel ID.
     */
    public static function isSupergroupOrChannel(int $id): bool
    {
        return self::getType($id) === self::CHANNEL_OR_SUPERGROUP;
    }

    /**
     * Checks whether the provided bot API ID is a chat ID.
     */
    public static function isChat(int $id): bool
    {
        return self::getType($id) === self::CHAT;
    }
    /**
     * Checks whether the provided bot API ID is a user ID.
     */
    public static function isUser(int $id): bool
    {
        return self::getType($id) === self::USER;
    }
    /**
     * Checks whether the provided bot API ID is a secret chat ID.
     */
    public static function isSecretChat(int $id): bool
    {
        return self::getType($id) === self::SECRET_CHAT;
    }
}
