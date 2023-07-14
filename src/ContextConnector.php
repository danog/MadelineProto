<?php

declare(strict_types=1);

/**
 * Proxying AMPHP connector.
 *
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

namespace danog\MadelineProto;

use Amp\Cancellation;
use Amp\Socket\ConnectContext;
use Amp\Socket\Socket;
use Amp\Socket\SocketAddress;
use Amp\Socket\SocketConnector;
use Throwable;

/** @internal */
final class ContextConnector implements SocketConnector
{
    public function __construct(private DoHWrapper $doHWrapper, private LoggerGetter $loggerGetter, private bool $fromDns = false)
    {
    }
    public function connect(SocketAddress|string $uri, ?ConnectContext $context = null, ?Cancellation $cancellation = null): Socket
    {
        $ctx = $context ?? new ConnectContext();
        $ctxs = $this->doHWrapper->generateContexts($uri, $ctx);
        if (empty($ctxs)) {
            throw new Exception("No contexts for raw connection to URI {$uri}");
        }
        $logger = $this->loggerGetter->getLogger();
        foreach ($ctxs as $ctx) {
            try {
                $ctx->setIsDns($this->fromDns);
                if ($cancellation) {
                    $ctx->setCancellation($cancellation);
                }
                $result = ($ctx->getStream());
                $logger->logger('OK!', Logger::WARNING);
                return $result->getSocket();
            } catch (Throwable $e) {
                if (\defined('MADELINEPROTO_TEST') && \constant('MADELINEPROTO_TEST') === 'pony') {
                    throw $e;
                }
                $logger->logger('Connection failed: '.$e, Logger::ERROR);
            }
        }
        throw new Exception("Could not connect to URI {$uri}");
    }
}
