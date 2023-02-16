<?php

declare(strict_types=1);

namespace danog\MadelineProto\MTProtoTools;

use Amp\Sync\LocalMutex;
use danog\MadelineProto\DataCenter;
use danog\MadelineProto\Logger;
use phpseclib3\Math\BigInteger;

use function Amp\async;
use function Amp\Future\await;

/**
 * @property DataCenter $datacenter
 *
 * @internal
 */
trait AuthKeyHandler
{
    private ?LocalMutex $auth_mutex = null;
    /**
     * Asynchronously create, bind and check auth keys for all DCs.
     *
     * @internal
     */
    public function initAuthorization(): void
    {
        $this->auth_mutex ??= new LocalMutex;
        $lock = $this->auth_mutex->acquire();
        $this->logger('Initing authorization...');
        $this->initing_authorization = true;
        try {
            $main = [];
            $media = [];
            foreach ($this->datacenter->getDataCenterConnections() as $socket) {
                if (!$socket->hasCtx()) {
                    continue;
                }
                if ($socket->isMedia()) {
                    $media []= $socket->initAuthorization(...);
                } else {
                    $main []= $socket->initAuthorization(...);
                }
            }
            if ($main) {
                \array_shift($main)();
            }
            await(\array_map(async(...), $main));
            await(\array_map(async(...), $media));
        } finally {
            $lock->release();
            $this->logger('Done initing authorization!');
            $this->initing_authorization = false;
        }
        $this->startUpdateSystem(true);
    }
    /**
     * Get diffie-hellman configuration.
     */
    public function getDhConfig(): array
    {
        $dh_config = $this->methodCallAsyncRead('messages.getDhConfig', ['version' => $this->dh_config['version'], 'random_length' => 0]);
        if ($dh_config['_'] === 'messages.dhConfigNotModified') {
            $this->logger->logger('DH configuration not modified', Logger::VERBOSE);
            return $this->dh_config;
        }
        $dh_config['p'] = new BigInteger((string) $dh_config['p'], 256);
        $dh_config['g'] = new BigInteger($dh_config['g']);
        Crypt::checkPG($dh_config['p'], $dh_config['g']);
        return $this->dh_config = $dh_config;
    }
}
