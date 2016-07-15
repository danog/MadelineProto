<?php
// by https://github.com/mgp25
class TelegramEncryption
{
  public $key;
  public $iv;
  public $cipherText;
  public $plainText;
  public $debug;

  public function __construct($key, $iv, $cipherText = null, $plainText = null, $debug = false)
{
  $this->key = $key;
  $this->iv = $iv;
  $this->cipherText = $cipherText;
  $this->plainText = $plainText;
  $this->debug = $debug; }
public function IGE256Decrypt()
{

$key = $this->key;
$message = $this->cipherText;
$blockSize = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);

$xPrev = substr($this->iv, 0, $blockSize);
$yPrev = substr($this->iv, $blockSize, strlen($this->iv));

$decrypted = '';

for ($i=0; $i < strlen($message); $i += $blockSize)
{
  $x = substr($message, $i, $blockSize);
  $this->debugLog("x: " . _c($x) . "\n");

  $yXOR = $this->exor($x, $yPrev);
  $this->debugLog("yPrev: " . _c($yPrev) . "\n");
  $this->debugLog("yXOR: " . _c($yXOR) . "\n");
  $yFinal = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $yXOR, MCRYPT_MODE_ECB);                    $yFinal = str_pad($yFinal, strlen($xPrev), "\x00");
$this->debugLog("yFinal: " . _c($yFinal) . “\n");

$y = $this->exor($yFinal, $xPrev);
$this->debugLog("xPrev: " . _c($xPrev) . "\n");
$this->debugLog("y: " . _c($y) . "\n");

$xPrev = $x;
$yPrev = $y;
$decrypted .= $y;

$this->debugLog("Currently Decrypted: "._c($decrypted)."\n\n");
}
return $decrypted;  
}

public function IGE256Encrypt()
{
$key = $this->key;
$message = $this->plainText;
$blockSize = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);

$xPrev = substr($this->iv, $blockSize, strlen($this->iv));
$yPrev = substr($this->iv, 0, $blockSize);  

$encrypted = '';

for ($i=0; $i < strlen($message); $i += $blockSize)
{

$x = substr($message, $i, $blockSize);
$this->debugLog("x: " . _c($x) . “\n");

$yXOR = $this->exor($x, $yPrev);
$this->debugLog("yPrev: " . _c($yPrev) . "\n");
$this->debugLog("yXOR: " . _c($yXOR) . "\n");
$yFinal = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $yXOR, MCRYPT_MODE_ECB);             $yFinal = str_pad($yFinal, strlen($xPrev), "\x00");
$this->debugLog("yFinal: " . _c($yFinal) . “\n");
$y = $this->exor($yFinal, $xPrev);
$this->debugLog("xPrev: " . _c($xPrev) . “\n");
$this->debugLog("y: " . _c($y) . “\n");

$xPrev = $x;
$yPrev = $y;

$encrypted .= $y;
$this->debugLog("Currently encrypted: "._c($encrypted)."\n\n");
}
return $encrypted;
}

public function debugLog($message)
{
   if ($this->debug)
      echo $message;
}

public function exor($array1, $array2)
{
   $len = (strlen($array1) <= strlen($array2)) ? strlen($array2) : strlen($array1);

$array1 = str_pad($array1, $len, “\x00");
$array2 = str_pad($array2, $len, “\x00");

$res = ‘';
for ($i=0; $i < $len; $i++)
{
   $res .= $array1[$i] ^ $array2[$i];
}
return $res;
}

function _c($binary) { return sprintf(“[%s]", chunk_split(bin2hex($binary), 4, ' ')); }

}
