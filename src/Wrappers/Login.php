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

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\DeferredCancellation;
use Amp\DeferredFuture;
use AssertionError;
use danog\MadelineProto\API;
use danog\MadelineProto\DataCenter;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto\PermAuthKey;
use danog\MadelineProto\MTProto\TempAuthKey;
use danog\MadelineProto\MTProtoTools\PasswordCalculator;
use danog\MadelineProto\RPCError\PasswordHashInvalidError;
use danog\MadelineProto\RPCError\SessionPasswordNeededError;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Settings;
use danog\MadelineProto\TL\Types\LoginQrCode;
use danog\MadelineProto\Tools;

/**
 * Manages logging in and out.
 *
 * @property Settings     $settings    Settings
 * @property ?LoginQrCode $loginQrCode
 * @internal
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
        if ($this->authorized === \danog\MadelineProto\API::LOGGED_IN) {
            return null;
        }
        $callbacks = [$this, $this->referenceDatabase, $this->peerDatabase];
        /** @psalm-suppress InvalidArgument */
        $this->TL->updateCallbacks($callbacks);
        $this->logger->logger(Lang::$current_lang['login_bot'], Logger::NOTICE);
        return $this->processAuthorization($this->methodCallAsyncRead(
            'auth.importBotAuthorization',
            [
                'bot_auth_token' => $token,
                'api_id' => $this->settings->getAppInfo()->getApiId(),
                'api_hash' => $this->settings->getAppInfo()->getApiHash(),
            ],
        ));
    }

    private ?DeferredCancellation $qrLoginDeferred = null;
    /**
     * Initiates QR code login.
     *
     * Returns a QR code login helper object, that can be used to render the QR code, display the link directly, wait for login, QR code expiration and much more.
     *
     * Returns null if we're already logged in, or if we're waiting for a password (use getAuthorization to distinguish between the two cases).
     */
    public function qrLogin(): ?LoginQrCode
    {
        if ($this->authorized === \danog\MadelineProto\API::LOGGED_IN) {
            return null;
        } elseif ($this->authorized === \danog\MadelineProto\API::WAITING_PASSWORD) {
            return null;
        } elseif ($this->authorized !== API::NOT_LOGGED_IN) {
            throw new AssertionError("Unexpected state {$this->authorized}!");
        }
        $this->qrLoginDeferred ??= new DeferredCancellation;
        if (!$this->loginQrCode || $this->loginQrCode->isExpired()) {
            try {
                $authorization = $this->methodCallAsyncRead(
                    'auth.exportLoginToken',
                    [
                        'api_id' => $this->settings->getAppInfo()->getApiId(),
                        'api_hash' => $this->settings->getAppInfo()->getApiHash(),
                    ],
                );
                $datacenter = $this->datacenter->currentDatacenter;
                if ($authorization['_'] === 'auth.loginToken') {
                    return $this->loginQrCode = new LoginQrCode(
                        $this,
                        "tg://login?token=".Tools::base64urlEncode((string) $authorization['token']),
                        $authorization['expires']
                    );
                }

                if ($authorization['_'] === 'auth.loginTokenMigrateTo') {
                    $datacenter = $this->isTestMode() ? 10_000 + $authorization['dc_id'] : $authorization['dc_id'];
                    $this->authorized_dc = $datacenter;
                    $authorization = $this->methodCallAsyncRead(
                        'auth.importLoginToken',
                        $authorization,
                        $datacenter
                    );
                }
                $this->processAuthorization($authorization['authorization']);
            } catch (SessionPasswordNeededError) {
                $this->logger->logger(Lang::$current_lang['login_2fa_enabled'], Logger::NOTICE);
                $this->authorization = $this->methodCallAsyncRead('account.getPassword', [], $datacenter ?? null);
                if (!isset($this->authorization['hint'])) {
                    $this->authorization['hint'] = '';
                }
                $this->authorized = \danog\MadelineProto\API::WAITING_PASSWORD;
                $this->qrLoginDeferred?->cancel();
                $this->qrLoginDeferred = null;
                return null;
            }
            return null;
        }
        return $this->loginQrCode;
    }
    /**
     * @internal
     */
    public function getQrLoginCancellation(): Cancellation
    {
        if ($this->qrLoginDeferred) {
            return $this->qrLoginDeferred->getCancellation();
        }
        $c = new DeferredCancellation;
        $c->cancel();
        return $c->getCancellation();
    }
    /**
     * Logout the session.
     */
    public function logout(): void
    {
        if ($this->authorized === API::LOGGED_IN) {
            $this->authorized = API::LOGGED_OUT;
            $this->methodCallAsyncRead('auth.logOut', []);
        }
        $this->authorized = API::LOGGED_OUT;
        if ($this->hasEventHandler()) {
            $this->stop();
        } else {
            $this->ipcServer?->stop();
        }
    }
    /** @internal */
    public function waitQrLogin(): void
    {
        if (!$this->qrLoginDeferred) {
            return;
        }
        try {
            (new DeferredFuture)->getFuture()->await($this->getQrLoginCancellation());
        } catch (CancelledException) {
        }
    }
    /**
     * Login as user.
     *
     * @param string  $number   Phone number
     * @param integer $sms_type SMS type
     */
    public function phoneLogin(string $number, int $sms_type = 5): array
    {
        if ($this->authorized === \danog\MadelineProto\API::LOGGED_IN) {
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
        $this->authorization['phone_number'] = $number;
        //$this->authorization['_'] .= 'MP';
        $this->authorized = \danog\MadelineProto\API::WAITING_CODE;
        $this->logger->logger(Lang::$current_lang['login_code_sent'], Logger::NOTICE);
        return $this->authorization;
    }
    /**
     * Complet user login using login code.
     *
     * @param string $code Login code
     */
    public function completePhoneLogin(string $code): array
    {
        if ($this->authorized !== \danog\MadelineProto\API::WAITING_CODE) {
            throw new Exception(Lang::$current_lang['login_code_uncalled']);
        }
        $this->authorized = API::NOT_LOGGED_IN;
        $this->logger->logger(Lang::$current_lang['login_user'], Logger::NOTICE);
        try {
            $authorization = $this->methodCallAsyncRead('auth.signIn', ['phone_number' => $this->authorization['phone_number'], 'phone_code_hash' => $this->authorization['phone_code_hash'], 'phone_code' => $code]);
        } catch (SessionPasswordNeededError) {
            $this->logger->logger(Lang::$current_lang['login_2fa_enabled'], Logger::NOTICE);
            $this->authorization = $this->methodCallAsyncRead('account.getPassword', []);
            if (!isset($this->authorization['hint'])) {
                $this->authorization['hint'] = '';
            }
            $this->authorized = \danog\MadelineProto\API::WAITING_PASSWORD;
            return $this->authorization;
        } catch (RPCErrorException $e) {
            if ($e->rpc === 'PHONE_NUMBER_UNOCCUPIED') {
                $this->logger->logger(Lang::$current_lang['login_need_signup'], Logger::NOTICE);
                $this->authorized = \danog\MadelineProto\API::WAITING_SIGNUP;
                $this->authorization['phone_code'] = $code;
                return ['_' => 'account.needSignup'];
            }
            throw $e;
        }
        if ($authorization['_'] === 'auth.authorizationSignUpRequired') {
            $this->logger->logger(Lang::$current_lang['login_need_signup'], Logger::NOTICE);
            $this->authorized = \danog\MadelineProto\API::WAITING_SIGNUP;
            $this->authorization['phone_code'] = $code;
            $authorization['_'] = 'account.needSignup';
            return $authorization;
        }
        return $this->processAuthorization($authorization);
    }
    /**
     * Import authorization.
     *
     * @param array<int, string> $authorization Authorization info
     * @param int                $mainDcID      Main DC ID
     */
    public function importAuthorization(array $authorization, int $mainDcID): array
    {
        if ($this->authorized === \danog\MadelineProto\API::LOGGED_IN) {
            throw new Exception(Lang::$current_lang['already_loggedIn']);
        }
        $this->logger->logger(Lang::$current_lang['login_auth_key'], Logger::NOTICE);

        $this->datacenter = new DataCenter($this);
        $auth_key = $authorization[$mainDcID];
        if (!\is_array($auth_key)) {
            $auth_key = ['auth_key' => $auth_key];
        }
        $dataCenterConnection = $this->datacenter->getDataCenterConnection($mainDcID);

        $this->logger->logger("Setting auth key in DC $mainDcID", Logger::NOTICE);
        $auth_key = new PermAuthKey($auth_key);
        $auth_key->authorized(true);
        $auth_key->setServerSalt(random_bytes(8));
        $dataCenterConnection->setPermAuthKey($auth_key);
        $dataCenterConnection->setTempAuthKey(new TempAuthKey());
        $dataCenterConnection->bind($this->settings->getAuth()->getPfs());

        $this->datacenter->currentDatacenter = $mainDcID;
        $this->authorized_dc = $mainDcID;
        $this->authorized = \danog\MadelineProto\API::LOGGED_IN;
        $this->getPhoneConfig();
        $res = ($this->fullGetSelf());
        $callbacks = [$this, $this->referenceDatabase, $this->peerDatabase];
        if (!($this->authorization['user']['bot'] ?? false)) {
            $callbacks[] = $this->minDatabase;
        }
        /** @psalm-suppress InvalidArgument */
        $this->TL->updateCallbacks($callbacks);
        $this->startUpdateSystem();
        $this->qrLoginDeferred?->cancel();
        $this->qrLoginDeferred = null;
        $this->fullGetSelf();
        return $res;
    }
    /**
     * Export authorization.
     *
     * @return array{0: (int|string), 1: string}
     */
    public function exportAuthorization(): array
    {
        if ($this->authorized !== \danog\MadelineProto\API::LOGGED_IN) {
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
    public function completeSignup(string $first_name, string $last_name = ''): array
    {
        if ($this->authorized !== \danog\MadelineProto\API::WAITING_SIGNUP) {
            throw new Exception(Lang::$current_lang['signup_uncalled']);
        }
        $this->authorized = API::NOT_LOGGED_IN;
        $this->logger->logger(Lang::$current_lang['signing_up'], Logger::NOTICE);
        return $this->processAuthorization($this->methodCallAsyncRead('auth.signUp', ['phone_number' => $this->authorization['phone_number'], 'phone_code_hash' => $this->authorization['phone_code_hash'], 'phone_code' => $this->authorization['phone_code'], 'first_name' => $first_name, 'last_name' => $last_name]));
    }
    /**
     * Complete 2FA login.
     *
     * @param string $password Password
     */
    public function complete2faLogin(string $password): array
    {
        if ($this->authorized !== \danog\MadelineProto\API::WAITING_PASSWORD) {
            throw new Exception(Lang::$current_lang['2fa_uncalled']);
        }
        $this->logger->logger(Lang::$current_lang['login_user'], Logger::NOTICE);
        try {
            $res = $this->methodCallAsyncRead('auth.checkPassword', ['password' => $password], $this->authorized_dc);
        } catch (PasswordHashInvalidError) {
            $res = $this->methodCallAsyncRead('auth.checkPassword', ['password' => $password], $this->authorized_dc);
        }
        return $this->processAuthorization($res);
    }
    private function processAuthorization(array $authorization): array
    {
        if ($this->authorized === \danog\MadelineProto\API::LOGGED_IN) {
            throw new Exception(Lang::$current_lang['already_loggedIn']);
        }
        $this->authorized_dc ??= $this->datacenter->currentDatacenter;
        $this->authorization = $authorization;
        $this->authorized = \danog\MadelineProto\API::LOGGED_IN;
        $this->datacenter->getDataCenterConnection($this->authorized_dc)->authorized(true);
        $this->qrLoginDeferred?->cancel();
        $this->qrLoginDeferred = null;
        $this->logger->logger(Lang::$current_lang['login_ok'], Logger::NOTICE);
        $this->fullGetSelf();
        $this->getPhoneConfig();
        $this->startUpdateSystem();
        $this->initDb();
        $this->serialize();
        return $authorization;
    }
    /**
     * Update the 2FA password.
     *
     * The params array can contain password, new_password, email and hint params.
     *
     * @param array{password?: string, new_password?: string, email?: string, hint?: string} $params The params
     */
    public function update2fa(array $params): void
    {
        $hasher = new PasswordCalculator($this->methodCallAsyncRead('account.getPassword', []));
        $this->methodCallAsyncRead('account.updatePasswordSettings', $hasher->getPassword($params));
    }
}
