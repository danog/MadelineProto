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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\VoIP;

use JsonSerializable;

/**
 * The media state of the other party of a one-to-one call, as reported by its `MediaState`
 * tgcalls signaling message.
 */
final class MediaState implements JsonSerializable
{
    /**
     * @internal
     *
     * @psalm-mutation-free
     */
    public function __construct(
        /** Whether the other party muted their microphone. */
        public readonly bool $muted,
        /** Whether the other party is transmitting a camera video stream. */
        public readonly bool $video,
        /** Whether the other party is transmitting a screen sharing stream. */
        public readonly bool $screencast,
        /** Whether the other party reported a low battery level. */
        public readonly bool $batteryLow = false,
    ) {
    }

    /**
     * @internal
     */
    public static function fromSignaling(array $message): self
    {
        $state = static fn (mixed $v): bool => \in_array($v, ['active', 'suspended'], true);
        return new self(
            (bool) ($message['muted'] ?? false),
            $state($message['videoState'] ?? 'inactive'),
            $state($message['screencastState'] ?? 'inactive'),
            (bool) ($message['isBatteryLow'] ?? false),
        );
    }

    /**
     * @internal
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'muted' => $this->muted,
            'video' => $this->video,
            'screencast' => $this->screencast,
            'batteryLow' => $this->batteryLow,
        ];
    }
}
