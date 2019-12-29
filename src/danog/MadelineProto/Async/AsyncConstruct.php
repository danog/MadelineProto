<?php
/**
 * Async constructor abstract class.
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

namespace danog\MadelineProto\Async;

use danog\MadelineProto\Tools;

/**
 * Async constructor class.
 *
 * Manages asynchronous construction and wakeup of classes
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class AsyncConstruct
{
    /**
     * Async init promise.
     *
     * @var Promise
     */
    public $asyncInitPromise;

    /**
     * Blockingly init.
     *
     * @return void
     */
    public function init(): void
    {
        if ($this->asyncInitPromise) {
            Tools::wait($this->asyncInitPromise);
        }
    }

    /**
     * Asynchronously init.
     *
     * @return \Generator
     */
    public function initAsynchronously(): \Generator
    {
        if ($this->asyncInitPromise) {
            yield $this->asyncInitPromise;
        }
    }

    /**
     * Set init promise.
     *
     * @param Promise $promise Promise
     *
     * @internal
     *
     * @return void
     */
    public function setInitPromise($promise): void
    {
        $this->asyncInitPromise = Tools::callFork($promise);
        $this->asyncInitPromise->onResolve(function ($error, $result) {
            if ($error) {
                throw $error;
            }
            $this->asyncInitPromise = null;
        });
    }
}
