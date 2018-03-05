<?php

require 'vendor/autoload.php';

$handler = new \danog\MadelineProto\Server(['type' => AF_INET, 'protocol' => 0, 'address' => 'localhost', 'port' => 8000, 'transport_protocol' => 'tcp_abridged']);
$handler->start();
