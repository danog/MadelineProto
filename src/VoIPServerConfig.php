<?php

declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

if (class_exists(VoIPServerConfig::class)) {
    return;
}
/**
 * Manages storage of VoIP server config.
 *
 * @internal
 */
final class VoIPServerConfig
{
    /**
     * The configuration.
     *
     */
    private static array $_config = [];
    /**
     * The default configuration.
     *
     */
    private static array $_configDefault = [];
    /**
     * Update shared call settings.
     *
     * @param array $config The settings
     */
    public static function update(array $config): void
    {
        self::$_config = $config;
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
     */
    public static function updateDefault(array $configDefault): void
    {
        self::$_configDefault = $configDefault;
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
     */
    public static function getFinal(): array
    {
        return array_merge(self::$_configDefault, self::$_config);
    }
}
