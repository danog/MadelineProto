<?php

/**
 * Login module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Wrappers;

use danog\MadelineProto\MTProtoTools\PasswordCalculator;

/**
 * Manages logging in and out.
 */
trait Login
{
    public function logout_async()
    {
        yield $this->method_call_async_read('auth.logOut', [], ['datacenter' => $this->datacenter->curdc]);
        $this->resetSession();
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['logout_ok'], \danog\MadelineProto\Logger::NOTICE);
        $this->startUpdateSystem();

        return true;
    }

    public function bot_login_async($token)
    {
        if ($this->authorized === self::LOGGED_IN) {
            $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['already_logged_in'], \danog\MadelineProto\Logger::NOTICE);
            yield $this->logout_async();
        }
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_bot'], \danog\MadelineProto\Logger::NOTICE);
        $this->authorization = yield $this->method_call_async_read('auth.importBotAuthorization', ['bot_auth_token' => $token, 'api_id' => $this->settings['app_info']['api_id'], 'api_hash' => $this->settings['app_info']['api_hash']], ['datacenter' => $this->datacenter->curdc]);
        $this->authorized = self::LOGGED_IN;
        $this->authorized_dc = $this->datacenter->curdc;
        $this->datacenter->sockets[$this->datacenter->curdc]->authorized = true;
        $this->updates = [];
        $this->updates_key = 0;
        yield $this->init_authorization_async();
        $this->startUpdateSystem();

        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_ok'], \danog\MadelineProto\Logger::NOTICE);

        return $this->authorization;
    }

    public function phone_login_async($number, $sms_type = 5)
    {
        if ($this->authorized === self::LOGGED_IN) {
            $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['already_logged_in'], \danog\MadelineProto\Logger::NOTICE);
            yield $this->logout_async();
        }
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_code_sending'], \danog\MadelineProto\Logger::NOTICE);
        $this->authorization = yield $this->method_call_async_read('auth.sendCode', ['settings' => ['_' => 'codeSettings'], 'phone_number' => $number, 'sms_type' => $sms_type, 'api_id' => $this->settings['app_info']['api_id'], 'api_hash' => $this->settings['app_info']['api_hash'], 'lang_code' => $this->settings['app_info']['lang_code']], ['datacenter' => $this->datacenter->curdc]);
        $this->authorized_dc = $this->datacenter->curdc;
        $this->authorization['phone_number'] = $number;
        //$this->authorization['_'] .= 'MP';
        $this->authorized = self::WAITING_CODE;
        $this->updates = [];
        $this->updates_key = 0;
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_code_sent'], \danog\MadelineProto\Logger::NOTICE);

        return $this->authorization;
    }

    public function complete_phone_login_async($code)
    {
        if ($this->authorized !== self::WAITING_CODE) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['login_code_uncalled']);
        }
        $this->authorized = self::NOT_LOGGED_IN;
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_user'], \danog\MadelineProto\Logger::NOTICE);

        try {
            $authorization = yield $this->method_call_async_read('auth.signIn', ['phone_number' => $this->authorization['phone_number'], 'phone_code_hash' => $this->authorization['phone_code_hash'], 'phone_code' => (string) $code], ['datacenter' => $this->datacenter->curdc]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            if ($e->rpc === 'SESSION_PASSWORD_NEEDED') {
                $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_2fa_enabled'], \danog\MadelineProto\Logger::NOTICE);
                $this->authorization = yield $this->method_call_async_read('account.getPassword', [], ['datacenter' => $this->datacenter->curdc]);
                if (!isset($this->authorization['hint'])) {
                    $this->authorization['hint'] = '';
                }
                $this->authorized = self::WAITING_PASSWORD;

                return $this->authorization;
            }
            if ($e->rpc === 'PHONE_NUMBER_UNOCCUPIED') {
                $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_need_signup'], \danog\MadelineProto\Logger::NOTICE);
                $this->authorized = self::WAITING_SIGNUP;
                $this->authorization['phone_code'] = $code;

                return ['_' => 'account.needSignup'];
            }

            throw $e;
        }
        $this->authorized = self::LOGGED_IN;
        $this->authorization = $authorization;
        $this->datacenter->sockets[$this->datacenter->curdc]->authorized = true;
        yield $this->init_authorization_async();
        yield $this->get_phone_config_async();
        $this->startUpdateSystem();

        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_ok'], \danog\MadelineProto\Logger::NOTICE);

        return $this->authorization;
    }

    public function import_authorization_async($authorization)
    {
        if ($this->authorized === self::LOGGED_IN) {
            $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['already_logged_in'], \danog\MadelineProto\Logger::NOTICE);
            yield $this->logout_async();
        }
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_auth_key'], \danog\MadelineProto\Logger::NOTICE);
        list($dc_id, $auth_key) = $authorization;
        if (!is_array($auth_key)) {
            $auth_key = ['auth_key' => $auth_key, 'id' => substr(sha1($auth_key, true), -8), 'server_salt' => ''];
        }
        $this->authorized_dc = $dc_id;
        $this->datacenter->sockets[$dc_id]->session_id = $this->random(8);
        $this->datacenter->sockets[$dc_id]->session_in_seq_no = 0;
        $this->datacenter->sockets[$dc_id]->session_out_seq_no = 0;
        $this->datacenter->sockets[$dc_id]->auth_key = $auth_key;
        $this->datacenter->sockets[$dc_id]->temp_auth_key = null;
        $this->datacenter->sockets[$dc_id]->incoming_messages = [];
        $this->datacenter->sockets[$dc_id]->outgoing_messages = [];
        $this->datacenter->sockets[$dc_id]->new_outgoing = [];
        $this->datacenter->sockets[$dc_id]->new_incoming = [];
        $this->datacenter->sockets[$dc_id]->authorized = true;
        $this->authorized = self::LOGGED_IN;
        yield $this->init_authorization_async();
        yield $this->get_phone_config_async();

        $res = yield $this->get_self_async();

        $this->startUpdateSystem();

        return $res;
    }

    public function export_authorization_async()
    {
        if ($this->authorized !== self::LOGGED_IN) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['not_logged_in']);
        }
        yield $this->get_self_async();
        $this->authorized_dc = $this->datacenter->curdc;

        return [$this->datacenter->curdc, $this->datacenter->sockets[$this->datacenter->curdc]->auth_key['auth_key']];
    }

    public function complete_signup_async($first_name, $last_name)
    {
        if ($this->authorized !== self::WAITING_SIGNUP) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['signup_uncalled']);
        }
        $this->authorized = self::NOT_LOGGED_IN;
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['signing_up'], \danog\MadelineProto\Logger::NOTICE);
        $this->authorization = yield $this->method_call_async_read('auth.signUp', ['phone_number' => $this->authorization['phone_number'], 'phone_code_hash' => $this->authorization['phone_code_hash'], 'phone_code' => $this->authorization['phone_code'], 'first_name' => $first_name, 'last_name' => $last_name], ['datacenter' => $this->datacenter->curdc]);
        $this->authorized = self::LOGGED_IN;
        $this->datacenter->sockets[$this->datacenter->curdc]->authorized = true;
        yield $this->init_authorization_async();
        yield $this->get_phone_config_async();

        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['signup_ok'], \danog\MadelineProto\Logger::NOTICE);
        $this->startUpdateSystem();

        return $this->authorization;
    }

    public function complete_2fa_login_async($password)
    {
        if ($this->authorized !== self::WAITING_PASSWORD) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['2fa_uncalled']);
        }
        $this->authorized = self::NOT_LOGGED_IN;
        $hasher = new PasswordCalculator($this->logger);
        $hasher->addInfo($this->authorization);
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_user'], \danog\MadelineProto\Logger::NOTICE);
        $this->authorization = yield $this->method_call_async_read('auth.checkPassword', ['password' => $hasher->getCheckPassword($password)], ['datacenter' => $this->datacenter->curdc]);
        $this->authorized = self::LOGGED_IN;
        $this->datacenter->sockets[$this->datacenter->curdc]->authorized = true;
        yield $this->init_authorization_async();
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_ok'], \danog\MadelineProto\Logger::NOTICE);
        yield $this->get_phone_config_async();
        $this->startUpdateSystem();

        return $this->authorization;
    }

    /**
     * Update the 2FA password
     *
     * The params array can contain password, new_password, email and hint params.
     * 
     * @param array $params The params
     * @return void
     */
    public function update_2fa_async(array $params)
    {
        $hasher = new PasswordCalculator($this->logger);
        $hasher->addInfo(yield $this->method_call_async_read('account.getPassword', [], ['datacenter' => $this->datacenter->curdc]));

        return yield $this->method_call_async_read('account.updatePasswordSettings', $hasher->getPassword($params), ['datacenter' => $this->datacenter->curdc]);
    }
}
