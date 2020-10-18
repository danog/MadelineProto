<?php

/**
 * EventHandler module.
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

namespace danog\MadelineProto;

use danog\MadelineProto\Db\DbPropertiesTrait;

/**
 * Event handler.
 */
abstract class EventHandler extends InternalDoc
{
    use DbPropertiesTrait {
        DbPropertiesTrait::initDb as private internalInitDb;
    }
    /**
     * Whether the event handler was started.
     */
    private bool $startedInternal = false;
    /**
     * API instance.
     */
    protected MTProto $API;
    public function __construct($API) // BC
    {
    }
    /**
     * Internal constructor.
     *
     * @internal
     *
     * @param APIWrapper $MadelineProto MadelineProto instance
     *
     * @return void
     */
    public function initInternal(APIWrapper $MadelineProto): void
    {
        self::link($this, $MadelineProto->getFactory());
        $this->API =& $MadelineProto->getAPI();
        foreach ($this->API->getMethodNamespaces() as $namespace) {
            $this->{$namespace} = $this->exportNamespace($namespace);
        }
    }
    /**
     * Start method handler.
     *
     * @internal
     *
     * @return \Generator
     */
    public function startInternal(): \Generator
    {
        if ($this->startedInternal) {
            return;
        }
        if (isset(static::$dbProperties)) {
            yield from $this->internalInitDb($this->API);
        }
        if (\method_exists($this, 'onStart')) {
            yield $this->onStart();
        }
        $this->startedInternal = true;
    }
    /**
     * Get peers where to send error reports.
     *
     * @return array|string|int
     */
    public function getReportPeers()
    {
        return [];
    }

    /**
     * Get API instance.
     *
     * @return MTProto
     */
    public function getAPI(): MTProto
    {
        return $this->API;
    }
}
