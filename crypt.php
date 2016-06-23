<?php
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libpy2php');
require_once ('libpy2php.php');
require_once ('AES.class.php');
function ige_encrypt($message, $key, $iv) {
    return py2php_kwargs_function_call('_ige', [$message, $key, $iv], ["operation" => 'encrypt']);
}
function ige_decrypt($message, $key, $iv) {
    return py2php_kwargs_function_call('_ige', [$message, $key, $iv], ["operation" => 'decrypt']);
}
/**
 * Given a key, given an iv, and message
 * do whatever operation asked in the operation field.
 * Operation will be checked for: "decrypt" and "encrypt" strings.
 * Returns the message encrypted/decrypted.
 * message must be a multiple by 16 bytes (for division in 16 byte blocks)
 * key must be 32 byte
 * iv must be 32 byte (it's not internally used in AES 256 ECB, but it's
 * needed for IGE)
 */
function _ige($message, $key, $iv, $operation = 'decrypt') {
    $message = $bytes($message);
    if ((count($key) != 32)) {
        throw new $ValueError('key must be 32 bytes long (was ' . pyjslib_str(count($key)) . ' bytes)');
    }
    if ((count($iv) != 32)) {
        throw new $ValueError('iv must be 32 bytes long (was ' . pyjslib_str(count($iv)) . ' bytes)');
    }
    $cipher = new AES($key);
    $cipher = $cipher->encrypt($iv);
    $blocksize = $cipher->block_size;
    if (((count($message) % $blocksize) != 0)) {
        throw new $ValueError('message must be a multiple of 16 bytes (try adding ' . pyjslib_str((16 - (count($message) % 16))) . ' bytes of padding)');
    }
    $ivp = array_slice($iv, 0, $blocksize - 0);
    $ivp2 = array_slice($iv, $blocksize, null);
    $ciphered = $bytes();
    foreach (pyjslib_range(0, count($message), $blocksize) as $i) {
        $indata = array_slice($message, $i, ($i + $blocksize) - $i);
        if (($operation == 'decrypt')) {
            $xored = new strxor($indata, $ivp2);
            $decrypt_xored = $cipher->decrypt($xored);
            $outdata = new strxor($decrypt_xored, $ivp);
            $ivp = $indata;
            $ivp2 = $outdata;
        } else if (($operation == 'encrypt')) {
            $xored = new strxor($indata, $ivp);
            $encrypt_xored = $cipher->encrypt($xored);
            $outdata = new strxor($encrypt_xored, $ivp2);
            $ivp = $outdata;
            $ivp2 = $indata;
        } else {
            throw new $ValueError('operation must be either \'decrypt\' or \'encrypt\'');
        }
        $ciphered+= $outdata;
    }
    return $ciphered;
}
