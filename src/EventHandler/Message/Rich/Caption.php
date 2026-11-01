<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich;

use JsonSerializable;

/**
 * Caption of a media, collage, slideshow or map block of a rich message.
 */
final class Caption implements JsonSerializable
{
    use JsonSerializeTrait;

    /** Text of the caption */
    public readonly RichText $text;
    /** Credit of the caption (e.g. its author) */
    public readonly ?RichText $credit;

    public function __construct(RichText|string $text, RichText|string|null $credit = null)
    {
        $this->text = RichText::from($text);
        $this->credit = $credit === null ? null : RichText::from($credit);
    }

    /**
     * Get a caption from a caption, a rich text or a plain string.
     *
     * @psalm-pure
     */
    public static function from(self|RichText|string|null $caption): ?self
    {
        return $caption === null || $caption instanceof self ? $caption : new self($caption);
    }
}
