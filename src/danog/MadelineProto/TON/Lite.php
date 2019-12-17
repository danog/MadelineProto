<?php

/**
 * TON API module.
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

namespace danog\MadelineProto\TON;

use danog\MadelineProto\Logger;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\Tools;

use function Amp\File\get;

/**
 * TON API.
 */
class Lite
{
    /**
     * Lite client config.
     *
     * @var array
     */
    private $config;
    /**
     * Misc settings.
     *
     * @var array
     */
    private $settings = [];
    /**
     * TL serializer instance.
     *
     * @var \danog\MadelineProto\TL\TL
     */
    private $TL;
    /**
     * Logger instance.
     *
     * @var Logger
     */
    public $logger;
    /**
     * Liteserver connections.
     *
     * @var ADNLConnection[]
     */
    private $connections = [];
    /**
     * Construct settings.
     *
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->logger = Logger::getLoggerFromSettings($this->settings);
        $this->TL = new TL($this);
        $this->TL->init(
            [
                'lite_api' => __DIR__.'/schemes/lite_api.tl',
                'ton_api' => __DIR__.'/schemes/ton_api.tl',
            ]
        );
    }
    /**
     * Connect to the lite endpoints specified in the config file.
     *
     * @param string $config Path to config file
     *
     * @return \Generator
     */
    public function connect(string $config): \Generator
    {
        $config = \json_decode(yield get($config), true);
        $config['_'] = 'liteclient.config.global';
        $config = Tools::convertJsonTL($config);
        $config['validator']['init_block'] = $config['validator']['init_block'] ?? $config['validator']['zero_state'];

        $this->config = yield $this->TL->deserialize(
            yield $this->TL->serializeObject(
                ['type' => ''],
                $config,
                'cleanup'
            )
        );

        foreach ($this->config['liteservers'] as $lite) {
            $this->connections[] = $connection = new ADNLConnection($this->TL);
            yield $connection->connect($lite);
        }
    }

    public function methodCall(string $methodName, array $args = [], array $aargs = []) {
        $data = yield $this->TL->serializeMethod($methodName, $args);
        $data = yield $this->TL->serializeMethod('liteServer.query', ['data' => $data]);
        return yield $this->connections[rand(0, count($this->connections) - 1)]->query($data);
    }

    /**
     * Asynchronously run async callable.
     *
     * @param callable $func Function
     *
     * @return \Generator
     */
    public function loop(callable $func): \Generator
    {
        return yield $func();
    }

    /**
     * Convert parameters.
     *
     * @param array $parameters Parameters
     *
     * @return void
     */
    public function botAPItoMTProto(array $parameters)
    {
        return $parameters;
    }
    
    /**
     * Get TL method namespaces
     *
     * @return void
     */
    public function getMethodNamespaces()
    {
        return $this->TL->getMethodNamespaces();
    }
}
