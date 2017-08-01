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
require_once 'vendor/autoload.php';
if (file_exists('web_data.php')) {
    require_once 'web_data.php';
}

echo 'Deserializing MadelineProto from session.madeline...'.PHP_EOL;
$MadelineProto = false;
try {
    $MadelineProto = \danog\MadelineProto\Serialization::deserialize('session.madeline');
} catch (\danog\MadelineProto\Exception $e) {
    var_dump($e->getMessage());
}

if (file_exists('.env')) {
    echo 'Loading .env...'.PHP_EOL;
    $dotenv = new Dotenv\Dotenv(getcwd());
    $dotenv->load();
}
if (getenv('TEST_SECRET_CHAT') == '') {
    die('TEST_SECRET_CHAT is not defined in .env, please define it.'.PHP_EOL);
}
echo 'Loading settings...'.PHP_EOL;
$settings = json_decode(getenv('MTPROTO_SETTINGS'), true) ?: [];

var_dump($settings);
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

        echo 'Serializing MadelineProto to session.madeline...'.PHP_EOL;
        echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('session.madeline', $MadelineProto).' bytes'.PHP_EOL;
    } else {
        $MadelineProto->bot_login(getenv('BOT_TOKEN'));
    }
}
\danog\MadelineProto\Logger::log(['hey'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);
\danog\MadelineProto\Logger::log(['hey'], \danog\MadelineProto\Logger::VERBOSE);
\danog\MadelineProto\Logger::log(['hey'], \danog\MadelineProto\Logger::NOTICE);
\danog\MadelineProto\Logger::log(['hey'], \danog\MadelineProto\Logger::WARNING);
\danog\MadelineProto\Logger::log(['hey'], \danog\MadelineProto\Logger::ERROR);
\danog\MadelineProto\Logger::log(['hey'], \danog\MadelineProto\Logger::FATAL_ERROR);

$message = (getenv('TRAVIS_COMMIT') == '') ? 'I iz works always (io laborare sembre) (yo lavorar siempre) (mi labori ĉiam) (я всегда работать) (Ik werkuh altijd) (Ngimbonga ngaso sonke isikhathi ukusebenza)' : ('Travis ci tests in progress: commit '.getenv('TRAVIS_COMMIT').', job '.getenv('TRAVIS_JOB_NUMBER').', PHP version: '.getenv('TRAVIS_PHP_VERSION'));

echo 'Serializing MadelineProto to session.madeline...'.PHP_EOL; echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('session.madeline', $MadelineProto).' bytes'.PHP_EOL;
/*
$m = new \danog\MadelineProto\API($settings);
$m->import_authorization($MadelineProto->export_authorization());
*/
$songs = [
    'Aronchupa - Little Swing'     => 'input.raw',
    'Parov Stelar - Booty Swing'   => 'inputa.raw',
    'Caravan Palace - Lone Digger' => 'inputb.raw',
//    'Swingrowers - Butterfly' => 'inputc.raw',
    'Postmodern Jukebox - Thrift Shop' => 'inputd.raw',
];
$calls = [];
    $offset = 0;
    while (1) {
        $updates = $MadelineProto->API->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]); // Just like in the bot API, you can specify an offset, a limit and a timeout
        foreach ($updates as $update) {
            \danog\MadelineProto\Logger::log([$update]);
            $offset = $update['update_id'] + 1; // Just like in the bot API, the offset must be set to the last update_id
            switch ($update['update']['_']) {
                case 'updatePhoneCall':
                if (is_object($update['update']['phone_call']) && isset($update['update']['phone_call']->madeline) && $update['update']['phone_call']->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_INCOMING) {
                    shuffle($songs);
                    if ($update['update']['phone_call']->accept() === false) {
                        echo 'DID NOT ACCEPT A CALL';
                    }
                    $calls[$update['update']['phone_call']->getOtherID()] = $update['update']['phone_call'];
                    $update['update']['phone_call']->playOnHold($songs);
//                    while ($MadelineProto->call_status($update['update']['phone_call']->getCallID()['id']) < \danog\MadelineProto\VoIP::CALL_STATE_READY) { $MadelineProto->get_updates();var_dump($update['update']['phone_call']->getCallState()); }
/*
                    $update['update']['phone_call']->configuration['shared_config']['audio_max_bitrate'] = 1000*1000;
                    $update['update']['phone_call']->configuration['shared_config']['audio_init_bitrate'] = 700*1000;
                    $update['update']['phone_call']->configuration['shared_config']['audio_bitrate_step_incr'] = 100*1000;
                    $update['update']['phone_call']->configuration['shared_config']['audio_bitrate_step_decr'] = 0;
                    $update['update']['phone_call']->parseConfig();
var_dump($update['update']['phone_call']->configuration['shared_config']);
*/
                    try {
                        //                        $MadelineProto->messages->sendMessage(['peer' => $update['update']['phone_call']->getOtherID(), 'message' => 'Emojis: '.implode('', $update['update']['phone_call']->getVisualization())]);
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                    }
                }

           }
        }
        echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('session.madeline', $MadelineProto).' bytes'.PHP_EOL;
    }
