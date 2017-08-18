#!/usr/bin/env php
<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/
set_include_path(get_include_path().':'.realpath(dirname(__FILE__).'/MadelineProto/'));
require_once 'vendor/autoload.php';
if (file_exists('web_data.php')) {
    require_once 'web_data.php';
}

echo 'Deserializing MadelineProto from s.madeline...'.PHP_EOL;
$MadelineProto = false;

try {
    $MadelineProto = \danog\MadelineProto\Serialization::deserialize('s.madeline');
} catch (\danog\MadelineProto\Exception $e) {
    var_dump($e->getMessage());
}
if (file_exists('.env')) {
    echo 'Loading .env...'.PHP_EOL;
    $dotenv = new Dotenv\Dotenv(getcwd());
    $dotenv->load();
}

echo 'Loading settings...'.PHP_EOL;
$settings = json_decode(getenv('MTPROTO_SETTINGS'), true) ?: [];

if ($MadelineProto === false) {
    echo 'Loading MadelineProto...'.PHP_EOL;
    $MadelineProto = new \danog\MadelineProto\API($settings);
    if (getenv('TRAVIS_COMMIT') == '') {
        $checkedPhone = $MadelineProto->auth->checkPhone(// auth.checkPhone becomes auth->checkPhone
            [
                'phone_number'     => getenv('MTPROTO_NUMBER'),
           ]
        );

        \danog\MadelineProto\Logger::log([$checkedPhone], \danog\MadelineProto\Logger::NOTICE);
        $sentCode = $MadelineProto->phone_login(getenv('MTPROTO_NUMBER'));
        \danog\MadelineProto\Logger::log([$sentCode], \danog\MadelineProto\Logger::NOTICE);
        echo 'Enter the code you received: ';
        $code = fgets(STDIN, (isset($sentCode['type']['length']) ? $sentCode['type']['length'] : 5) + 1);
        $authorization = $MadelineProto->complete_phone_login($code);
        \danog\MadelineProto\Logger::log([$authorization], \danog\MadelineProto\Logger::NOTICE);
        if ($authorization['_'] === 'account.noPassword') {
            throw new \danog\MadelineProto\Exception('2FA is enabled but no password is set!');
        }
        if ($authorization['_'] === 'account.password') {
            \danog\MadelineProto\Logger::log(['2FA is enabled'], \danog\MadelineProto\Logger::NOTICE);
            $authorization = $MadelineProto->complete_2fa_login(readline('Please enter your password (hint '.$authorization['hint'].'): '));
        }
        if ($authorization['_'] === 'account.needSignup') {
            \danog\MadelineProto\Logger::log(['Registering new user'], \danog\MadelineProto\Logger::NOTICE);
            $authorization = $MadelineProto->complete_signup(readline('Please enter your first name: '), readline('Please enter your last name (can be empty): '));
        }

        echo 'Serializing MadelineProto to s.madeline...'.PHP_EOL;
        echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('s.madeline', $MadelineProto).' bytes'.PHP_EOL;
    } else {
        $MadelineProto->bot_login(getenv('BOT_TOKEN'));
    }
}
$message = (getenv('TRAVIS_COMMIT') == '') ? 'I iz works always (io laborare sembre) (yo lavorar siempre) (mi labori ĉiam) (я всегда работать) (Ik werkuh altijd) (Ngimbonga ngaso sonke isikhathi ukusebenza)' : ('Travis ci tests in progress: commit '.getenv('TRAVIS_COMMIT').', job '.getenv('TRAVIS_JOB_NUMBER').', PHP version: '.getenv('TRAVIS_PHP_VERSION'));

echo 'Serializing MadelineProto to s.madeline...'.PHP_EOL;
echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('s.madeline', $MadelineProto).' bytes'.PHP_EOL;

$sent = [-440592694=>true];

$offset = 0;
while (true) {
    try {
        $updates = $MadelineProto->API->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]); // Just like in the bot API, you can specify an offset, a limit and a timeout
        //\danog\MadelineProto\Logger::log([$updates]);
        foreach ($updates as $update) {
            echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('s.madeline', $MadelineProto).' bytes'.PHP_EOL;
            $offset = $update['update_id'] + 1; // Just like in the bot API, the offset must be set to the last update_id
            switch ($update['update']['_']) {
                /*case 'updateNewChannelMessage':
                    if ($update['update']['message']['out'] || $update['update']['message']['message'] === '') continue;
                    $MadelineProto->messages->sendMessage(['peer' => $update['update']['message']['to_id'], 'message' => $update['update']['message']['message']]);
                    break;*/
                case 'updateNewMessage':
                    if ($update['update']['message']['out'] || $update['update']['message']['message'] === '') {
                        continue;
                    }
                    break;
                case 'updateNewEncryptedMessage':
                    //var_dump($MadelineProto->download_to_dir($update['update']['message'], '.'));
                    if (isset($sent[$update['update']['message']['chat_id']])) {
                        continue;
                    }
                    $i = 0;
                    while ($i < $argv[1]) {
                        echo "SENDING MESSAGE $i TO ".$update['update']['message']['chat_id'].PHP_EOL;
                        $MadelineProto->messages->sendEncrypted(['peer' => $update['update']['message']['chat_id'], 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => $i++]]);
                        \danog\MadelineProto\Serialization::serialize('s.madeline', $MadelineProto);
                    }
                    $sent[$update['update']['message']['chat_id']] = true;
           }
        }
    } catch (\danog\MadelineProto\RPCErrorException $e) {
        var_dump($e);
    } catch (\danog\MadelineProto\Exception $e) {
        var_dump($e->getMessage());
    }
    echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('s.madeline', $MadelineProto).' bytes'.PHP_EOL;
}
