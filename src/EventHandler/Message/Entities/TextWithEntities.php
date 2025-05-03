<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mahdi <mahdi.talaee1379@gmail.com>
 * @copyright 2016-2025 Mahdi <mahdi.talaee1379@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Message\Entities;

use danog\MadelineProto\ParseMode;
use danog\MadelineProto\StrTools;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

final class TextWithEntities implements JsonSerializable
{
    public function __construct(
        /** Text */
        public readonly string $text,
        /**
         * Message entities for styled text.
         * @var list<MessageEntity>
         */
        public readonly array $entities,
        /** Whether to parse HTML or Markdown markup in the message */
        public readonly ?ParseMode $parseMode,
    ) {
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

    protected readonly string $html;
    protected readonly string $htmlTelegram;

    /**
     * Get an HTML version of the message.
     *
     * @psalm-suppress InaccessibleProperty
     *
     * @param bool $allowTelegramTags Whether to allow telegram-specific tags like tg-spoiler, tg-emoji, mention links and so on...
     */
    public function getHTML(bool $allowTelegramTags = false): string
    {
        if (!$this->entities) {
            return StrTools::htmlEscape($this->text);
        }
        if ($allowTelegramTags) {
            return $this->htmlTelegram ??= StrTools::entitiesToHtml($this->text, $this->entities, $allowTelegramTags);
        }
        return $this->html ??= StrTools::entitiesToHtml($this->text, $this->entities, $allowTelegramTags);
    }
}
