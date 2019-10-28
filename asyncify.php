<?php

$not_subbing = [];

foreach (\explode("\n", \shell_exec("find src -type f -name '*.php'")) as $file) {
    if (!$file) {
        continue;
    }
    if (\in_array(\basename($file, '.php'), ['APIFactory', 'API', 'Connection', 'Coroutine', 'ReferenceDatabase', 'ProxySocketPool'])) {
        continue;
    }
    if (\strpos($file, 'Loop/')) {
        continue;
    }
    if (\strpos($file, 'Stream/')) {
        continue;
    }
    if (\strpos($file, 'Server/')) {
        continue;
    }
    if (\strpos($file, 'Async/')) {
        continue;
    }
    $to_sub = [];
    $last_match = null;
    foreach (\explode("\n", $filec = \file_get_contents($file)) as $number => $line) {
        if (\preg_match("/public function (\w*)[(]/", $line, $matches)) {
            $last_match = \stripos($matches[1], 'async') === false ? $matches[1] : null;
        }
        if (\preg_match('/function [(]/', $line) && \stripos($line, 'public function') === false) {
            $last_match = 0;
        }
        if (\strpos($line, 'yield') !== false) {
            if ($last_match) {
                echo "subbing $last_match for $line at $number in $file".PHP_EOL;
                $to_sub[] = $last_match;
            } elseif ($last_match === 0) {
                echo "============\nNOT SUBBING $last_match for $line at $number in $file\n============".PHP_EOL;
                $not_subbing[$file] = $file;
            }
        }
    }
    $input = [];
    $output = [];
    foreach ($to_sub as $func) {
        $input[] = "public function $func(";
        $output[] = "public function $func".'_async(';
    }
    if ($input) {
        \file_put_contents($file, \str_replace($input, $output, $filec));
    }
}
\var_dump(\array_values($not_subbing));
