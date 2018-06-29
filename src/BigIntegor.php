<?php

namespace phpseclib\Math;

if (PHP_MAJOR_VERSION < 7 && !(class_exists('\\Phar') && \Phar::running())) {
    throw new \Exception('MadelineProto requires php 7 to run');
}
if (defined('HHVM_VERSION')) {
    $engines = [['PHP64', ['OpenSSL']], ['BCMath', ['OpenSSL']], ['PHP32', ['OpenSSL']]];
    foreach ($engines as $engine) {
        try {
            \phpseclib\Math\BigInteger::setEngine($engine[0], isset($engine[1]) ? $engine[1] : []);
            break;
        } catch (\Exception $e) {
        }
    }
}

class BigIntegor
{
}
