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
use Amp\CancelledException;
use Amp\CompositeCancellation;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Amp\Parallel\Context\Context;
use Amp\Parallel\Context\ContextFactory;
use Amp\Parallel\Context\DefaultContextFactory;
use Amp\TimeoutCancellation;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Parallel\WebContextFactory;
use Throwable;
use ValueError;

/**
 * The context factory installed by {@see WebContextFactory::install()} under a webserver: it starts processes, like
 * amphp/parallel does, and falls back to requests to the webserver ({@see WebContextFactory}) where processes can't
 * work, like the IPC server of MadelineProto (AbstractServer::startMe()).
 *
 * Processes can't work without proc_open, or without a PHP binary that can run them: none in the PATH of the
 * webserver, or one of another version or without the extensions. Such a process exits or hangs without ever
 * connecting, and amphp/parallel waits for it forever: it gets PROCESS_TIMEOUT seconds.
 *
 * Once a process couldn't be started, every context is a web context.
 *
 * @internal
 */
final class FallbackContextFactory implements ContextFactory
{
    use ForbidCloning;
    use ForbidSerialization;

    /**
     * Seconds a process has to connect back.
     */
    public const PROCESS_TIMEOUT = 10;

    private bool $useWeb = false;

    public function __construct(
        private readonly ContextFactory $processFactory = new DefaultContextFactory(),
        private readonly ContextFactory $webFactory = new WebContextFactory(),
    ) {
    }

    /**
     * Whether contexts are started with requests to the webserver.
     */
    public function usesWeb(): bool
    {
        return $this->useWeb;
    }

    #[\Override]
    public function start(string|array $script, ?Cancellation $cancellation = null): Context
    {
        if (!$this->useWeb) {
            $timeout = new TimeoutCancellation(self::PROCESS_TIMEOUT);
            try {
                return $this->processFactory->start(
                    $script,
                    $cancellation ? new CompositeCancellation($cancellation, $timeout) : $timeout,
                );
            } catch (ValueError $e) {
                // An invalid script
                throw $e;
            } catch (CancelledException $e) {
                $cancellation?->throwIfRequested();
            } catch (Throwable $e) {
            }
            Logger::log(
                "Could not start a process for a context of amphp/parallel, starting them with requests to the webserver from now on: $e",
                Logger::WARNING,
            );
            $this->useWeb = true;
        }
        return $this->webFactory->start($script, $cancellation);
    }
}
