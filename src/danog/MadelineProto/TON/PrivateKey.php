<?php
/**
 * TON public key module.
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

use phpseclib3\Crypt\EC\Curves\Curve25519;
use phpseclib3\Crypt\EC\PrivateKey as ECPrivateKey;

class PrivateKey extends ECPrivateKey
{
    public static function load($key, $password = false)
    {
        self::initialize_static_variables();

        $components = false;

        $curve = new Curve25519;
        $peerPublic = Common::extractPoint($key, $curve);
        
        $components['format'] = 'TON';
        $components['curve'] = $curve;

        $new = static::onLoad($components);
        return $new;
    }
}