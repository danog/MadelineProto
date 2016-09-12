#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';
$config = parse_ini_file('credentials', true);
if (!$config) {
    pyjslib_printnl("File 'credentials' seems to not exist.");
    exit(-1);
}

function base64url_decode($data)
{
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}
$MadelineProto = new \danog\MadelineProto\API($config);
