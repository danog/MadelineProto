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

use danog\MadelineProto\MTProto\PermAuthKey;
use danog\MadelineProto\MTProtoTools\PasswordCalculator;

/**
 * Manages logging in and out.
 */
trait Login
{
    public function logout()
    {
        yield $this->methodCallAsyncRead('auth.logOut', [], ['datacenter' => $this->datacenter->curdc]);
        $this->resetSession();
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['logout_ok'], \danog\MadelineProto\Logger::NOTICE);
        $this->startUpdateSystem();

        return true;
    }

    public function botLogin($token)
    {
        if ($this->authorized === self::LOGGED_IN) {
            $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['already_logged_in'], \danog\MadelineProto\Logger::NOTICE);
            yield $this->logout();
        }
        $callbacks = [$this, $this->referenceDatabase];
        $this->updateCallbacks($callbacks);

        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_bot'], \danog\MadelineProto\Logger::NOTICE);
        $this->authorization = yield $this->methodCallAsyncRead('auth.importBotAuthorization', ['bot_auth_token' => $token, 'api_id' => $this->settings['app_info']['api_id'], 'api_hash' => $this->settings['app_info']['api_hash']], ['datacenter' => $this->datacenter->curdc]);
        $this->authorized = self::LOGGED_IN;
        $this->authorized_dc = $this->datacenter->curdc;
        $this->datacenter->getDataCenterConnection($this->datacenter->curdc)->authorized(true);
        $this->updates = [];
        $this->updates_key = 0;
        yield $this->initAuthorization();
        $this->startUpdateSystem();

        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_ok'], \danog\MadelineProto\Logger::NOTICE);

        return $this->authorization;
    }

    public function phoneLogin($number, $sms_type = 5)
    {
        if ($this->authorized === self::LOGGED_IN) {
            $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['already_logged_in'], \danog\MadelineProto\Logger::NOTICE);
            yield $this->logout();
        }
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_code_sending'], \danog\MadelineProto\Logger::NOTICE);
        $this->authorization = yield $this->methodCallAsyncRead('auth.sendCode', ['settings' => ['_' => 'codeSettings'], 'phone_number' => $number, 'sms_type' => $sms_type, 'api_id' => $this->settings['app_info']['api_id'], 'api_hash' => $this->settings['app_info']['api_hash'], 'lang_code' => $this->settings['app_info']['lang_code']], ['datacenter' => $this->datacenter->curdc]);
        $this->authorized_dc = $this->datacenter->curdc;
        $this->authorization['phone_number'] = $number;
        //$this->authorization['_'] .= 'MP';
        $this->authorized = self::WAITING_CODE;
        $this->updates = [];
        $this->updates_key = 0;
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_code_sent'], \danog\MadelineProto\Logger::NOTICE);

        return $this->authorization;
    }

    public function completePhoneLogin($code)
    {
        if ($this->authorized !== self::WAITING_CODE) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['login_code_uncalled']);
        }
        $this->authorized = self::NOT_LOGGED_IN;
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_user'], \danog\MadelineProto\Logger::NOTICE);

        try {
            $authorization = yield $this->methodCallAsyncRead('auth.signIn', ['phone_number' => $this->authorization['phone_number'], 'phone_code_hash' => $this->authorization['phone_code_hash'], 'phone_code' => (string) $code], ['datacenter' => $this->datacenter->curdc]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            if ($e->rpc === 'SESSION_PASSWORD_NEEDED') {
                $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_2fa_enabled'], \danog\MadelineProto\Logger::NOTICE);
                $this->authorization = yield $this->methodCallAsyncRead('account.getPassword', [], ['datacenter' => $this->datacenter->curdc]);
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
        if ($authorization['_'] === 'auth.authorizationSignUpRequired') {
            $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_need_signup'], \danog\MadelineProto\Logger::NOTICE);
            $this->authorized = self::WAITING_SIGNUP;
            $this->authorization['phone_code'] = $code;

            $authorization['_'] = 'account.needSignup';
            return $authorization;
        }
        $this->authorized = self::LOGGED_IN;
        $this->authorization = $authorization;
        $this->datacenter->getDataCenterConnection($this->datacenter->curdc)->authorized(true);
        yield $this->initAuthorization();
        yield $this->getPhoneConfig();
        $this->startUpdateSystem();

        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_ok'], \danog\MadelineProto\Logger::NOTICE);

        return $this->authorization;
    }

    public function importAuthorization($authorization)
    {
        if ($this->authorized === self::LOGGED_IN) {
            $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['already_logged_in'], \danog\MadelineProto\Logger::NOTICE);
            yield $this->logout();
        }
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_auth_key'], \danog\MadelineProto\Logger::NOTICE);
        list($dc_id, $auth_key) = $authorization;
        if (!\is_array($auth_key)) {
            $auth_key = ['auth_key' => $auth_key];
        }
        $auth_key = new PermAuthKey($auth_key);

        $this->authorized_dc = $dc_id;
        $dataCenterConnection = $this->datacenter->getDataCenterConnection($dc_id);
        $dataCenterConnection->resetSession();
        $dataCenterConnection->setPermAuthKey($auth_key);
        $dataCenterConnection->authorized(true);
        $this->authorized = self::LOGGED_IN;
        yield $this->initAuthorization();
        yield $this->getPhoneConfig();

        $res = yield $this->getSelf();

        $callbacks = [$this, $this->referenceDatabase];
        if (!($this->authorization['user']['bot'] ?? false)) {
            $callbacks []= $this->minDatabase;
        }
        $this->updateCallbacks($callbacks);

        $this->startUpdateSystem();

        return $res;
    }

    public function exportAuthorization()
    {
        if ($this->authorized !== self::LOGGED_IN) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['not_logged_in']);
        }
        yield $this->getSelf();
        $this->authorized_dc = $this->datacenter->curdc;

        return [$this->datacenter->curdc, $this->datacenter->getDataCenterConnection($this->datacenter->curdc)->getPermAuthKey()->getAuthKey()];
    }

    public function completeSignup($first_name, $last_name)
    {
        if ($this->authorized !== self::WAITING_SIGNUP) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['signup_uncalled']);
        }
        $this->authorized = self::NOT_LOGGED_IN;
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['signing_up'], \danog\MadelineProto\Logger::NOTICE);
        $this->authorization = yield $this->methodCallAsyncRead('auth.signUp', ['phone_number' => $this->authorization['phone_number'], 'phone_code_hash' => $this->authorization['phone_code_hash'], 'phone_code' => $this->authorization['phone_code'], 'first_name' => $first_name, 'last_name' => $last_name], ['datacenter' => $this->datacenter->curdc]);
        $this->authorized = self::LOGGED_IN;
        $this->datacenter->getDataCenterConnection($this->datacenter->curdc)->authorized(true);
        yield $this->initAuthorization();
        yield $this->getPhoneConfig();

        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['signup_ok'], \danog\MadelineProto\Logger::NOTICE);
        $this->startUpdateSystem();

        return $this->authorization;
    }

    public function complete2faLogin($password)
    {
        if ($this->authorized !== self::WAITING_PASSWORD) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['2fa_uncalled']);
        }
        $this->authorized = self::NOT_LOGGED_IN;
        $hasher = new PasswordCalculator($this->logger);
        $hasher->addInfo($this->authorization);
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_user'], \danog\MadelineProto\Logger::NOTICE);
        $this->authorization = yield $this->methodCallAsyncRead('auth.checkPassword', ['password' => $hasher->getCheckPassword($password)], ['datacenter' => $this->datacenter->curdc]);
        $this->authorized = self::LOGGED_IN;
        $this->datacenter->getDataCenterConnection($this->datacenter->curdc)->authorized(true);
        yield $this->initAuthorization();
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_ok'], \danog\MadelineProto\Logger::NOTICE);
        yield $this->getPhoneConfig();
        $this->startUpdateSystem();

        return $this->authorization;
    }

    /**
     * Update the 2FA password.
     *
     * The params array can contain password, new_password, email and hint params.
     *
     * @param array $params The params
     * @return void
     */
    public function update2fa(array $params)
    {
        $hasher = new PasswordCalculator($this->logger);
        $hasher->addInfo(yield $this->methodCallAsyncRead('account.getPassword', [], ['datacenter' => $this->datacenter->curdc]));

        return yield $this->methodCallAsyncRead('account.updatePasswordSettings', $hasher->getPassword($params), ['datacenter' => $this->datacenter->curdc]);
    }
}
