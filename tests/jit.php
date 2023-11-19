<?php declare(strict_types=1);

$status = opcache_get_status();
var_dump($status);

if (!$status["jit"]["on"]) {
    echo "JIT is not enabled!".PHP_EOL;
    die(1);
}

echo "OK!".PHP_EOL;
die(0);
