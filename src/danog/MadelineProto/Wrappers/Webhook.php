<?php

/**
 * Webhook module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Wrappers;

use Amp\Http\Client\Request;
use danog\MadelineProto\Settings;

/**
 * Manages logging in and out.
 *
 * @property Settings $settings
 */
trait Webhook
{
    /**
     * Set webhook update handler.
     *
     * @param string $hook_url Webhook URL
     * @param string $pem_path PEM path for self-signed certificate
     *
     * @return void
     */
    public function setWebhook(string $hook_url, string $pem_path = ''): void
    {
        $this->pem_path = $pem_path;
        $this->hook_url = $hook_url;
        $this->updateHandler = [$this, 'pwrWebhook'];
        $this->startUpdateSystem();
    }
    /**
     * Send update to webhook.
     *
     * @param array $update Update
     *
     * @return void
     */
    private function pwrWebhook(array $update): void
    {
        $payload = \json_encode($update);
        if ($payload === '') {
            $this->logger->logger($update, $payload, \json_last_error_msg());
            $this->logger->logger('EMPTY UPDATE');
            return;
        }
        \danog\MadelineProto\Tools::callFork((function () use ($payload): \Generator {
            $request = new Request($this->hook_url, 'POST');
            $request->setHeader('content-type', 'application/json');
            $request->setBody($payload);
            $result = yield (yield $this->datacenter->getHTTPClient()->request($request))->getBody()->buffer();
            $this->logger->logger('Result of webhook query is '.$result, \danog\MadelineProto\Logger::NOTICE);
            $result = \json_decode($result, true);
            if (\is_array($result) && isset($result['method']) && $result['method'] != '' && \is_string($result['method'])) {
                try {
                    $this->logger->logger('Reverse webhook command returned', yield from $this->methodCallAsyncRead($result['method'], $result));
                } catch (\Throwable $e) {
                    $this->logger->logger("Reverse webhook command returned: {$e}");
                }
            }
        })());
    }
}
