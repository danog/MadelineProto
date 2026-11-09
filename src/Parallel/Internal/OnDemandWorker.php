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
use Amp\Future;
use Amp\Parallel\Context\StatusError;
use Amp\Parallel\Worker\Execution;
use Amp\Parallel\Worker\Task;
use Amp\Parallel\Worker\Worker;
use Closure;
use danog\MadelineProto\Logger;
use Revolt\EventLoop;
use Throwable;

use function Amp\async;

/**
 * A worker of the pool installed by {@see \danog\MadelineProto\Parallel\WebContextFactory::install()}, whose
 * context is started for its first task and, if it's a web context, stopped once it's been idle for a while, to be
 * started again for the next task (a process context keeps running, as in amphp's default pool).
 *
 * A web context occupies a worker of the webserver (a PHP-FPM child) for as long as it runs, and a worker of a pool
 * runs until the pool is shut down: the pool of a long-running script (an IPC server) would occupy its workers
 * forever. A ContextWorkerPool can't stop its idle workers, and reports a worker that exits on its own as crashed (a
 * warning, which MadelineProto's error handler throws in the event loop): this worker stays running for the pool
 * while its context is stopped, also when the webserver ends the request of its context (its time limit).
 *
 * @internal
 */
final class OnDemandWorker implements Worker
{
    use ForbidCloning;
    use ForbidSerialization;

    private ?Worker $worker = null;

    /** Whether the worker runs in a web context, and is stopped when idle. */
    private bool $stopWhenIdle = false;

    /** @var Future<Worker>|null */
    private ?Future $starting = null;

    /** Tasks submitted and not done yet. */
    private int $running = 0;

    private ?string $idleTimer = null;

    private bool $stopped = false;

    /**
     * @param Closure(): array{Worker, bool} $factory     Starts the worker running the tasks, and tells whether it
     *                                                    runs in a web context (stopped when idle).
     * @param float                          $idleTimeout Seconds after which an idle worker in a web context is stopped.
     */
    public function __construct(
        private readonly Closure $factory,
        private readonly float $idleTimeout,
    ) {
    }

    #[\Override]
    public function isRunning(): bool
    {
        return !$this->stopped;
    }

    #[\Override]
    public function isIdle(): bool
    {
        return $this->running === 0;
    }

    #[\Override]
    public function submit(Task $task, ?Cancellation $cancellation = null): Execution
    {
        if ($this->stopped) {
            throw new StatusError('The worker has been shut down');
        }
        $this->running++;
        $this->cancelIdleTimer();
        try {
            $execution = $this->getWorker($cancellation)->submit($task, $cancellation);
        } catch (Throwable $e) {
            $this->done();
            throw $e;
        }
        $execution->getFuture()->finally($this->done(...))->ignore();
        return $execution;
    }

    /**
     * The running worker, started if needed.
     */
    private function getWorker(?Cancellation $cancellation): Worker
    {
        if ($this->worker?->isRunning()) {
            return $this->worker;
        }
        $this->worker = null;
        $this->starting ??= async(function (): Worker {
            try {
                [$worker, $stopWhenIdle] = ($this->factory)();
            } finally {
                $this->starting = null;
            }
            if ($this->stopped) {
                $worker->kill();
                throw new StatusError('The worker has been shut down');
            }
            $this->worker = $worker;
            $this->stopWhenIdle = $stopWhenIdle;
            // The submit() that started it may have been cancelled meanwhile: done() found no worker to stop
            $this->armIdleTimer();
            return $worker;
        });
        return $this->starting->await($cancellation);
    }

    private function done(): void
    {
        $this->running--;
        $this->armIdleTimer();
    }

    /**
     * Stop the worker once it's been idle for idleTimeout seconds, if it's idle now and runs in a web context.
     */
    private function armIdleTimer(): void
    {
        if ($this->running === 0
            && $this->stopWhenIdle
            && !$this->stopped
            && $this->worker !== null
            && $this->idleTimer === null
        ) {
            $this->idleTimer = EventLoop::unreference(EventLoop::delay($this->idleTimeout, function (): void {
                $this->idleTimer = null;
                $worker = $this->worker;
                $this->worker = null;
                if ($worker?->isRunning()) {
                    Logger::log('Stopping an idle worker of amphp/parallel');
                    self::shutdownWorker($worker);
                }
            }));
        }
    }

    private function cancelIdleTimer(): void
    {
        if ($this->idleTimer !== null) {
            EventLoop::cancel($this->idleTimer);
            $this->idleTimer = null;
        }
    }

    private static function shutdownWorker(Worker $worker): void
    {
        try {
            $worker->shutdown();
        } catch (Throwable $e) {
            Logger::log("Stopping a worker of amphp/parallel failed: $e", Logger::ERROR);
        }
    }

    #[\Override]
    public function shutdown(): void
    {
        $this->stopped = true;
        $this->cancelIdleTimer();
        if ($this->starting !== null) {
            try {
                $this->starting->await();
            } catch (Throwable) {
                // Stopped once started
            }
        }
        if ($this->worker !== null) {
            $worker = $this->worker;
            $this->worker = null;
            if ($worker->isRunning()) {
                self::shutdownWorker($worker);
            }
        }
    }

    #[\Override]
    public function kill(): void
    {
        $this->stopped = true;
        $this->cancelIdleTimer();
        $this->worker?->kill();
        $this->worker = null;
    }
}
