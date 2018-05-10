#!/usr/bin/env php
<?php
$debug = false; // Set this to true to avoid automatic updates of this script





echo "mtproxyd - Copyright by Daniil Gentili, licensed under AGPLv3\n\n";
if (!isset($argv[2])) {
    echo "Usage: ".$argv[0]." seed port\n\nseed is any string or word that will be used as seed to generate the proxy secret\nport is the port where to start listening for connections\n";
    exit(1);
}

if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}

if (!$debug) {
    $mtproxyd = file_get_contents('https://phar.madelineproto.xyz/mtproxyd?v=new');
    if ($mtproxyd) {
        file_put_contents($argv[0], $mtproxyd);
    }
}

require_once 'madeline.php';

$secret = md5($argv[1]);
echo "Secret is $secret\n";

$MadelineProto = new \danog\MadelineProto\API('proxy.madeline');
$MadelineProto->parse_dc_options($MadelineProto->help->getConfig()['dc_options']);
$handler = new \danog\MadelineProto\Server(['type' => AF_INET, 'protocol' => 0, 'address' => '0.0.0.0', 'port' => $argv[2], 'handler' => '\danog\MadelineProto\Server\Proxy', 'extra' => ['madeline' => $MadelineProto->API->datacenter->sockets, 'secret' => hex2bin($secret), 'timeout' => 10]]);
$handler->start();
