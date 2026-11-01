<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Text;

use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A date, shown in the local time zone of the user, in the specified format.
 */
final class Date extends RichText
{
    /** Text shown by clients that don't support dates */
    public readonly RichText $text;

    public function __construct(
        RichText|string $text,
        /** Unix timestamp of the date */
        public readonly int $date,
        /** Whether to show the date relative to the current time (e.g. "in 5 minutes"), ignoring the other flags */
        public readonly bool $relative = false,
        /** Whether to show the time in short format (e.g. 22:45) */
        public readonly bool $shortTime = false,
        /** Whether to show the time in long format (e.g. 22:45:00) */
        public readonly bool $longTime = false,
        /** Whether to show the date in short format (e.g. 17.03.22) */
        public readonly bool $shortDate = false,
        /** Whether to show the date in long format (e.g. March 17, 2022) */
        public readonly bool $longDate = false,
        /** Whether to show the day of the week */
        public readonly bool $dayOfWeek = false,
    ) {
        $this->text = RichText::from($text);
    }
}
