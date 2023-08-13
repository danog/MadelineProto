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

namespace danog\Loop\Generic {
    if (\defined('MADELINE_POLYFILLED_LOOP')) {
        return;
    }

    \define('MADELINE_POLYFILLED_LOOP', true);

    use danog\Loop\GenericLoop as LoopGenericLoop;
    use danog\Loop\PeriodicLoop as LoopPeriodicLoop;
    use danog\MadelineProto\Tools;
    use Generator;

    /**
     * @deprecated Please use danog\Loop\PeriodicLoop instead
     */
    class PeriodicLoop extends LoopPeriodicLoop
    {
        public function __construct(callable $callback, string $name, ?int $interval)
        {
            if ($callback instanceof \Closure) {
                try {
                    $callback = $callback->bindTo($this);
                } catch (\Throwable) {
                    // Might cause an error for wrapped object methods
                }
            }
            /** @psalm-suppress InvalidArgument */
            parent::__construct(
                function ($_) use ($callback) {
                    $result = $callback();
                    if ($result instanceof Generator) {
                        $result = Tools::consumeGenerator($result);
                    }
                    return $result;
                },
                $name,
                $interval ? $interval/1000 : null
            );
        }
    }

    /**
     * @deprecated Please use danog\Loop\GenericLoop instead
     */
    class GenericLoop extends LoopGenericLoop
    {
        public function __construct(callable $callback, string $name)
        {
            if ($callback instanceof \Closure) {
                try {
                    $callback = $callback->bindTo($this);
                } catch (\Throwable) {
                    // Might cause an error for wrapped object methods
                }
            }
            /** @psalm-suppress InvalidArgument */
            parent::__construct(
                function ($_) use ($callback) {
                    $result = $callback();
                    if ($result instanceof Generator) {
                        $result = Tools::consumeGenerator($result);
                    }
                    if (\is_int($result) || \is_float($result)) {
                        $result /= 1000;
                    }
                    return $result;
                },
                $name
            );
        }
    }
}
