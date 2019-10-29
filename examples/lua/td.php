#!/usr/bin/env php
<?php
/*
Copyright 2016-2019 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

require '../vendor/autoload.php';
$settings = [];
$Lua = false;

try {
    $Lua = \danog\MadelineProto\Serialization::deserialize('td.madeline');
} catch (\danog\MadelineProto\Exception $e) {
}
if (!\is_object($Lua)) {
    $MadelineProto = new \danog\MadelineProto\API($settings);
    while (!\in_array(($res = \readline('Do you want to login as a user or as a bot (u/b)? ')), ['u', 'b'])) {
        echo 'Please write either u or b'.PHP_EOL;
    }
    switch ($res) {
        case 'u':
            $sentCode = $MadelineProto->phoneLogin(\readline('Enter your phone number: '));
            \danog\MadelineProto\Logger::log($sentCode, \danog\MadelineProto\Logger::NOTICE);
            echo 'Enter the code you received: ';
            $code = \fgets(STDIN, (isset($sentCode['type']['length']) ? $sentCode['type']['length'] : 5) + 1);
            $authorization = $MadelineProto->completePhoneLogin($code);
            \danog\MadelineProto\Logger::log($authorization, \danog\MadelineProto\Logger::NOTICE);
            if ($authorization['_'] === 'account.noPassword') {
                throw new \danog\MadelineProto\Exception('2FA is enabled but no password is set!');
            }
            if ($authorization['_'] === 'account.password') {
                \danog\MadelineProto\Logger::log('2FA is enabled', \danog\MadelineProto\Logger::NOTICE);
                $authorization = $MadelineProto->complete_2fa_login(\readline('Please enter your password (hint '.$authorization['hint'].'): '));
            }
            if ($authorization['_'] === 'account.needSignup') {
                \danog\MadelineProto\Logger::log('Registering new user', \danog\MadelineProto\Logger::NOTICE);
                $authorization = $MadelineProto->completeSignup(\readline('Please enter your first name: '), \readline('Please enter your last name (can be empty): '));
            }
            \danog\MadelineProto\Logger::log($authorization, \danog\MadelineProto\Logger::NOTICE);
            $Lua = new \danog\MadelineProto\Lua('madeline.lua', $MadelineProto);
            break;
        case 'b':
            $authorization = $MadelineProto->botLogin(\readline('Please enter a bot token: '));
            \danog\MadelineProto\Logger::log($authorization, \danog\MadelineProto\Logger::NOTICE);
            $Lua = new \danog\MadelineProto\Lua('madeline.lua', $MadelineProto);
            break;
    }
}

$offset = 0;
while (true) {
    $updates = $Lua->MadelineProto->getUpdates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]); // Just like in the bot API, you can specify an offset, a limit and a timeout
    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1; // Just like in the bot API, the offset must be set to the last update_id
        $Lua->tdcliUpdateCallback($update['update']);
    }
    echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('td.madeline', $Lua).' bytes'.PHP_EOL;
}
