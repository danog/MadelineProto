<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

use AssertionError;

/**
 * Message entity representing a date and time, displayed by clients in the local time zone and format of the user.
 */
final class FormattedDate extends MessageEntity
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        /** Offset of message entity within message (in UTF-16 code units) */
        public readonly int $offset,

        /** Length of message entity within message (in UTF-16 code units) */
        public readonly int $length,

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
    }

    /**
     * Create an entity from a bot API date_time_format string (as accepted by the bot API: r, or a combination of w, d/D and t/T).
     *
     * @psalm-pure
     */
    public static function fromBotAPIFormat(int $offset, int $length, int $date, string $format): self
    {
        if ($format === 'r' || $format === 'R') {
            return new self($offset, $length, $date, relative: true);
        }
        $flags = ['shortTime' => false, 'longTime' => false, 'shortDate' => false, 'longDate' => false, 'dayOfWeek' => false];
        foreach (str_split($format) as $c) {
            match ($c) {
                't' => [$flags['shortTime'], $flags['longTime']] = [true, false],
                'T' => [$flags['shortTime'], $flags['longTime']] = [false, true],
                'd' => [$flags['shortDate'], $flags['longDate']] = [true, false],
                'D' => [$flags['shortDate'], $flags['longDate']] = [false, true],
                'w', 'W' => $flags['dayOfWeek'] = true,
                default => throw new AssertionError("Invalid date_time_format: $format"),
            };
        }
        return new self($offset, $length, $date, ...$flags);
    }

    /**
     * Get the bot API date_time_format string.
     *
     * @psalm-mutation-free
     */
    public function getBotAPIFormat(): string
    {
        if ($this->relative) {
            return 'r';
        }
        $format = $this->dayOfWeek ? 'w' : '';
        if ($this->longDate) {
            $format .= 'D';
        } elseif ($this->shortDate) {
            $format .= 'd';
        }
        if ($this->longTime) {
            $format .= 'T';
        } elseif ($this->shortTime) {
            $format .= 't';
        }
        return $format;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function toBotAPI(): array
    {
        return ['type' => 'date_time', 'offset' => $this->offset, 'length' => $this->length, 'unix_time' => $this->date, 'date_time_format' => $this->getBotAPIFormat()];
    }
    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function toMTProto(): array
    {
        return [
            '_' => 'messageEntityFormattedDate',
            'relative' => $this->relative,
            'short_time' => $this->shortTime,
            'long_time' => $this->longTime,
            'short_date' => $this->shortDate,
            'long_date' => $this->longDate,
            'day_of_week' => $this->dayOfWeek,
            'offset' => $this->offset,
            'length' => $this->length,
            'date' => $this->date,
        ];
    }
}
