<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Amir Hossein Jafari <amirhosseinjafari8228@gmail.com>
 * @copyright 2016-2023 Amir Hossein Jafari <amirhosseinjafari8228@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Poll;

use danog\MadelineProto\EventHandler\AbstractPoll;
use danog\MadelineProto\EventHandler\Message\Entities\MessageEntity;
use danog\MadelineProto\StrTools;

/** Represents a quiz (with wrong and correct answers, results shown in the return type) poll */
final class QuizPoll extends AbstractPoll
{
    /** Explanation of quiz solution */
    public readonly ?string $solution;

    /** @var list<MessageEntity> Message [entities](https://core.telegram.org/api/entities) for styled text in quiz solution */
    public readonly array $solutionEntities;

    /** @internal */
    public function __construct(array $rawPoll)
    {
        parent::__construct($rawPoll);
        $this->solution = $rawPoll['results']['solution'] ?? null;
        $this->solutionEntities = MessageEntity::fromRawEntities($rawPoll['results']['solution_entites'] ?? []);
    }

    protected readonly string $htmlSolution;
    protected readonly string $htmlSolutionTelegram;

    /**
     * Get an HTML version of the solution.
     *
     * @psalm-suppress InaccessibleProperty
     *
     * @param bool $allowTelegramTags Whether to allow telegram-specific tags like tg-spoiler, tg-emoji, mention links and so on...
     */
    public function getSolutionHTML(bool $allowTelegramTags = false): ?string
    {
        if ($this->solution === null) {
            return null;
        }
        if (!$this->solutionEntities) {
            return StrTools::htmlEscape($this->solution);
        }
        if ($allowTelegramTags) {
            return $this->htmlSolutionTelegram ??= StrTools::entitiesToHtml($this->solution, $this->solutionEntities, $allowTelegramTags);
        }
        return $this->htmlSolution ??= StrTools::entitiesToHtml($this->solution, $this->solutionEntities, $allowTelegramTags);
    }
}
