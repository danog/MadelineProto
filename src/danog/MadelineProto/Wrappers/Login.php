<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Wrappers;

use danog\MadelineProto\Exception;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\PermAuthKey;
use danog\MadelineProto\MTProtoTools\PasswordCalculator;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Settings;

/**
 * Manages logging in and out.
 *
 * @property Settings $settings Settings
 */
trait Login
{
    /**
     * Login as bot.
     *
     * @param string $token Bot token
     */
    public function botLogin(string $token): array|null
    {
        if ($this->authorized === MTProto::LOGGED_IN) {
            return null;
        }
        $callbacks = [$this, $this->referenceDatabase];
        $this->TL->updateCallbacks($callbacks);
        $this->logger->logger(Lang::$current_lang['login_bot'], Logger::NOTICE);
        $this->authorization = $this->methodCallAsyncRead(
            'auth.importBotAuthorization',
            [
                'bot_auth_token' => $token,
                'api_id' => $this->settings->getAppInfo()->getApiId(),
                'api_hash' => $this->settings->getAppInfo()->getApiHash(),
            ],
        );
        $this->authorized = MTProto::LOGGED_IN;
        $this->authorized_dc = $this->datacenter->currentDatacenter;
        $this->datacenter->getDataCenterConnection($this->datacenter->currentDatacenter)->authorized(true);
        $this->initAuthorization();
        $this->startUpdateSystem();
        $this->logger->logger(Lang::$current_lang['login_ok'], Logger::NOTICE);
        return $this->authorization;
    }
    /**
     * Login as user.
     *
     * @param string  $number   Phone number
     * @param integer $sms_type SMS type
     */
    public function phoneLogin(string $number, int $sms_type = 5)
    {
        if ($this->authorized === MTProto::LOGGED_IN) {
            throw new Exception(Lang::$current_lang['already_loggedIn']);
        }
        $this->logger->logger(Lang::$current_lang['login_code_sending'], Logger::NOTICE);
        $this->authorization = $this->methodCallAsyncRead(
            'auth.sendCode',
            [
                'settings' => ['_' => 'codeSettings'],
                'phone_number' => $number,
                'sms_type' => $sms_type,
                'api_id' => $this->settings->getAppInfo()->getApiId(),
                'api_hash' => $this->settings->getAppInfo()->getApiHash(),
                'lang_code' => $this->settings->getAppInfo()->getLangCode(),
            ],
        );
        $this->authorized_dc = $this->datacenter->currentDatacenter;
        $this->authorization['phone_number'] = $number;
        //$this->authorization['_'] .= 'MP';
        $this->authorized = MTProto::WAITING_CODE;
        $this->logger->logger(Lang::$current_lang['login_code_sent'], Logger::NOTICE);
        return $this->authorization;
    }
    /**
     * Complet user login using login code.
     *
     * @param string $code Login code
     */
    public function completePhoneLogin(string $code)
    {
        if ($this->authorized !== MTProto::WAITING_CODE) {
            throw new Exception(Lang::$current_lang['login_code_uncalled']);
        }
        $this->authorized = MTProto::NOT_LOGGED_IN;
        $this->logger->logger(Lang::$current_lang['login_user'], Logger::NOTICE);
        try {
            $authorization = $this->methodCallAsyncRead('auth.signIn', ['phone_number' => $this->authorization['phone_number'], 'phone_code_hash' => $this->authorization['phone_code_hash'], 'phone_code' => $code]);
        } catch (RPCErrorException $e) {
            if ($e->rpc === 'SESSION_PASSWORD_NEEDED') {
                $this->logger->logger(Lang::$current_lang['login_2fa_enabled'], Logger::NOTICE);
                $this->authorization = $this->methodCallAsyncRead('account.getPassword', []);
                if (!isset($this->authorization['hint'])) {
                    $this->authorization['hint'] = '';
                }
                $this->authorized = MTProto::WAITING_PASSWORD;
                return $this->authorization;
            }
            if ($e->rpc === 'PHONE_NUMBER_UNOCCUPIED') {
                $this->logger->logger(Lang::$current_lang['login_need_signup'], Logger::NOTICE);
                $this->authorized = MTProto::WAITING_SIGNUP;
                $this->authorization['phone_code'] = $code;
                return ['_' => 'account.needSignup'];
            }
            throw $e;
        }
        if ($authorization['_'] === 'auth.authorizationSignUpRequired') {
            $this->logger->logger(Lang::$current_lang['login_need_signup'], Logger::NOTICE);
            $this->authorized = MTProto::WAITING_SIGNUP;
            $this->authorization['phone_code'] = $code;
            $authorization['_'] = 'account.needSignup';
            return $authorization;
        }
        $this->authorized = MTProto::LOGGED_IN;
        $this->authorization = $authorization;
        $this->datacenter->getDataCenterConnection($this->datacenter->currentDatacenter)->authorized(true);
        $this->initAuthorization();
        $this->getPhoneConfig();
        $this->startUpdateSystem();
        $this->logger->logger(Lang::$current_lang['login_ok'], Logger::NOTICE);
        return $this->authorization;
    }
    /**
     * Import authorization.
     *
     * @param array<int, string> $authorization Authorization info
     * @param int $mainDcID Main DC ID
     */
    public function importAuthorization(array $authorization, int $mainDcID)
    {
        if ($this->authorized === MTProto::LOGGED_IN) {
            throw new Exception(Lang::$current_lang['already_loggedIn']);
        }
        $this->logger->logger(Lang::$current_lang['login_auth_key'], Logger::NOTICE);
        foreach ($this->datacenter->getDataCenterConnections() as $connection) {
            $connection->resetSession();
            $connection->setPermAuthKey(null);
            $connection->setTempAuthKey(null);
            $connection->authorized(false);
        }
        foreach ($authorization as $dc_id => $auth_key) {
            $this->logger->logger("Setting auth key in DC $dc_id", Logger::NOTICE);
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
        $this->connectToAllDcs(true);
        $this->initAuthorization();
        $this->getPhoneConfig();
        $res = ($this->fullGetSelf());
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
     * @return array{0: (int|string), 1: string}
     */
    public function exportAuthorization(): array
    {
        if ($this->authorized !== MTProto::LOGGED_IN) {
            throw new Exception(Lang::$current_lang['not_loggedIn']);
        }
        $this->fullGetSelf();
        $this->authorized_dc = $this->datacenter->currentDatacenter;
        return [$this->datacenter->currentDatacenter, $this->datacenter->getDataCenterConnection($this->datacenter->currentDatacenter)->getPermAuthKey()->getAuthKey()];
    }
    /**
     * Complete signup to Telegram.
     *
     * @param string $first_name First name
     * @param string $last_name  Last name
     */
    public function completeSignup(string $first_name, string $last_name = '')
    {
        if ($this->authorized !== MTProto::WAITING_SIGNUP) {
            throw new Exception(Lang::$current_lang['signup_uncalled']);
        }
        $this->authorized = MTProto::NOT_LOGGED_IN;
        $this->logger->logger(Lang::$current_lang['signing_up'], Logger::NOTICE);
        $this->authorization = $this->methodCallAsyncRead('auth.signUp', ['phone_number' => $this->authorization['phone_number'], 'phone_code_hash' => $this->authorization['phone_code_hash'], 'phone_code' => $this->authorization['phone_code'], 'first_name' => $first_name, 'last_name' => $last_name]);
        $this->authorized = MTProto::LOGGED_IN;
        $this->datacenter->getDataCenterConnection($this->datacenter->currentDatacenter)->authorized(true);
        $this->initAuthorization();
        $this->getPhoneConfig();
        $this->logger->logger(Lang::$current_lang['signup_ok'], Logger::NOTICE);
        $this->startUpdateSystem();
        return $this->authorization;
    }
    /**
     * Complete 2FA login.
     *
     * @param string $password Password
     */
    public function complete2faLogin(string $password)
    {
        if ($this->authorized !== MTProto::WAITING_PASSWORD) {
            throw new Exception(Lang::$current_lang['2fa_uncalled']);
        }
        $this->authorized = MTProto::NOT_LOGGED_IN;
        $hasher = new PasswordCalculator($this->logger);
        $hasher->addInfo($this->methodCallAsyncRead('account.getPassword', []));
        $this->logger->logger(Lang::$current_lang['login_user'], Logger::NOTICE);
        $this->authorization = $this->methodCallAsyncRead('auth.checkPassword', ['password' => $hasher->getCheckPassword($password)]);
        $this->authorized = MTProto::LOGGED_IN;
        $this->datacenter->getDataCenterConnection($this->datacenter->currentDatacenter)->authorized(true);
        $this->initAuthorization();
        $this->logger->logger(Lang::$current_lang['login_ok'], Logger::NOTICE);
        $this->getPhoneConfig();
        $this->startUpdateSystem();
        return $this->authorization;
    }
    /**
     * Update the 2FA password.
     *
     * The params array can contain password, new_password, email and hint params.
     *
     * @param array $params The params
     */
    public function update2fa(array $params): void
    {
        $hasher = new PasswordCalculator($this->logger);
        $hasher->addInfo($this->methodCallAsyncRead('account.getPassword', []));
        $this->methodCallAsyncRead('account.updatePasswordSettings', $hasher->getPassword($params));
    }
}
