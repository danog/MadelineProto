<?php
/**
 * VoIPServerConfig.
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

if (\class_exists('\\danog\\MadelineProto\\VoIPServerConfigInternal')) {
    /**
     * Manages storage of VoIP server config.
     */
    class VoIPServerConfig extends VoIPServerConfigInternal
    {
        /**
         * The configuration.
         *
         * @var array
         */
        private static $_config = [];
        /**
         * The default configuration.
         *
         * @var array
         */
        private static $_configDefault = [];

        /**
         * Update shared call settings.
         *
         * @param array $config The settings
         *
         * @return void
         */
        public static function update(array $config)
        {
            self::$_config = $config;
            self::updateInternal(self::getFinal());
        }

        /**
         * Get shared call settings.
         *
         * @return array The settings
         */
        public static function get(): array
        {
            return self::$_config;
        }

        /**
         * Update default shared call settings.
         *
         * @param array $configDefault The settings
         *
         * @return void
         */
        public static function updateDefault(array $configDefault)
        {
            self::$_configDefault = $configDefault;
            self::updateInternal(self::getFinal());
        }

        /**
         * Get default shared call settings.
         *
         * @return array The settings
         */
        public static function getDefault(): array
        {
            return self::$_configDefault;
        }

        /**
         * Get final settings.
         *
         * @return void
         */
        public static function getFinal(): array
        {
            return \array_merge(self::$_configDefault, self::$_config);
        }
    }
}
