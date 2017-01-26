<?php


ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php-Madeline-errors.log');

require 'vendor/autoload.php';
require 'db_connect.php';
$settings = [
    'db'      => $db_settings,
    'workers' => [
        'serialization_interval' => 120,
        'worker_sleep'           => 1,
    ],
    'token' => ['min_length' => 30, 'max_length' => 40],
    'other' => [
        'homedir'       => '/tmp/',
        'uri'           => $_SERVER['REQUEST_URI'],
        'params'        => $_REQUEST,
        'endpoint'      => 'http'.($_SERVER['SERVER_PORT'] == 443 ? 's' : '').'://'.$_SERVER['HTTP_HOST'].':'.$_SERVER['SERVER_PORT'].'/',
        'response_wait' => 60,
    ],
];

try {
    $web_API = new \danog\MadelineProto\WebAPI($settings);
    echo json_encode($web_API->run());
} catch (\danog\MadelineProto\ResponseException $e) {
    echo json_encode(['ok' => false, 'error_code' => 400, 'error_description' => $e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile())]);
    error_log('Exception thrown: '.$e->getMessage());
    error_log($e->getTraceAsString());
} catch (\danog\MadelineProto\Exception $e) {
    echo json_encode(['ok' => false, 'error_code' => 400, 'error_description' => $e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile())]);
    error_log('Exception thrown: '.$e->getMessage());
    error_log($e->getTraceAsString());
} catch (\danog\MadelineProto\RPCErrorException $e) {
    echo json_encode(['ok' => false, 'error_code' => $e->getCode(), 'error_description' => $e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile())]);
    error_log('Exception thrown: '.$e->getMessage());
    error_log($e->getTraceAsString());
} catch (\danog\MadelineProto\TL\Exception $e) {
    echo json_encode(['ok' => false, 'error_code' => 400, 'error_description' => $e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile())]);
    error_log('Exception thrown: '.$e->getMessage());
    error_log($e->getTraceAsString());
} catch (\PDOException $e) {
    echo json_encode(['ok' => false, 'error_code' => 400, 'error_description' => $e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile())]);
    error_log('Exception thrown: '.$e->getMessage());
    error_log($e->getTraceAsString());
}
