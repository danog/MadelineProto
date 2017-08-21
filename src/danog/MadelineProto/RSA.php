<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with the MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
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

    public function ___construct($rsa_key)
    {
        //if ($this->unserialized($rsa_key)) return true;
        \danog\MadelineProto\Logger::log(['Istantiating \phpseclib\Crypt\RSA...'], Logger::ULTRA_VERBOSE);
        $key = new \phpseclib\Crypt\RSA();

        \danog\MadelineProto\Logger::log(['Loading key...'], Logger::ULTRA_VERBOSE);
        $key->load($rsa_key);
        $this->n = \phpseclib\Common\Functions\Objects::getVar($key, 'modulus');
        $this->e = \phpseclib\Common\Functions\Objects::getVar($key, 'exponent');

        \danog\MadelineProto\Logger::log(['Computing fingerprint...'], Logger::ULTRA_VERBOSE);
        $this->fp = substr(
            sha1(
                $this->serialize_object(
                    ['type' => 'bytes'],
                    $this->n->toBytes(), 'key'
                )
                .
                $this->serialize_object(
                    ['type' => 'bytes'],
                    $this->e->toBytes(), 'key'
                ),
                true
            ),
            -8
        );

        return true;
    }

    public function __sleep()
    {
        return ['e', 'n', 'fp'];
    }

    public function encrypt($data)
    {
        \danog\MadelineProto\Logger::log(['Encrypting with rsa key...'], Logger::VERBOSE);

        return (new \phpseclib\Math\BigInteger($data, 256))->powMod($this->e, $this->n)->toBytes();
    }
}
