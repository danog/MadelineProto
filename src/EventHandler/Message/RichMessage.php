<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Message;

use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\EventHandler\Message\Entities\MessageEntity;
use danog\MadelineProto\EventHandler\Message\Rich\Block;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\TL\Conversion\Rich\OutputConverter;
use danog\MadelineProto\TL\Conversion\Rich\TextConverter;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/**
 * A rich message: structured text made of blocks (headings, paragraphs, lists, tables, quotes, media, buttons and so on).
 *
 * Like a normal message, it has a plain text version ($message) with entities for styled text ($entities), its media ($media) and an HTML version (getHTML()).
 *
 * Its blocks are typed Block objects (see the Rich\Block namespace), whose texts are RichText objects (see the Rich\Text namespace): each RichText also has a plain text, entities and HTML version.
 *
 * The blocks can be sent again as-is with sendRichMessage (or editRich, replyRich and so on), and so can the whole object, except for:
 * - unsupported blocks (blocks and media that can't be represented), which are dropped;
 * - mentions, hashtags, TON addresses and the other entities detected by Telegram, which are sent as plain text, and detected again unless entity detection is skipped.
 *
 * Rich messages are not media messages: media filters (e.g. FilterMedia) don't match them, FilterNoMedia does, and FilterRichMessage matches only them.
 */
final class RichMessage implements JsonSerializable
{
    use HtmlTrait;

    /**
     * Plain text version of the rich message.
     *
     * Blocks are separated by newlines, list items are prefixed by their label, table cells are separated by tabs; media blocks are represented by their caption.
     */
    public readonly string $message;

    /**
     * [Entities](https://core.telegram.org/api/entities) for the styled text of $message.
     *
     * Besides the styles of the text, headings are bold, preformatted blocks and mathematical expressions are pre entities, quotes are block quotes, and expandable quotes and the contents of collapsed details blocks are collapsed block quotes.
     *
     * @var list<MessageEntity>
     */
    public readonly array $entities;

    /**
     * Media of the media blocks of the rich message, in order.
     *
     * @var list<Media>
     */
    public readonly array $media;

    /**
     * @internal Received rich messages are created by MadelineProto: to send one, pass a list of blocks to sendRichMessage.
     *
     * @param list<Block> $blocks Blocks of the rich message
     * @param bool $rtl Whether the text is right-to-left
     * @param bool $part Whether this is just a part of the message (e.g. while a rich message draft is being streamed)
     */
    public function __construct(
        /**
         * Blocks of the rich message.
         *
         * @var list<Block>
         */
        public readonly array $blocks,
        /** Whether the text is right-to-left */
        public readonly bool $rtl = false,
        /** Whether this is just a part of the message (e.g. while a rich message draft is being streamed) */
        public readonly bool $part = false,
    ) {
        [$this->message, $this->entities, $this->media] = TextConverter::convert($blocks);
    }

    /** @internal */
    public static function fromRawRichMessage(MTProto $API, array $rawRichMessage, bool $protected = false): self
    {
        return OutputConverter::convert(
            $rawRichMessage,
            static fn (array $media): ?Media => $API->wrapMedia($media, $protected),
        );
    }

    /** @internal */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        $res = ['_' => static::class];
        $refl = new ReflectionClass($this);
        foreach ($refl->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $res[$prop->getName()] = $prop->getValue($this);
        }
        return $res;
    }
}
