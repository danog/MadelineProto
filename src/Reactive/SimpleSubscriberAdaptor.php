<?php

declare(strict_types=1);

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

namespace danog\MadelineProto\Reactive;

/**
 * @internal
 *
 * @template T
 *
 * @implements Subscriber<T>
 */
final class SimpleSubscriberAdaptor implements Subscriber
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        /** @var SimpleSubscriber<T> $subscriber */
        public readonly SimpleSubscriber $subscriber
    ) {
    }

    #[\Override]
    public function onAttach($initState): void
    {
        $this->subscriber->onSimpleStateChange($initState);
    }

    #[\Override]
    public function onStateChange($prevState, $state): void
    {
        $this->subscriber->onSimpleStateChange($state);
    }
}
