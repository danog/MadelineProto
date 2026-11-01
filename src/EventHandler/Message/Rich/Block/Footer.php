<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Block;

use danog\MadelineProto\EventHandler\Message\Rich\Block;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A footer.
 */
final class Footer extends Block
{
    /** The text */
    public readonly RichText $text;

    public function __construct(
        RichText|string $text,
    ) {
        $this->text = RichText::from($text);
    }
}
