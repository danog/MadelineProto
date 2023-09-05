<?php declare(strict_types=1);

namespace danog\MadelineProto\MTProtoTools;

/**
 * Represents the type of a bot API dialog ID.
 */
enum DialogIdType
{
    case USER;
    case CHAT;
    case CHANNEL_OR_SUPERGROUP;
    case SECRET_CHAT;
}
