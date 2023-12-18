<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

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
        return sprintf(
            Lang::$current_lang[$this->severe ? 'static_analysis_severe' : 'static_analysis_minor'],
            "{$this->file}:{$this->line}",
            $this->message
        );
    }

    public function log(): void
    {
        Logger::log((string) $this, $this->severe ? Logger::FATAL_ERROR : Logger::ERROR);
    }

    public function getHTML(): string
    {
        $issueStr = htmlentities((string) $this);
        $color = $this->severe ? 'red' : 'orange';
        $warning = "<h2 style='color:$color;'>{$issueStr}</h2>";
        return $warning;
    }

    public function throw(): void
    {
        throw new Exception(message: (string) $this, file: $this->file, line: $this->line);
    }
}
