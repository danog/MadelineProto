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

namespace danog\MadelineProto\Parallel\Internal;

use Amp\Cancellation;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Amp\Parallel\Worker\Worker;
use Amp\Parallel\Worker\WorkerFactory;

use function Amp\Parallel\Worker\workerFactory;

/**
 * Creates {@see OnDemandWorker}s, which start their workers with the global worker factory of amphp/parallel.
 *
 * @internal
 */
final class OnDemandWorkerFactory implements WorkerFactory
{
    use ForbidCloning;
    use ForbidSerialization;

    /**
     * @param float $idleTimeout Seconds after which an idle worker is stopped.
     */
    public function __construct(
        private readonly float $idleTimeout,
    ) {
    }

    #[\Override]
    public function create(?Cancellation $cancellation = null): Worker
    {
        return new OnDemandWorker(static fn (): Worker => workerFactory()->create(), $this->idleTimeout);
    }
}
