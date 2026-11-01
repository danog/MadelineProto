<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich;

use JsonSerializable;

/**
 * A block of a [rich message](https://core.telegram.org/bots/api#richblock): a heading, a paragraph, a list, a table, a media and so on.
 *
 * Blocks can be created and sent with sendRichMessage (or replyRich, editRich and so on).
 */
abstract class Block implements JsonSerializable
{
    use JsonSerializeTrait;
}
