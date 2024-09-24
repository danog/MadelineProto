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

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\EventHandler\Message\Entities\MessageEntity;
use danog\MadelineProto\EventHandler\Poll\MultiplePoll;
use danog\MadelineProto\EventHandler\Poll\PollAnswer;
use danog\MadelineProto\EventHandler\Poll\QuizPoll;
use danog\MadelineProto\EventHandler\Poll\SinglePoll;
use danog\MadelineProto\StrTools;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/** Poll */
abstract class AbstractPoll implements JsonSerializable
{
    /** ID of the poll */
    public readonly int $id;

    /** Whether the poll is closed and doesn’t accept any more answers */
    public readonly bool $closed;

    /** The question of the poll */
    public readonly string $question;

    /**
     * Styled text entities in the question of the poll.
     *
     * @var list<MessageEntity>
     */
    public readonly array $questionEntities;

    /** @var list<PollAnswer> The possible answers */
    public readonly array $answers;

    /**	Amount of time in seconds the poll will be active after creation, 5-600 */
    public readonly ?int $closePeriod;

    /** Point in time (Unix timestamp) when the poll will be automatically closed. Must be at least 5 and no more than 600 seconds in the future */
    public readonly ?int $closeDate;

    /** @var list<int> IDs of the last users that recently voted in the poll */
    public readonly array $recentVoters;

    /** Total number of people that voted in the poll */
    public readonly ?int $totalVoters;

    /** @internal */
    public function __construct(array $rawPoll)
    {
        $this->id           = $rawPoll['poll']['id'];
        $this->closed       = $rawPoll['poll']['closed'];
        $this->question     = $rawPoll['poll']['question']['text'];
        $this->questionEntities = MessageEntity::fromRawEntities($rawPoll['poll']['question']['entities']);
        $this->closeDate    = $rawPoll['poll']['close_date'] ?? null;
        $this->closePeriod  = $rawPoll['poll']['close_period'] ?? null;
        $this->recentVoters = $rawPoll['results']['recent_voters'] ?? [];
        $this->totalVoters  = $rawPoll['results']['total_voters'] ?? null;
        $this->answers = self::getPollAnswers($rawPoll['poll']['answers'], $rawPoll['results']['results'] ?? []);
    }

    public static function fromRawPoll(array $rawPoll): AbstractPoll
    {
        if ($rawPoll['poll']['quiz']) {
            return new QuizPoll($rawPoll);
        }

        if ($rawPoll['poll']['multiple_choice']) {
            return new MultiplePoll($rawPoll);
        }

        return new SinglePoll($rawPoll);
    }

    /**
     * @return list<PollAnswer>
     */
    private static function getPollAnswers(array $answers, array $result): array
    {
        $out = [];
        foreach ($answers as $key => $value) {
            $merge = array_merge($value, $result[$key] ?? []);
            $out[] = new PollAnswer($merge);
        }
        return $out;
    }

    protected readonly string $htmlQuestion;
    protected readonly string $htmlQuestionTelegram;

    /**
     * Get an HTML version of the question.
     *
     * @psalm-suppress InaccessibleProperty
     *
     * @param bool $allowTelegramTags Whether to allow telegram-specific tags like tg-spoiler, tg-emoji, mention links and so on...
     */
    public function getQuestionHTML(bool $allowTelegramTags = false): string
    {
        if (!$this->questionEntities) {
            return StrTools::htmlEscape($this->question);
        }
        if ($allowTelegramTags) {
            return $this->htmlQuestionTelegram ??= StrTools::entitiesToHtml($this->question, $this->questionEntities, $allowTelegramTags);
        }
        return $this->htmlQuestion ??= StrTools::entitiesToHtml($this->question, $this->questionEntities, $allowTelegramTags);
    }

    /** @internal */
    public function jsonSerialize(): mixed
    {
        $res = ['_' => static::class];
        $refl = new ReflectionClass($this);
        foreach ($refl->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $res[$prop->getName()] = $prop->getValue($this);
        }
        return $res;
    }
}
