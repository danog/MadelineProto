<?php

namespace danog\MadelineProto;

// by https://github.com/mgp25
class TelegramEncryption
{
    public $key;
    public $iv;
    public $debug;
    public $rijndael;

    public function __construct($key, $iv, $debug = false)
    {
        $this->key = $key;
        $this->iv = $iv;
        $this->debug = $debug;
        $this->rijndael = new \phpseclib\Crypt\Rijndael(\phpseclib\Crypt\Rijndael::MODE_ECB);
        $this->rijndael->setKeyLength(128);
        $this->rijndael->setKey($this->key);
    }

    public function decrypt($message = null)
    {
        $key = $this->key;
        $blockSize = $this->rijndael->block_size;

        $xPrev = substr($this->iv, 0, $blockSize);
        $yPrev = substr($this->iv, $blockSize, strlen($this->iv));

        $decrypted = '';

        for ($i = 0; $i < strlen($message); $i += $blockSize) {
            $x = substr($message, $i, $blockSize);

            $this->debugLog('x: '.$this->_c($x)."\n");

            $yXOR = $this->exor($x, $yPrev);
            $this->debugLog('yPrev: '.$this->_c($yPrev)."\n");
            $this->debugLog('yXOR: '.$this->_c($yXOR)."\n");
            $yFinal = $this->rijndael->encrypt($yXOR);
            $yFinal = str_pad($yFinal, strlen($xPrev), "\x00");
            $this->debugLog('yFinal: '.$this->_c($yFinal)."\n");

            $y = $this->exor($yFinal, $xPrev);
            $this->debugLog('xPrev: '.$this->_c($xPrev)."\n");
            $this->debugLog('y: '.$this->_c($y)."\n");

            $xPrev = $x;
            $yPrev = $y;
            $decrypted .= $y;

            $this->debugLog('Currently Decrypted: '.$this->_c($decrypted)."\n\n");
        }

        return $decrypted;
    }

    public function encrypt()
    {
        $key = $this->key;
        $message = $this->plainText;
        $blockSize = $this->rijndael->block_size;

        $xPrev = substr($this->iv, $blockSize, strlen($this->iv));
        $yPrev = substr($this->iv, 0, $blockSize);

        $encrypted = '';

        for ($i = 0; $i < strlen($message); $i += $blockSize) {
            $x = substr($message, $i, $blockSize);
            $this->debugLog('x: '.$this->_c($x)."\n");

            $yXOR = $this->exor($x, $yPrev);
            $this->debugLog('yPrev: '.$this->_c($yPrev)."\n");
            $this->debugLog('yXOR: '.$this->_c($yXOR)."\n");
            $yFinal = $this->rijndael->encrypt($yXOR);
            $yFinal = str_pad($yFinal, strlen($xPrev), "\x00");
            $this->debugLog('yFinal: '.$this->_c($yFinal)."\n");
            $y = $this->exor($yFinal, $xPrev);
            $this->debugLog('xPrev: '.$this->_c($xPrev)."\n");
            $this->debugLog('y: '.$this->_c($y)."\n");

            $xPrev = $x;
            $yPrev = $y;

            $encrypted .= $y;
            $this->debugLog('Currently encrypted: '.$this->_c($encrypted)."\n\n");
        }

        return $encrypted;
    }

    public function debugLog($message)
    {
        if ($this->debug) {
            echo $message;
        }
    }

    public function exor($array1, $array2)
    {
        $len = (strlen($array1) <= strlen($array2)) ? strlen($array2) : strlen($array1);

        $array1 = str_pad($array1, $len, "\x00");
        $array2 = str_pad($array2, $len, "\x00");

        $res = '';
        for ($i = 0; $i < $len; $i++) {
            $res .= $array1[$i] ^ $array2[$i];
        }

        return $res;
    }

    public function _c($binary)
    {
        return sprintf('[%s]', chunk_split(bin2hex($binary), 4, ' '));
    }
}
