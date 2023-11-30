<?php

$lock = fopen('woodpecker.lock', 'c+');
flock($lock, LOCK_EX);

if (fread($lock, 1) !== "1") {
    exec("tests/setup.sh", $o, $res);
    fwrite($lock, "1");
} else {
    $o = [];
    $res = 0;
}

flock($lock, LOCK_UN);

echo implode("\n", $o);
exit($res);