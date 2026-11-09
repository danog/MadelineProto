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
use Amp\Parallel\Context\Context;
use Amp\Parallel\Context\ContextFactory;
use Amp\Parallel\Worker\ContextWorkerFactory;
use Amp\Parallel\Worker\Worker;
use Amp\Parallel\Worker\WorkerFactory;
use danog\MadelineProto\Parallel\WebContext;
use danog\MadelineProto\Parallel\WebContextFactory;

use function Amp\Parallel\Context\contextFactory;
use function Amp\Parallel\Worker\workerFactory;

/**
 * Creates {@see OnDemandWorker}s, which start their workers like amphp's default worker factory, with the global
 * context factory: only those whose context is a web context are stopped when idle. With a global worker factory set
 * by the user, which can't tell, its workers are.
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
        return new OnDemandWorker(static function (): array {
            if (WebContextFactory::isGlobalSet('Amp\\Parallel\\Worker\\workerFactory')) {
                return [workerFactory()->create(), true];
            }
            $contexts = new class(contextFactory()) implements ContextFactory {
                public bool $web = false;

                public function __construct(private readonly ContextFactory $factory)
                {
                }

                #[\Override]
                public function start(string|array $script, ?Cancellation $cancellation = null): Context
                {
                    $context = $this->factory->start($script, $cancellation);
                    $this->web = $context instanceof WebContext;
                    return $context;
                }
            };
            $worker = (new ContextWorkerFactory(contextFactory: $contexts))->create();
            return [$worker, $contexts->web];
        }, $this->idleTimeout);
    }
}
