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

use danog\MadelineProto\EventHandler\AbstractStory;
use danog\MadelineProto\EventHandler\Story\Story;
use danog\MadelineProto\EventHandler\Story\StoryDeleted;
use danog\MadelineProto\Ipc\IpcCapable;
use danog\MadelineProto\MTProto;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/**
 * Represents a forwarded story.
 */
abstract class MediaStory extends IpcCapable implements JsonSerializable // for now. I should think a way
{
    public readonly bool $viaMention;

    public readonly int $senderId;

    public readonly int $storyId;

    protected readonly ?AbstractStory $story;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
    ) {
        parent::__construct($API);
        $this->viaMention = $rawMedia['via_mention'];
        $this->senderId = $API->getIdInternal($rawMedia['peer']);
        $this->storyId = $rawMedia['id'];
        $this->story = match ($rawMedia['story']['_'] ?? null) {
            'storyItem' => new Story($API, [
                'peer' => $this->senderId,
                'story' => $rawMedia['story'],
            ]),
            'storyItemDeleted' => new StoryDeleted($API, [
                'peer' => $this->senderId,
                'story' => $rawMedia['story'],
            ]),
            'storyItemSkipped' => null, // Will it happen?
            default => null
        };
    }

    /**
     * Get story.
     *
     * @psalm-suppress InaccessibleProperty
     * @return ?AbstractStory
     */
    public function getStory(): ?AbstractStory
    {
        $client = $this->getClient();
        $result = $client->methodCallAsyncRead(
            'stories.getStoriesByID',
            [
                'peer' => $this->senderId,
                'id' => [ $this->storyId ],
            ]
        )['stories'][0] ?? false;
        if ($result) {
            $arr = [ 'peer' => $this->senderId, 'story' => $result ];
            return $this->story ??= $result['_'] === 'storyItem' // I hope storyItemSkipped never happen
                ? new StoryDeleted($client, $arr)
                : new Story($client, $arr);
        }
        return null;
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
