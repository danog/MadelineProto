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
use danog\MadelineProto\Doc\MethodDoc;

/**
 * Event handler.
 */
abstract class EventHandler extends MethodDoc
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
    /**
     * @deprecated Please don't override the constructor.
     * @internal Please don't override the constructor.
     */
    public function __construct($API) // BC
    {
    }

    /**
     * Sleep function.
     *
     * @return array
     */
    public function __sleep()
    {
        return [];
    }

    /**
     * Start MadelineProto and the event handler (enables async).
     *
     * Also initializes error reporting, catching and reporting all errors surfacing from the event loop.
     *
     * @param string $session Session name
     * @param SettingsAbstract $settings Settings
     *
     * @return void
     */
    final public static function startAndLoop(string $session, SettingsAbstract $settings): void
    {
        $API = new API($session, $settings);
        $API->startAndLoop(static::class);
    }
    /**
     * Internal constructor.
     *
     * @internal
     *
     * @param APIWrapper $MadelineProto MadelineProto wrapper
     *
     * @return void
     */
    final public function initInternal(APIWrapper $MadelineProto): void
    {
        $this->initProxyNamespaces($MadelineProto);
    }
    /**
     * Start method handler.
     *
     * @internal
     *
     * @return \Generator
     */
    final public function startInternal(): \Generator
    {
        if ($this->startedInternal) {
            return;
        }
        if (isset(static::$dbProperties)) {
            yield from $this->internalInitDb($this->wrapper->getApi());
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
}
