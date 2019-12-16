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

use danog\MadelineProto\Magic;
use danog\MadelineProto\Tools;
use phpseclib3\Crypt\EC\Curves\Curve25519;
use phpseclib3\Crypt\EC\Formats\Keys\Common;
use phpseclib3\Crypt\EC\Formats\Keys\MontgomeryPublic;
use phpseclib3\Crypt\EC\PublicKey as PPublicKey;
use phpseclib3\Math\BigInteger;

class PublicKey extends PPublicKey
{
    public static function load($key, $password = false)
    {
        self::initialize_static_variables();

        $components = false;

        // Transpose
        $key[31] = $key[31] & chr(127);

        $curve = new Curve25519;
        $modulo = Tools::getVar($curve, "modulo");
        $y = new BigInteger(strrev($key), 256);
        $y2 = clone $y;
        $y = $y->add(Magic::$one);
        $y2 = $y2->subtract(Magic::$one);
        $y2 = $modulo->subtract($y2)->powMod(Magic::$one, $modulo);

        $y2 = $y2->modInverse($modulo);

        $key = $y->multiply($y2)->powMod(Magic::$one, $modulo)->toBytes();

        $components = MontgomeryPublic::load($key);

        $components['format'] = 'TON';
        $components['curve'] = $curve;

        $new = static::onLoad($components);
        return $new;
    }
}
