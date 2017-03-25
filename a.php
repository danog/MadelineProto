<?php

require 'vendor/autoload.php';
$two = new \phpseclib\Math\BigInteger(2);
$t = new \phpseclib\Math\BigInteger(2);

$pow = 1984;
for ($x = 0; $x < ($pow-1); $x++) {
  $two = $two->multiply($t);
var_dump($x);
}
echo($two->__toString().PHP_EOL);
