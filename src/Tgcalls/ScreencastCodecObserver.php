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

// IMPORTANT NOTE: Please keep the above copyright notice intact if copying or rewriting this file in another language.

namespace danog\MadelineProto\Tgcalls;

/**
 * Bridges the presentation playlist's {@see VideoCodecObserver} events to the {@see Controller}'s
 * screencast handling, so the one-to-one Controller can observe the codecs of both its camera playlist
 * (as the observer itself) and its separate presentation playlist (through this adapter).
 *
 * A dedicated named class rather than a closure, because — like the {@see Controller} — it is part of
 * the call's serializable state and must survive a serialize/unserialize cycle.
 *
 * @internal
 * @psalm-mutable
 */
final class ScreencastCodecObserver implements VideoCodecObserver
{
    public function __construct(private readonly Controller $controller)
    {
    }

    /**
     * @param array<string, string> $parameters
     * @psalm-impure
     */
    #[\Override]
    public function onVideoCodec(string $codec, array $parameters = []): void
    {
        $this->controller->onScreencastCodec($codec, $parameters);
    }

    /**
     * @psalm-impure
     */
    #[\Override]
    public function onVideoStopped(): void
    {
        $this->controller->onScreencastStopped();
    }
}
