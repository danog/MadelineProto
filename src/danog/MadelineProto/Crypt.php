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

class Crypt
{
    public static function ige_encrypt($message, $key, $iv)
    {
        return self::_ige($message, $key, $iv, 'encrypt');
    }

    public static function ige_decrypt($message, $key, $iv)
    {
        return self::_ige($message, $key, $iv, 'decrypt');
    }

    /**
     * Given a key, given an iv, and message
     * do whatever operation asked in the operation field.
     * Operation will be checked for: "decrypt" and "encrypt" strings.
     * Returns the message encrypted/decrypted.
     * message must be a multiple by 16 bytes (for division in 16 byte blocks)
     * key must be 32 byte
     * iv must be 32 byte (it's not internally used in AES 256 ECB, but it's
     * needed for IGE).
     */
    public static function _ige($message, $key, $iv, $operation = 'decrypt')
    {
        if (strlen($key) != 32) {
            throw new Exception('key must be 32 bytes long (was '.strlen($key).' bytes)');
        }
        if (strlen($iv) != 32) {
            throw new Exception('iv must be 32 bytes long (was '.strlen($iv).' bytes)');
        }
        $cipher = new \phpseclib\Crypt\AES(\phpseclib\Crypt\AES::MODE_ECB);
        $cipher->setKey($key);
        $cipher->paddable = false;
        $blocksize = $cipher->block_size;
        if ((strlen($message) % $blocksize) != 0) {
            throw new Exception('message must be a multiple of 16 bytes (try adding '.(16 - (strlen($message) % 16)).' bytes of padding)');
        }
        $ivp = substr($iv, 0, $blocksize);
        $ivp2 = substr($iv, $blocksize);
        $ciphered = '';
        foreach (Tools::range(0, strlen($message), $blocksize) as $i) {
            $indata = substr($message, $i, $blocksize);
            if ($operation == 'decrypt') {
                $xored = $indata ^ $ivp2;
                $decrypt_xored = $cipher->decrypt($xored);
                $outdata = $decrypt_xored ^ $ivp;
                $ivp = $indata;
                $ivp2 = $outdata;
            } elseif ($operation == 'encrypt') {
                $xored = $indata ^ $ivp;
                $encrypt_xored = $cipher->encrypt($xored);
                $outdata = $encrypt_xored ^ $ivp2;
                $ivp = $outdata;
                $ivp2 = $indata;
            } else {
                throw new Exception('Crypt: operation must be either \'decrypt\' or \'encrypt\'');
            }
            $ciphered .= $outdata;
        }

        return $ciphered;
    }
}
