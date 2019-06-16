<?php
/**
 * RSA module.
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
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

class RSA
{
    use \danog\MadelineProto\TL\TL;
    use \danog\MadelineProto\Tools;
    use \danog\Serializable;
    public $e;
    public $n;
    public $fp;

    public function load($rsa_key)
    {
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['rsa_init'], Logger::ULTRA_VERBOSE);
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['loading_key'], Logger::ULTRA_VERBOSE);
        $key = \phpseclib\Crypt\RSA::load($rsa_key);
        $this->n = self::getVar($key, 'modulus');
        $this->e = self::getVar($key, 'exponent');
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['computing_fingerprint'], Logger::ULTRA_VERBOSE);
        $this->fp = substr(sha1((yield $this->serialize_object_async(['type' => 'bytes'], $this->n->toBytes(), 'key')).(yield $this->serialize_object_async(['type' => 'bytes'], $this->e->toBytes(), 'key')), true), -8);

        return $this;
    }

    public function __sleep()
    {
        return ['e', 'n', 'fp'];
    }

    public function encrypt($data)
    {
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['rsa_encrypting'], Logger::VERBOSE);

        return (new \phpseclib\Math\BigInteger($data, 256))->powMod($this->e, $this->n)->toBytes();
    }
    /**
     * Accesses a private variable from an object
     *
     * @param object $obj
     * @param string $var
     * @return mixed
     * @access public
     */
    public static function getVar($obj, $var)
    {
        $reflection = new \ReflectionClass(get_class($obj));
        $prop = $reflection->getProperty($var);
        $prop->setAccessible(true);
        return $prop->getValue($obj);
    }
}
