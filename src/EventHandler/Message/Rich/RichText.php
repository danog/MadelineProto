<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich;

use danog\MadelineProto\EventHandler\Message\Entities\MessageEntity;
use danog\MadelineProto\EventHandler\Message\Rich\Text\Plain;
use danog\MadelineProto\StrTools;
use danog\MadelineProto\TL\Conversion\Rich\TextConverter;
use JsonSerializable;

/**
 * Styled text of a [rich message](https://core.telegram.org/bots/api#richtext): a tree of plain texts, styles, links and so on.
 *
 * Like a message, it has a plain text version (getText()) with entities (getEntities()) and an HTML version (getHTML()).
 */
abstract class RichText implements JsonSerializable
{
    use JsonSerializeTrait;

    /** @var array{string, list<MessageEntity>}|null */
    private ?array $converted = null;

    /**
     * Get a rich text from a rich text or a plain string.
     *
     * @psalm-pure
     */
    public static function from(self|string $text): self
    {
        return \is_string($text) ? new Plain($text) : $text;
    }

    /**
     * Get the plain text version of the rich text.
     */
    public function getText(): string
    {
        return ($this->converted ??= TextConverter::convertText($this))[0];
    }

    /**
     * Get the [entities](https://core.telegram.org/api/entities) for the styles of the plain text version of the rich text.
     *
     * @return list<MessageEntity>
     */
    public function getEntities(): array
    {
        return ($this->converted ??= TextConverter::convertText($this))[1];
    }

    /**
     * Get an HTML version of the rich text.
     *
     * @param bool $allowTelegramTags Whether to allow telegram-specific tags like tg-spoiler, tg-emoji, mention links and so on...
     */
    public function getHTML(bool $allowTelegramTags = false): string
    {
        $entities = $this->getEntities();
        if (!$entities) {
            return StrTools::htmlEscape($this->getText());
        }
        return StrTools::entitiesToHtml($this->getText(), $entities, $allowTelegramTags);
    }
}
