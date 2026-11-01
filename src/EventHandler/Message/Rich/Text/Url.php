<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Text;

use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A link to a URL.
 */
final class Url extends RichText
{
    /** The text */
    public readonly RichText $text;

    public function __construct(
        RichText|string $text,
        /** The URL */
        public readonly string $url,
    ) {
        $this->text = RichText::from($text);
    }
}
