#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';

$MadelineProto = new \danog\MadelineProto\API();

if (file_exists('number.php')) {
    include_once 'number.php';
    $sendCode = $MadelineProto->auth->sendCode(
        [
            'phone_number' => $number,
            'sms_type'     => 5,
            'api_id'       => $this->settings['app_info']['api_id'],
            'api_hash'     => $this->settings['app_info']['api_hash'],
            'lang_code'    => $this->settings['app_info']['lang_code'],
        ]
    );
    var_dump($sendCode);
}
