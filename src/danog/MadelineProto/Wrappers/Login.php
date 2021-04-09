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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Wrappers;

use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\PermAuthKey;
use danog\MadelineProto\MTProtoTools\PasswordCalculator;

use danog\MadelineProto\Settings;

/**
 * Manages logging in and out.
 *
 * @property Settings $settings Settings
 */
trait Login
{
    /**
     * Log out currently logged in user.
     *
     * @return \Generator
     */
    public function logout(): \Generator
    {
        yield from $this->methodCallAsyncRead('auth.logOut', []);
        yield from $this->resetSession();
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['logout_ok'], \danog\MadelineProto\Logger::NOTICE);
        $this->startUpdateSystem();
        return true;
    }
    /**
     * Login as bot.
     *
     * @param string $token Bot token
     *
     * @return \Generator
     */
    public function botLogin(string $token): \Generator
    {
        if ($this->authorized === MTProto::LOGGED_IN) {
            return;
        }
        $callbacks = [$this, $this->referenceDatabase];
        $this->TL->updateCallbacks($callbacks);
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_bot'], \danog\MadelineProto\Logger::NOTICE);
        $this->authorization = yield from $this->methodCallAsyncRead(
            'auth.importBotAuthorization',
            [
                'bot_auth_token' => $token,
                'api_id' => $this->settings->getAppInfo()->getApiId(),
                'api_hash' => $this->settings->getAppInfo()->getApiHash(),
            ]
        );
        $this->authorized = MTProto::LOGGED_IN;
        $this->authorized_dc = $this->datacenter->curdc;
        $this->datacenter->getDataCenterConnection($this->datacenter->curdc)->authorized(true);
        $this->updates = [];
        $this->updates_key = 0;
        yield from $this->initAuthorization();
        $this->startUpdateSystem();
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_ok'], \danog\MadelineProto\Logger::NOTICE);
        return $this->authorization;
    }
    /**
     * Login as user.
     *
     * @param string  $number   Phone number
     * @param integer $sms_type SMS type
     *
     * @return \Generator
     */
    public function phoneLogin($number, $sms_type = 5): \Generator
    {
        if ($this->authorized === MTProto::LOGGED_IN) {
            $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['already_loggedIn'], \danog\MadelineProto\Logger::NOTICE);
            yield from $this->logout();
        }
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_code_sending'], \danog\MadelineProto\Logger::NOTICE);
        $this->authorization = yield from $this->methodCallAsyncRead(
            'auth.sendCode',
            [
                'settings' => ['_' => 'codeSettings'],
                'phone_number' => $number,
                'sms_type' => $sms_type,
                'api_id' => $this->settings->getAppInfo()->getApiId(),
                'api_hash' => $this->settings->getAppInfo()->getApiHash(),
                'lang_code' => $this->settings->getAppInfo()->getLangCode()
            ]
        );
        $this->authorized_dc = $this->datacenter->curdc;
        $this->authorization['phone_number'] = $number;
        //$this->authorization['_'] .= 'MP';
        $this->authorized = MTProto::WAITING_CODE;
        $this->updates = [];
        $this->updates_key = 0;
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_code_sent'], \danog\MadelineProto\Logger::NOTICE);
        return $this->authorization;
    }
    /**
     * Complet user login using login code.
     *
     * @param string $code Login code
     *
     * @return \Generator
     */
    public function completePhoneLogin($code): \Generator
    {
        if ($this->authorized !== MTProto::WAITING_CODE) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['login_code_uncalled']);
        }
        $this->authorized = MTProto::NOT_LOGGED_IN;
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_user'], \danog\MadelineProto\Logger::NOTICE);
        try {
            $authorization = yield from $this->methodCallAsyncRead('auth.signIn', ['phone_number' => $this->authorization['phone_number'], 'phone_code_hash' => $this->authorization['phone_code_hash'], 'phone_code' => (string) $code]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            if ($e->rpc === 'SESSION_PASSWORD_NEEDED') {
                $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_2fa_enabled'], \danog\MadelineProto\Logger::NOTICE);
                $this->authorization = yield from $this->methodCallAsyncRead('account.getPassword', []);
                if (!isset($this->authorization['hint'])) {
                    $this->authorization['hint'] = '';
                }
                $this->authorized = MTProto::WAITING_PASSWORD;
                return $this->authorization;
            }
            if ($e->rpc === 'PHONE_NUMBER_UNOCCUPIED') {
                $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_need_signup'], \danog\MadelineProto\Logger::NOTICE);
                $this->authorized = MTProto::WAITING_SIGNUP;
                $this->authorization['phone_code'] = $code;
                return ['_' => 'account.needSignup'];
            }
            throw $e;
        }
        if ($authorization['_'] === 'auth.authorizationSignUpRequired') {
            $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_need_signup'], \danog\MadelineProto\Logger::NOTICE);
            $this->authorized = MTProto::WAITING_SIGNUP;
            $this->authorization['phone_code'] = $code;
            $authorization['_'] = 'account.needSignup';
            return $authorization;
        }
        $this->authorized = MTProto::LOGGED_IN;
        $this->authorization = $authorization;
        $this->datacenter->getDataCenterConnection($this->datacenter->curdc)->authorized(true);
        yield from $this->initAuthorization();
        yield from $this->getPhoneConfig();
        $this->startUpdateSystem();
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_ok'], \danog\MadelineProto\Logger::NOTICE);
        return $this->authorization;
    }
    /**
     * Import authorization.
     *
     * @param array<int, string> $authorization Authorization info
     * @param int $mainDcID Main DC ID
     *
     * @return \Generator
     */
    public function importAuthorization(array $authorization, int $mainDcID): \Generator
    {
        if ($this->authorized === MTProto::LOGGED_IN) {
            $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['already_loggedIn'], \danog\MadelineProto\Logger::NOTICE);
            yield from $this->logout();
        }
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_auth_key'], \danog\MadelineProto\Logger::NOTICE);
        foreach ($this->datacenter->getDataCenterConnections() as $connection) {
            $connection->resetSession();
            $connection->setPermAuthKey(null);
            $connection->setTempAuthKey(null);
            $connection->authorized(false);
        }
        foreach ($authorization as $dc_id => $auth_key) {
            $this->logger->logger("Setting auth key in DC $dc_id", \danog\MadelineProto\Logger::NOTICE);
            if (!\is_array($auth_key)) {
                $auth_key = ['auth_key' => $auth_key];
            }
            $auth_key = new PermAuthKey($auth_key);
            $auth_key->authorized(true);
            $dataCenterConnection = $this->datacenter->getDataCenterConnection($dc_id);
            $dataCenterConnection->setPermAuthKey($auth_key);
        }
        $this->authorized_dc = $mainDcID;
        $this->authorized = MTProto::LOGGED_IN;
        yield from $this->connectToAllDcs(true);
        yield from $this->initAuthorization();
        yield from $this->getPhoneConfig();
        $res = (yield from $this->fullGetSelf());
        $callbacks = [$this, $this->referenceDatabase];
        if (!($this->authorization['user']['bot'] ?? false)) {
            $callbacks[] = $this->minDatabase;
        }
        $this->TL->updateCallbacks($callbacks);
        $this->startUpdateSystem();
        return $res;
    }
    /**
     * Export authorization.
     *
     * @return \Generator
     *
     * @psalm-return \Generator<mixed, array|bool, mixed, array{0: int|string, 1: string}>
     */
    public function exportAuthorization(): \Generator
    {
        if ($this->authorized !== MTProto::LOGGED_IN) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['not_loggedIn']);
        }
        yield from $this->fullGetSelf();
        $this->authorized_dc = $this->datacenter->curdc;
        return [$this->datacenter->curdc, $this->datacenter->getDataCenterConnection($this->datacenter->curdc)->getPermAuthKey()->getAuthKey()];
    }
    /**
     * Complete signup to Telegram.
     *
     * @param string $first_name First name
     * @param string $last_name  Last name
     *
     * @return \Generator
     */
    public function completeSignup(string $first_name, string $last_name = ''): \Generator
    {
        if ($this->authorized !== MTProto::WAITING_SIGNUP) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['signup_uncalled']);
        }
        $this->authorized = MTProto::NOT_LOGGED_IN;
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['signing_up'], \danog\MadelineProto\Logger::NOTICE);
        $this->authorization = yield from $this->methodCallAsyncRead('auth.signUp', ['phone_number' => $this->authorization['phone_number'], 'phone_code_hash' => $this->authorization['phone_code_hash'], 'phone_code' => $this->authorization['phone_code'], 'first_name' => $first_name, 'last_name' => $last_name]);
        $this->authorized = MTProto::LOGGED_IN;
        $this->datacenter->getDataCenterConnection($this->datacenter->curdc)->authorized(true);
        yield from $this->initAuthorization();
        yield from $this->getPhoneConfig();
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['signup_ok'], \danog\MadelineProto\Logger::NOTICE);
        $this->startUpdateSystem();
        return $this->authorization;
    }
    /**
     * Complete 2FA login.
     *
     * @param string $password Password
     *
     * @return \Generator
     */
    public function complete2faLogin(string $password): \Generator
    {
        if ($this->authorized !== MTProto::WAITING_PASSWORD) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['2fa_uncalled']);
        }
        $this->authorized = MTProto::NOT_LOGGED_IN;
        $hasher = new PasswordCalculator($this->logger);
        $hasher->addInfo(yield from $this->methodCallAsyncRead('account.getPassword', []));
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_user'], \danog\MadelineProto\Logger::NOTICE);
        $this->authorization = yield from $this->methodCallAsyncRead('auth.checkPassword', ['password' => $hasher->getCheckPassword($password)]);
        $this->authorized = MTProto::LOGGED_IN;
        $this->datacenter->getDataCenterConnection($this->datacenter->curdc)->authorized(true);
        yield from $this->initAuthorization();
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['login_ok'], \danog\MadelineProto\Logger::NOTICE);
        yield from $this->getPhoneConfig();
        $this->startUpdateSystem();
        return $this->authorization;
    }
    /**
     * Update the 2FA password.
     *
     * The params array can contain password, new_password, email and hint params.
     *
     * @param array $params The params
     *
     * @return \Generator
     */
    public function update2fa(array $params): \Generator
    {
        $hasher = new PasswordCalculator($this->logger);
        $hasher->addInfo(yield from $this->methodCallAsyncRead('account.getPassword', []));
        return yield from $this->methodCallAsyncRead('account.updatePasswordSettings', $hasher->getPassword($params));
    }
}
