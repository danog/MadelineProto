#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';
$config = parse_ini_file('credentials', true);
if (!$config) {
    pyjslib_printnl("File 'credentials' seems to not exist.");
    exit(-1);
}
$MadelineProto = new \danog\MadelineProto\API($config);
