<?php
/**
 * Shutdown module.
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

/*
 * Controls script shutdown
 */
class Shutdown
{
    /**
     * Callbacks to call on shutdown.
     *
     * @var array<callable>
     */
    private static $callbacks = [];
    /**
     * Whether the main shutdown was registered.
     *
     * @var boolean
     */
    private static $registered = false;
    /**
     * Incremental ID for new callback.
     *
     * @var integer
     */
    private static $id = 0;
    /**
     * Function to be called on shutdown.
     *
     * @return void
     */
    public static function shutdown()
    {
        foreach (self::$callbacks as $callback) {
            $callback();
        }
    }
    /**
     * Add a callback for script shutdown.
     *
     * @param callable $callback The callback to set
     * @param null|string $id The optional callback ID
     *
     * @return The callback ID
     */
    public static function addCallback($callback, $id = null)
    {
        if (!$id) {
            $id = self::$id++;
        }
        self::$callbacks[$id] = $callback;
        if (!self::$registered) {
            \register_shutdown_function([__CLASS__, 'shutdown']);
            self::$registered = true;
        }
        return $id;
    }
    /**
     * Remove a callback from the script shutdown callable list.
     *
     * @param null|string $id The optional callback ID
     *
     * @return bool true if the callback was removed correctly, false otherwise
     */
    public static function removeCallback($id)
    {
        if (isset(self::$callbacks[$id])) {
            unset(self::$callbacks[$id]);
            return true;
        }
        return false;
    }
}
