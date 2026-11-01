<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Block;

use danog\MadelineProto\EventHandler\Message\Rich\Block;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * An expandable block quote, shown collapsed.
 */
final class ExpandableBlockquote extends Block
{
    /** The text of the quote */
    public readonly RichText $text;
    /** Credit of the quote (e.g. its author) */
    public readonly ?RichText $credit;

    public function __construct(RichText|string $text, RichText|string|null $credit = null)
    {
        $this->text = RichText::from($text);
        $this->credit = $credit === null ? null : RichText::from($credit);
    }
}
