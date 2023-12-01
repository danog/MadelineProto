<?php

use LeProxy\LeProxy\LeProxyServer;
use React\EventLoop\Loop;

require __DIR__.'/vendor/autoload.php';

$proxy = new LeProxyServer(Loop::get());
$proxySocket = $proxy->listen('127.0.0.1:0', false);

echo $proxySocket->getAddress().PHP_EOL;

Loop::run();