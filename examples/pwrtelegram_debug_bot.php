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

if (\file_exists(__DIR__.'/vendor/autoload.php')) {
    include 'vendor/autoload.php';
} else {
    if (!\file_exists('madeline.php')) {
        \copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    include 'madeline.php';
}

$settings = [];
include_once 'token.php';

try {
    $MadelineProto = new \danog\MadelineProto\API('b.madeline');
} catch (\danog\MadelineProto\Exception $e) {
    $MadelineProto = new \danog\MadelineProto\API($settings);
    $authorization = $MadelineProto->botLogin($pwrtelegram_debug_token);
    \danog\MadelineProto\Logger::log($authorization, \danog\MadelineProto\Logger::NOTICE);
}
function base64urlDecode($data)
{
    return \base64_decode(\str_pad(\strtr($data, '-_', '+/'), \strlen($data) % 4, '=', STR_PAD_RIGHT));
}
function rleDecode($string)
{
    $base256 = '';
    $last = '';
    foreach (\str_split($string) as $cur) {
        if ($last === \chr(0)) {
            $base256 .= \str_repeat($last, \ord($cur));
            $last = '';
        } else {
            $base256 .= $last;
            $last = $cur;
        }
    }
    $string = $base256.$last;

    return $string;
}

function foreach_offset_length($string)
{
    /*    $a = [];
    $b = [];
    foreach ([2, 3, 4] as $r) {
        $a []= chr(0).chr($r);
        $b []= str_repeat(chr(0), $r);
    }
    $string = str_replace($a, $b, $string);*/
    $res = [];
    $strlen = \strlen($string);
    for ($offset = 0; $offset < \strlen($string); $offset++) {
        //        for ($length = $strlen - $offset; $length > 0; $length--) {
        foreach (['i' => 4, 'q' => 8] as $c => $length) {
            $s = \substr($string, $offset, $length);
            if (\strlen($s) === $length) {
                $number = \danog\PHP\Struct::unpack('<'.$c, $s)[0];
                //$number = ord($s);
                $res[] = ['number' => $number, 'offset' => $offset, 'length' => $length];
            }
        }
    }

    return $res;
}

$res = ['offset' => 0, 'files' => []];
function getfiles($token, &$params)
{
    foreach (\json_decode(\file_get_contents('https://api.telegram.org/bot'.$token.'/getupdates?offset='.$params['offset']), true)['result'] as $update) {
        $params['offset'] = $update['update_id'] + 1;
        if (isset($update['message']['audio'])) {
            $params['files'][$update['message']['message_id']] = $update['message']['audio']['file_id'];
        }
        if (isset($update['message']['document'])) {
            $params['files'][$update['message']['message_id']] = $update['message']['document']['file_id'];
        }
        if (isset($update['message']['video'])) {
            $params['files'][$update['message']['message_id']] = $update['message']['video']['file_id'];
        }
        if (isset($update['message']['sticker'])) {
            $params['files'][$update['message']['message_id']] = $update['message']['sticker']['file_id'];
        }
        if (isset($update['message']['voice'])) {
            $params['files'][$update['message']['message_id']] = $update['message']['voice']['file_id'];
        }
        if (isset($update['message']['photo'])) {
            $params['files'][$update['message']['message_id']] = \end($update['message']['photo'])['file_id'];
        }
    }
}
function recurse($array, $prefix = '')
{
    $res = [];
    foreach ($array as $k => $v) {
        if (\is_array($v)) {
            $res = \array_merge(recurse($v, $prefix.$k.'->'), $res);
        } elseif (\is_int($v)) {
            $res[$prefix.$k] = $v;
        }
    }

    return $res;
}
$offset = 0;
while (true) {
    $updates = $MadelineProto->getUpdates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]); // Just like in the bot API, you can specify an offset, a limit and a timeout
    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1; // Just like in the bot API, the offset must be set to the last update_id
        switch ($update['update']['_']) {
            case 'updateNewMessage':
                if (isset($update['update']['message']['out']) && $update['update']['message']['out']) {
                    continue;
                }

                try {
                    if (isset($update['update']['message']['media'])) {
                        getfiles($pwrtelegram_debug_token, $res);
                        $bot_api_id = $message = $res['files'][$update['update']['message']['id']];
                        $bot_api_id_b256 = base64urlDecode($bot_api_id);
                        $bot_api_id_rledecoded = rleDecode($bot_api_id_b256);
                        $message .= PHP_EOL.PHP_EOL;
                        for ($x = 0; $x < \strlen($bot_api_id_rledecoded) - 3; $x++) {
                            $message .= 'Bytes '.$x.'-'.($x + 4).': '.\danog\PHP\Struct::unpack('<i', \substr($bot_api_id_rledecoded, $x, 4))[0].PHP_EOL;
                        }
                        $message .= PHP_EOL.PHP_EOL.
                            'First 4 bytes: '.\ord($bot_api_id_rledecoded[0]).' '.\ord($bot_api_id_rledecoded[1]).' '.\ord($bot_api_id_rledecoded[2]).' '.\ord($bot_api_id_rledecoded[3]).PHP_EOL.
                            'First 4 bytes (single integer): '.(\danog\PHP\Struct::unpack('<i', \substr($bot_api_id_rledecoded, 0, 4))[0]).PHP_EOL.
                            'bytes 8-16: '.(\danog\PHP\Struct::unpack('<q', \substr($bot_api_id_rledecoded, 8, 8))[0]).PHP_EOL.
                            'bytes 16-24: '.(\danog\PHP\Struct::unpack('<q', \substr($bot_api_id_rledecoded, 16, 8))[0]).PHP_EOL.
                            'Last byte: '.\ord(\substr($bot_api_id_rledecoded, -1)).PHP_EOL.
                            'Total length: '.\strlen($bot_api_id_b256).PHP_EOL.
                            'Total length (rledecoded): '.\strlen($bot_api_id_rledecoded).PHP_EOL.
                             PHP_EOL.'<b>param (value): start-end (length)</b>'.PHP_EOL.PHP_EOL;
                        $bot_api = foreach_offset_length($bot_api_id_rledecoded);
                        //$mtproto = $MadelineProto->getDownloadInfo($update['update']['message']['media'])['InputFileLocation'];
                        //unset($mtproto['_']);
                        $m = [];
                        $mtproto = recurse($update['update']['message']);
                        /*
                        if (isset($mtproto['version'])) {
                            unset($mtproto['version']);
                        }
                        if (isset($update['update']['message']['media']['photo'])) {
                            $mtproto['id'] = $update['update']['message']['media']['photo']['id'];
                        }
                        $mtproto['sender_id'] = $update['update']['message']['from_id'];
                        if (isset($update['update']['message']['media']['photo'])) {
                            $mtproto['access_hash'] = $update['update']['message']['media']['photo']['access_hash'];
                        }
                        if (isset($update['update']['message']['media']['document'])) {
                            $mtproto['id'] = $update['update']['message']['media']['document']['id'];
                        }
                        if (isset($update['update']['message']['media']['document'])) {
                            $mtproto['access_hash'] = $update['update']['message']['media']['document']['access_hash'];
                        }*/
                        foreach ($mtproto as $key => $n) {
                            foreach ($bot_api as $bn) {
                                if ($bn['number'] === $n) {
                                    $m[$bn['offset'] + $bn['length']] = $key.' ('.$n.'): '.$bn['offset'].'-'.($bn['offset'] + $bn['length']).' ('.$bn['length'].') <b>FOUND</b>'.PHP_EOL;
                                    unset($mtproto[$key]);
                                }
                            }
                        }
                        \ksort($m);
                        foreach ($m as $key => $bn) {
                            $message .= $bn;
                        }
                        foreach ($mtproto as $key => $n) {
                            $message .= $key.' ('.$n.'): not found'.PHP_EOL;
                        }
                        $message .= PHP_EOL.PHP_EOL.'File number: '.\danog\PHP\Struct::unpack('<i', \substr($bot_api_id_rledecoded, 8, 4))[0];
                        if ($update['update']['message']['from_id'] === 101374607) {
                            $message = \danog\PHP\Struct::unpack('<i', \substr($bot_api_id_rledecoded, 8, 4))[0];
                        }
                        $MadelineProto->messages->sendMessage(['peer' => $update['update']['message']['from_id'], 'message' => $message, 'reply_to_msg_id' => $update['update']['message']['id'], 'parse_mode' => 'markdown']);
                    }
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                } catch (\danog\MadelineProto\Exception $e) {
                    $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                }

                try {
                    if (isset($update['update']['message']['media']) && $update['update']['message']['media'] == 'messageMediaPhoto' && $update['update']['message']['media'] == 'messageMediaDocument') {
                        $time = \time();
                        //                        $file = $MadelineProto->downloadToDir($update['update']['message']['media'], '/tmp');
//                        $MadelineProto->messages->sendMessage(['peer' => $update['update']['message']['from_id'], 'message' => 'Downloaded to '.$file.' in '.(time() - $time).' seconds', 'reply_to_msg_id' => $update['update']['message']['id'], 'entities' => [['_' => 'messageEntityPre', 'offset' => 0, 'length' => strlen($res), 'language' => 'json']]]);
                    }
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                }
        }
    }
    echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('b.madeline', $MadelineProto).' bytes'.PHP_EOL;
}
