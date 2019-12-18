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
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use danog\MadelineProto\TL\TL;

/**
 * RSA class.
 */
class RSA
{
    use \danog\MadelineProto\Tools;
    use \danog\Serializable;
    /**
     * Exponent.
     *
     * @var \tgseclib\Math\BigInteger
     */
    public $e;
    /**
     * Modulus.
     *
     * @var \tgseclib\Math\BigInteger
     */
    public $n;
    /**
     * Fingerprint.
     *
     * @var string
     */
    public $fp;

    /**
     * Load RSA key.
     *
     * @param TL     $TL      TL serializer
     * @param string $rsa_key RSA key
     *
     * @return \Generator<self>
     */
    public function load(TL $TL, string $rsa_key): \Generator
    {
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['rsa_init'], Logger::ULTRA_VERBOSE);
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['loading_key'], Logger::ULTRA_VERBOSE);
        $key = \tgseclib\Crypt\RSA::load($rsa_key);
        $this->n = Tools::getVar($key, 'modulus');
        $this->e = Tools::getVar($key, 'exponent');
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['computing_fingerprint'], Logger::ULTRA_VERBOSE);
        $this->fp = \substr(\sha1((yield $TL->serializeObject(['type' => 'bytes'], $this->n->toBytes(), 'key')).(yield $TL->serializeObject(['type' => 'bytes'], $this->e->toBytes(), 'key')), true), -8);

        return $this;
    }

    /**
     * Sleep function.
     *
     * @return array
     */
    public function __sleep(): array
    {
        return ['e', 'n', 'fp'];
    }

    /**
     * Encrypt data.
     *
     * @param string $data Data to encrypt
     *
     * @return string
     */
    public function encrypt($data): string
    {
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['rsa_encrypting'], Logger::VERBOSE);

        return (new \tgseclib\Math\BigInteger((string) $data, 256))->powMod($this->e, $this->n)->toBytes();
    }
}
