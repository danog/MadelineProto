<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Text;

use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A rich text that applies a style or a meaning to another rich text.
 */
abstract class StyledText extends RichText
{
    /** The styled text */
    public readonly RichText $text;

    public function __construct(RichText|string $text)
    {
        $this->text = RichText::from($text);
    }
}
