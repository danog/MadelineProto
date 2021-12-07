<?php

/**
 * RPCErrorException module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;

/**
 * Indicates an error returned by Telegram's API.
 */
class RPCErrorException extends \Exception
{
    use TL\PrettyException;
    /**
     * RPC error code.
     */
    public string $rpc = '';
    private $fetched = false;
    public static $descriptions = ['RPC_MCGET_FAIL' => 'Telegram is having internal issues, please try again later.', 'RPC_CALL_FAIL' => 'Telegram is having internal issues, please try again later.', 'USER_PRIVACY_RESTRICTED' => "The user's privacy settings do not allow you to do this", 'CHANNEL_PRIVATE' => "You haven't joined this channel/supergroup", 'USER_IS_BOT' => "Bots can't send messages to other bots", 'BOT_METHOD_INVALID' => 'This method cannot be run by a bot', 'PHONE_CODE_EXPIRED' => 'The phone code you provided has expired, this may happen if it was sent to any chat on telegram (if the code is sent through a telegram chat (not the official account) to avoid it append or prepend to the code some chars)', 'USERNAME_INVALID' => 'The provided username is not valid', 'ACCESS_TOKEN_INVALID' => 'The provided token is not valid', 'ACTIVE_USER_REQUIRED' => 'The method is only available to already activated users', 'FIRSTNAME_INVALID' => 'The first name is invalid', 'LASTNAME_INVALID' => 'The last name is invalid', 'PHONE_NUMBER_INVALID' => 'The phone number is invalid', 'PHONE_CODE_HASH_EMPTY' => 'phone_code_hash is missing', 'PHONE_CODE_EMPTY' => 'phone_code is missing', 'API_ID_INVALID' => 'The api_id/api_hash combination is invalid', 'PHONE_NUMBER_OCCUPIED' => 'The phone number is already in use', 'PHONE_NUMBER_UNOCCUPIED' => 'The phone number is not yet being used', 'USERS_TOO_FEW' => 'Not enough users (to create a chat, for example)', 'USERS_TOO_MUCH' => 'The maximum number of users has been exceeded (to create a chat, for example)', 'TYPE_CONSTRUCTOR_INVALID' => 'The type constructor is invalid', 'FILE_PART_INVALID' => 'The file part number is invalid', 'FILE_PARTS_INVALID' => 'The number of file parts is invalid', 'MD5_CHECKSUM_INVALID' => 'The MD5 checksums do not match', 'PHOTO_INVALID_DIMENSIONS' => 'The photo dimensions are invalid', 'FIELD_NAME_INVALID' => 'The field with the name FIELD_NAME is invalid', 'FIELD_NAME_EMPTY' => 'The field with the name FIELD_NAME is missing', 'MSG_WAIT_FAILED' => 'A waiting call returned an error', 'USERNAME_NOT_OCCUPIED' => 'The provided username is not occupied', 'PHONE_NUMBER_BANNED' => 'The provided phone number is banned from telegram', 'AUTH_KEY_UNREGISTERED' => 'The authorization key has expired', 'INVITE_HASH_EXPIRED' => 'The invite link has expired', 'USER_DEACTIVATED' => 'The user was deactivated', 'USER_ALREADY_PARTICIPANT' => 'The user is already in the group', 'MESSAGE_ID_INVALID' => 'The provided message id is invalid', 'PEER_ID_INVALID' => 'The provided peer id is invalid', 'CHAT_ID_INVALID' => 'The provided chat id is invalid', 'MESSAGE_DELETE_FORBIDDEN' => "You can't delete one of the messages you tried to delete, most likely because it is a service message.", 'CHAT_ADMIN_REQUIRED' => 'You must be an admin in this chat to do this', -429 => 'Too many requests', 'PEER_FLOOD' => "You are spamreported, you can't do this"];
    public static $errorMethodMap = [];
    private $caller = '';
    public static function localizeMessage($method, int $code, string $error)
    {
        if (!$method || !$code || !$error) {
            return $error;
        }
        $error = \preg_replace('/\\d+$/', "X", $error);
        $description = self::$descriptions[$error] ?? '';
        if (!isset(self::$errorMethodMap[$code][$method][$error]) || !isset(self::$descriptions[$error]) || $code === 500) {
            Tools::callFork((function () use ($method, $code, $error) {
                try {
                    $res = \json_decode(
                        yield
                            (yield HttpClientBuilder::buildDefault()
                                ->request(new Request('https://rpc.pwrtelegram.xyz/?method='.$method.'&code='.$code.'&error='.$error))
                            )->getBody()->buffer(),
                        true
                    );
                    if (isset($res['ok']) && $res['ok'] && isset($res['result'])) {
                        $description = $res['result'];
                        RPCErrorException::$descriptions[$error] = $description;
                        RPCErrorException::$errorMethodMap[$code][$method][$error] = $error;
                    }
                } catch (\Throwable $e) {
                }
            })());
        }
        if (!$description) {
            return $error;
        }
        return $description;
    }
    public function __toString()
    {
        $this->localized ??= self::localizeMessage($this->caller, $this->code, $this->message);
        $result = \sprintf(\danog\MadelineProto\Lang::$current_lang['rpc_tg_error'], $this->localized." ({$this->code})", $this->rpc, $this->file, $this->line.PHP_EOL, \danog\MadelineProto\Magic::$revision.PHP_EOL.PHP_EOL).PHP_EOL.$this->getTLTrace().PHP_EOL;
        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
            $result = \str_replace(PHP_EOL, '<br>'.PHP_EOL, $result);
        }
        return $result;
    }
    /**
     * Get localized error name.
     *
     * @return string
     */
    public function getLocalization(): string
    {
        $this->localized ??= self::localizeMessage($this->caller, $this->code, $this->message);
        return $this->localized;
    }
    /**
     * Set localized error name.
     *
     * @param string $localization
     * @return void
     */
    public function setLocalization(string $localization): void
    {
        $this->localized = $localization;
    }
    public function __construct($message = null, $code = 0, $caller = '', Exception $previous = null)
    {
        $this->rpc = $message;
        parent::__construct($message, $code, $previous);
        if (\is_string($caller)) {
            $this->prettifyTL($caller);
            $this->caller = $caller;
            $additional = [];
            foreach ($this->getTrace() as $level) {
                if (isset($level['function']) && $level['function'] === 'methodCall') {
                    $this->line = $level['line'];
                    $this->file = $level['file'];
                    $additional = $level['args'];
                }
            }
        }
        /*
        if (\in_array($this->rpc, ['CHANNEL_PRIVATE', -404, -429, 'USERNAME_NOT_OCCUPIED', 'ACCESS_TOKEN_INVALID', 'AUTH_KEY_UNREGISTERED', 'SESSION_PASSWORD_NEEDED', 'PHONE_NUMBER_UNOCCUPIED', 'PEER_ID_INVALID', 'CHAT_ID_INVALID', 'USERNAME_INVALID', 'CHAT_WRITE_FORBIDDEN', 'CHAT_ADMIN_REQUIRED', 'PEER_FLOOD'])) {
            return;
        }
        if (\strpos($this->rpc, 'FLOOD_WAIT_') !== false) {
            return;
        }
        $message === 'Telegram is having internal issues, please try again later.' ? \Rollbar\Rollbar::log(\Rollbar\Payload\Level::critical(), $message) : \Rollbar\Rollbar::log(\Rollbar\Payload\Level::error(), $this, $additional);
        */
    }
}
