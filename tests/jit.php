<?php declare(strict_types=1);

$ok = true;
foreach (['pcntl', 'uv', 'ffi', 'pgsql', 'memprof', 'intl', 'gmp', 'mbstring', 'pdo_mysql', 'xml', 'dom', 'iconv', 'zip', 'igbinary', 'gd'] as $ext) {
    if (!extension_loaded($ext)) {
        $ok = false;
        echo "$ext is not enabled!".PHP_EOL;
    }
}

$status = opcache_get_status();
var_dump($status);

if (!$status["jit"]["on"]) {
    echo "JIT is not enabled!".PHP_EOL;
    die(1);
}

if (!$ok) {
    die(1);
}

echo "OK!".PHP_EOL;
die(0);
