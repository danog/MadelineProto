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

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\MTProto;
use danog\MadelineProto\EventHandler\AbstractStory;
use danog\MadelineProto\EventHandler\AbstractMedia;
use danog\MadelineProto\EventHandler\Story\Story as UpdateStory;
use danog\MadelineProto\EventHandler\Story\StoryDeleted;

/**
 * Represents a class for non-downloadable media.
 */
abstract class Story extends AbstractMedia
{
    /** @var bool */
    public readonly bool $viaMention;

    /** @var bool */
    public readonly int $userId;

    /** @var int */
    public readonly int $storyId;

    /** @var AbstractStory */
    protected readonly ?AbstractStory $story;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
    ) {
        parent::__construct($API);
        $this->viaMention = $rawMedia['via_mention'];
        $this->userId = $API->getIdInternal($rawMedia['peer']);
        $this->storyId = $rawMedia['id'];
        $this->story = match($rawMedia['story']['_'] ?? null)
        {
            'storyItem' => new UpdateStory($API, [
                'peer' => $this->userId,
                'story' => $rawMedia['story']
            ]),
            'storyItemDeleted' => new StoryDeleted($API, [
                'peer' => $this->userId,
                'story' => $rawMedia['story']
            ]),
            'storyItemSkipped' => null,
            default => null
        };

    }

    /**
     * Get story.
     * 
     * @psalm-suppress InaccessibleProperty
     */
    public function getStory(): AbstractStory
    {
        $client = $this->getClient();
        $result = $client->methodCallAsyncRead(
            'stories.getStoriesByID',
            [
                'peer' => $this->userId,
                'id' => [ $this->storyId ],
            ]
        )['stories'][0];
        return $this->story ??= $result['_'] === 'storyItemDeleted'
            ? new StoryDeleted($client, [ 'peer' => $this->userId, 'story' => $result ])
            : new Story($client, [ 'peer' => $this->userId, 'story' => $result ]);
    }
}