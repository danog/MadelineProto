<?php

declare(strict_types=1);

namespace danog\MadelineProto;

/**
 * Represents an event handler issue.
 */
final class EventHandlerIssue
{
    public function __construct(
        /** Issue message */
        public readonly string $message,
        /** Issue file */
        public readonly string $file,
        /** Issue line */
        public readonly int $line,
        /** Whether the issue is severe enough to block inclusion */
        public readonly bool $severe,
    ) {
    }

    public function __toString(): string
    {
        return \sprintf(
            Lang::$current_lang[$this->severe ? 'static_analysis_severe' : 'static_analysis_minor'],
            "{$this->file}:{$this->line}",
            $this->message
        );
    }

    public function log(): void
    {
        Logger::log((string) $this, Logger::FATAL_ERROR);
    }

    public function getHTML(): string
    {
        $issueStr = \htmlentities((string) $this);
        $color = $this->severe ? 'red' : 'orange';
        $warning = "<h2 style='color:$color;'>{$issueStr}</h2>";
        return $warning;
    }

    public function throw(): void
    {
        throw new Exception(message: (string) $this, file: $this->file, line: $this->line);
    }
}
