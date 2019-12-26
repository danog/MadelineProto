<?php

/**
 * BigInteger placeholder for deserialization.
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

namespace phpseclib\Math;

if (PHP_MAJOR_VERSION < 7 && !((\class_exists(\Phar::class) && \Phar::running()) || \defined('TESTING_VERSIONS'))) {
    throw new \Exception('MadelineProto requires php 7 to run natively, use phar.madelineproto.xyz to run on PHP 5.6');
}
if (\defined('HHVM_VERSION')) {
    $engines = [['PHP64', ['OpenSSL']], ['BCMath', ['OpenSSL']], ['PHP32', ['OpenSSL']]];
    foreach ($engines as $engine) {
        try {
            \tgseclib\Math\BigInteger::setEngine($engine[0], isset($engine[1]) ? $engine[1] : []);
            break;
        } catch (\Exception $e) {
        }
    }
}

class BigIntegor
{
}
