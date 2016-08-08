<?php

namespace danog\MadelineProto;

class RSA extends TL
{
    public $key;
    public $n;
    public $e;
    public $fp;

    public function __construct($key)
    {
        $this->key = new \phpseclib\Crypt\RSA($key);
        $this->n = $key->modulus;
        $this->e = $key->exponent;
        $this->fp = new \phpseclib\Math\BigInteger(substr(sha1($this->serialize_param('bytes', $this->n->toBytes()).$this->serialize_param('bytes', $this->e->toBytes()), true), -8), 256);
    }

    public function encrypt($data)
    {
        $bigintdata = new \phpseclib\Math\BigInteger($data, 256);

        return $bigintdata->powMod($this->e, $this->n)->toBytes();
    }
}
