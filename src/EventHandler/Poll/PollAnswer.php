<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Amir Hossein Jafari <amirhosseinjafari8228@gmail.com>
 * @copyright 2016-2023 Amir Hossein Jafari <amirhosseinjafari8228@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Poll;

use danog\MadelineProto\EventHandler\Message\Entities\MessageEntity;
use danog\MadelineProto\StrTools;
use danog\MadelineProto\TL\Types\Bytes;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/** Represents a possible answer of a poll */
final class PollAnswer implements JsonSerializable
{
    /** Textual representation of the answer */
    public readonly string $text;

    /**
     * Styled text entities in the answer.
     *
     * @var list<MessageEntity>
     */
    public readonly array $entities;

    /** The param that has to be passed to [messages.sendVote](https://docs.madelineproto.xyz/API_docs/methods/messages.sendVote.html) */
    public readonly string $option;

    /** Whether we have chosen this answer */
    public readonly ?bool $chosen;

    /** For quizzes, whether the option we have chosen is correct */
    public readonly ?bool $correct;

    /** How many users voted for this option */
    public readonly ?int $voters;

    /** @internal */
    public function __construct(array $rawAnswer)
    {
        $this->text = $rawAnswer['text']['text'];
        $this->entities = MessageEntity::fromRawEntities($rawAnswer['text']['entities']);
        $this->option = (string) $rawAnswer['option'];
        $this->chosen = $rawAnswer['chosen'] ?? null;
        $this->correct = $rawAnswer['correct'] ?? null;
        $this->voters = $rawAnswer['voters'] ?? null;
    }

    protected readonly string $html;
    protected readonly string $htmlTelegram;

    /**
     * Get an HTML version of the answer.
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

    /** @internal */
    public function jsonSerialize(): mixed
    {
        $res = ['_' => static::class];
        $refl = new ReflectionClass($this);
        foreach ($refl->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $res[$prop->getName()] = $prop->getValue($this);
        }
        $res['option'] = new Bytes($res['option']);
        return $res;
    }
}
