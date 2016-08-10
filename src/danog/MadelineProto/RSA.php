<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with the MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

class RSA extends TL\TL
{
    public $key; // phpseclib\Crypt\RSA class
    public $n; // phpseclib\Math\BigInteger class
    public $e; // phpseclib\Math\BigInteger class
    public $fp; // phpseclib\Math\BigInteger class
    public $fp_float; // float

    public function __construct($key)
    {
        $this->key = new \phpseclib\Crypt\RSA();
        $this->key->load($key);
        $this->n = $this->key->modulus;
        $this->e = $this->key->exponent;

        $this->fp = new \phpseclib\Math\BigInteger(strrev(substr(sha1($this->serialize_param('bytes', $this->n->toBytes()).$this->serialize_param('bytes', $this->e->toBytes()), true), -8)), -256);
        $this->fp_float = (float) $this->fp->toString();
    }

    public function encrypt($data)
    {
        $bigintdata = new \phpseclib\Math\BigInteger($data, 256);

        return $bigintdata->powMod($this->e, $this->n)->toBytes();
    }
}
