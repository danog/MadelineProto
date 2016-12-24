<?php
/*
Copyright 2016 Daniil Gentili
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
 * Manages logging in and out
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
        \danog\MadelineProto\Logger::log('Logged out successfully!');

        return true;
    }

    public function bot_login($token)
    {
        if ($this->API->datacenter->authorized) {
            \danog\MadelineProto\Logger::log('This instance of MadelineProto is already logged in. Logging out first...');
            $this->logout();
        }
        \danog\MadelineProto\Logger::log('Logging in as a bot...');
        $this->API->datacenter->authorization = $this->API->method_call(
            'auth.importBotAuthorization',
            [
                'bot_auth_token'     => $token,
                'api_id'             => $this->API->settings['app_info']['api_id'],
                'api_hash'           => $this->API->settings['app_info']['api_hash'],
            ]
        );
        $this->API->datacenter->authorized = true;
        $this->API->get_updates_state();
        \danog\MadelineProto\Logger::log('Logged in successfully!');

        return $this->API->datacenter->authorization;
    }

    public function phone_login($number, $sms_type = 5)
    {
        if ($this->API->datacenter->authorized) {
            \danog\MadelineProto\Logger::log('This instance of MadelineProto is already logged in. Logging out first...');
            $this->logout();
        }
        \danog\MadelineProto\Logger::log('Sending code...');
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
        $this->API->datacenter->waiting_code = true;
        \danog\MadelineProto\Logger::log('Code sent successfully! Once you receive the code you should use the complete_phone_login function.');

        return $this->API->datacenter->authorization;
    }

    public function complete_phone_login($code)
    {
        if (!$this->API->datacenter->waiting_code) {
            throw new \danog\MadelineProto\Exception("I'm not waiting for the code! Please call the phone_login method first");
        }
        \danog\MadelineProto\Logger::log('Logging in as a normal user...');
        $this->API->datacenter->authorization = $this->API->method_call(
            'auth.signIn',
            [
                'phone_number'    => $this->API->datacenter->authorization['phone_number'],
                'phone_code_hash' => $this->API->datacenter->authorization['phone_code_hash'],
                'phone_code'      => $code,
            ]
        );
        $this->API->datacenter->waiting_code = false;
        $this->API->datacenter->authorized = true;
        $this->API->get_updates_state();
        \danog\MadelineProto\Logger::log('Logged in successfully!');

        return $this->API->datacenter->authorization;
    }

}
