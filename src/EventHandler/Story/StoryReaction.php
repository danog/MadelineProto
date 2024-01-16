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

namespace danog\MadelineProto\EventHandler\Story;

use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\MTProto;

/**
 * Represents a reaction to a story.
 */
final class StoryReaction extends Update
{
    /** ID of the sender of the story */
    public readonly int $senderId;

    /** Story ID */
    public readonly int $id;

    public readonly int|string|null $reaction;

    /** @internal */
    public function __construct(MTProto $API, array $rawStory)
    {
        parent::__construct($API);
        $this->senderId = $API->getIdInternal($rawStory);
        $this->id = $rawStory['story_id'];
        $this->reaction = $rawStory['reaction']['emoticon'] ?? $rawStory['reaction']['document_id'] ?? null;
    }
}
