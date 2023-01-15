<?php

declare(strict_types=1);

/**
 * Start module.
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
use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\Lang;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Tools;

use const PHP_SAPI;

/**
 * Manages simple logging in and out.
 *
 * @property Settings $settings Settings
 */
trait Start
{
    /**
     * Log in to telegram (via CLI or web).
     */
    public function start()
    {
        if (($this->getAuthorization()) === MTProto::LOGGED_IN) {
            return $this instanceof Client ? $this->getSelf() : $this->fullGetSelf();
        }
        if ($this->getWebTemplate() === 'legacy') {
            $settings = $this->getSettings();
            $this->setWebTemplate($settings->getTemplates()->getHtmlTemplate());
        }
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            if (\strpos(Tools::readLine(Lang::$current_lang['loginChoosePrompt']), 'b') !== false) {
                $this->botLogin(Tools::readLine(Lang::$current_lang['loginBot']));
            } else {
                $this->phoneLogin(Tools::readLine(Lang::$current_lang['loginUser']));
                $authorization = ($this->completePhoneLogin(Tools::readLine(Lang::$current_lang['loginUserCode'])));
                if ($authorization['_'] === 'account.password') {
                    $authorization = ($this->complete2faLogin(Tools::readLine(\sprintf(Lang::$current_lang['loginUserPass'], $authorization['hint']))));
                }
                if ($authorization['_'] === 'account.needSignup') {
                    $authorization = ($this->completeSignup(Tools::readLine(Lang::$current_lang['signupFirstName']), Tools::readLine(Lang::$current_lang['signupLastName'])));
                }
            }
            $this->serialize();
            return $this->fullGetSelf();
        }
        if (($this->getAuthorization()) === MTProto::NOT_LOGGED_IN) {
            if (isset($_POST['phone_number'])) {
                $this->webPhoneLogin();
            } elseif (isset($_POST['token'])) {
                $this->webBotLogin();
            } else {
                $this->webEcho();
            }
        } elseif (($this->getAuthorization()) === MTProto::WAITING_CODE) {
            if (isset($_POST['phone_code'])) {
                $this->webCompletePhoneLogin();
            } else {
                $this->webEcho(Lang::$current_lang['loginNoCode']);
            }
        } elseif (($this->getAuthorization()) === MTProto::WAITING_PASSWORD) {
            if (isset($_POST['password'])) {
                $this->webComplete2faLogin();
            } else {
                $this->webEcho(Lang::$current_lang['loginNoPass']);
            }
        } elseif (($this->getAuthorization()) === MTProto::WAITING_SIGNUP) {
            if (isset($_POST['first_name'])) {
                $this->webCompleteSignup();
            } else {
                $this->webEcho(Lang::$current_lang['loginNoName']);
            }
        }
        if (($this->getAuthorization()) === MTProto::LOGGED_IN) {
            $this->serialize();
            return $this->fullGetSelf();
        }
        die;
    }
    private function webPhoneLogin(): void
    {
        try {
            $this->phoneLogin($_POST['phone_number']);
            $this->webEcho();
        } catch (RPCErrorException $e) {
            $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        } catch (Exception $e) {
            $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        }
    }
    private function webCompletePhoneLogin(): void
    {
        try {
            $this->completePhoneLogin($_POST['phone_code']);
            $this->webEcho();
        } catch (RPCErrorException $e) {
            $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        } catch (Exception $e) {
            $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        }
    }
    private function webComplete2faLogin(): void
    {
        try {
            $this->complete2faLogin($_POST['password']);
            $this->webEcho();
        } catch (RPCErrorException $e) {
            $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        } catch (Exception $e) {
            $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        }
    }
    private function webCompleteSignup(): void
    {
        try {
            $this->completeSignup($_POST['first_name'], $_POST['last_name'] ?? '');
            $this->webEcho();
        } catch (RPCErrorException $e) {
            $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        } catch (Exception $e) {
            $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        }
    }
    private function webBotLogin(): void
    {
        try {
            $this->botLogin($_POST['token']);
            $this->webEcho();
        } catch (RPCErrorException $e) {
            $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        } catch (Exception $e) {
            $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        }
    }
}
