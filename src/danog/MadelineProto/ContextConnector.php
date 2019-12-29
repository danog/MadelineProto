<?php

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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\CancellationToken;
use Amp\MultiReasonException;
use Amp\NullCancellationToken;
use Amp\Promise;
use Amp\Socket\ConnectContext;
use Amp\Socket\Connector;

class ContextConnector implements Connector
{
    private $dataCenter;
    private $logger;
    private $fromDns = false;
    public function __construct(DataCenter $dataCenter, bool $fromDns = false)
    {
        $this->dataCenter = $dataCenter;
        $this->fromDns = $fromDns;
        $this->logger = $dataCenter->getAPI()->getLogger();
    }

    public function connect(string $uri, ?ConnectContext $ctx = null, ?CancellationToken $token = null): Promise
    {
        return Tools::call((function () use ($uri, $ctx, $token) {
            $ctx = $ctx ?? new ConnectContext;
            $token = $token ?? new NullCancellationToken;

            $ctxs = $this->dataCenter->generateContexts(0, $uri, $ctx);
            if (empty($ctxs)) {
                throw new Exception("No contexts for raw connection to URI $uri");
            }
            foreach ($ctxs as $ctx) {
                /* @var $ctx \danog\MadelineProto\Stream\ConnectionContext */
                try {
                    $ctx->setIsDns($this->fromDns);
                    $ctx->setCancellationToken($token);
                    $result = yield $ctx->getStream();
                    $this->logger->logger('OK!', \danog\MadelineProto\Logger::WARNING);

                    return $result->getSocket();
                } catch (\Throwable $e) {
                    if (\MADELINEPROTO_TEST === 'pony') {
                        throw $e;
                    }
                    $this->logger->logger('Connection failed: '.$e, \danog\MadelineProto\Logger::ERROR);
                    if ($e instanceof MultiReasonException) {
                        foreach ($e->getReasons() as $reason) {
                            $this->logger->logger('Multireason: '.$reason, \danog\MadelineProto\Logger::ERROR);
                        }
                    }
                }
            }

            throw new \danog\MadelineProto\Exception("Could not connect to URI $uri");
        })());
    }
}
