<?php

$input = \file_get_contents('vendor/league/uri-interfaces/src/Contracts/UriInterface.php');
\preg_match_all("/public function (\w+)[(].*/", $input, $matches);
$search = $matches[1];
$replace = $matches[0];

foreach ($search as &$method) {
    $method = "/public function $method".'[(].*/';
}
foreach ($replace as &$method) {
    $method= \str_replace(';', '', $method);
}

$input = \file_get_contents('vendor/league/uri/src/Uri.php');
$input = \preg_replace($search, $replace, $input);
\file_put_contents('vendor/league/uri/src/Uri.php', $input);
