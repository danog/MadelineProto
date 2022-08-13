<?php

namespace danog\MadelineProto\MTProtoTools;

use Closure;
use danog\MadelineProto\DataCenter;
use danog\MadelineProto\Tools;
use tgseclib\Math\BigInteger;

/**
 * @property DataCenter $datacenter
 */
trait AuthKeyHandler
{
    /**
     * Whether another initAuthorization is pending
     *
     * @var boolean
     */
    private $pending_auth = false;
    
    /**
     * Asynchronously create, bind and check auth keys for all DCs.
     *
     * @internal
     *
     * @return \Generator
     */
    public function initAuthorization(): \Generator
    {
        if ($this->initing_authorization) {
            if ($this->pending_auth) {
                $this->logger("Pending auth check, not queueing further auth check");
                return;
            }
            $this->logger("Queueing further auth check");
            $this->pending_auth = true;
            return;
        }
        $this->logger("Initing authorization...");
        $this->initing_authorization = true;
        try {
            do {
                $this->pending_auth = false;
                $main = [];
                $media = [];
                foreach ($this->datacenter->getDataCenterConnections() as $socket) {
                    if (!$socket->hasCtx()) {
                        continue;
                    }
                    if ($socket->isMedia()) {
                        $media []= [$socket, 'initAuthorization'];
                    } else {
                        $main []= [$socket, 'initAuthorization'];
                    }
                }
                if ($main) {
                    $first = \array_shift($main)();
                    yield from $first;
                }
                yield Tools::all(array_map(fn ($cb) => $cb(), $main));
                yield Tools::all(array_map(fn ($cb) => $cb(), $media));
            } while ($this->pending_auth);
        } finally {
            $this->logger("Done initing authorization!");
            $this->initing_authorization = false;
        }
    }
    /**
     * Get diffie-hellman configuration.
     *
     * @return \Generator<array>
     */
    public function getDhConfig(): \Generator
    {
        $dh_config = yield from $this->methodCallAsyncRead('messages.getDhConfig', ['version' => $this->dh_config['version'], 'random_length' => 0]);
        if ($dh_config['_'] === 'messages.dhConfigNotModified') {
            $this->logger->logger('DH configuration not modified', \danog\MadelineProto\Logger::VERBOSE);
            return $this->dh_config;
        }
        $dh_config['p'] = new BigInteger((string) $dh_config['p'], 256);
        $dh_config['g'] = new BigInteger($dh_config['g']);
        Crypt::checkPG($dh_config['p'], $dh_config['g']);
        return $this->dh_config = $dh_config;
    }
}
