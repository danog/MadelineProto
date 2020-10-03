<?php

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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Wrappers;

use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\Lang;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Settings;

use danog\MadelineProto\Tools;

/**
 * Manages simple logging in and out.
 *
 * @property Settings $settings Settings
 */
trait Start
{
    /**
     * Log in to telegram (via CLI or web).
     *
     * @return \Generator
     */
    public function start(): \Generator
    {
        if ((yield $this->getAuthorization()) === MTProto::LOGGED_IN) {
            return $this instanceof Client ? yield from $this->getSelf() : yield from $this->fullGetSelf();
        }
        if ($this->getWebTemplate() === 'legacy') {
            if ($this instanceof Client) {
                $settings = yield from $this->getSettings();
            } else {
                $settings = $this->settings;
            }
            $this->setWebTemplate($settings->getTemplates()->getHtmlTemplate());
        }
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            if (\strpos(yield Tools::readLine(Lang::$current_lang['loginChoosePrompt']), 'b') !== false) {
                yield from $this->botLogin(yield Tools::readLine(Lang::$current_lang['loginBot']));
            } else {
                yield from $this->phoneLogin(yield Tools::readLine(Lang::$current_lang['loginUser']));
                $authorization = (yield from $this->completePhoneLogin(yield Tools::readLine(Lang::$current_lang['loginUserCode'])));
                if ($authorization['_'] === 'account.password') {
                    $authorization = (yield from $this->complete2faLogin(yield Tools::readLine(\sprintf(Lang::$current_lang['loginUserPass'], $authorization['hint']))));
                }
                if ($authorization['_'] === 'account.needSignup') {
                    $authorization = (yield from $this->completeSignup(yield Tools::readLine(Lang::$current_lang['signupFirstName']), yield Tools::readLine(Lang::$current_lang['signupLastName'])));
                }
            }
            $this->serialize();
            return yield from $this->fullGetSelf();
        }
        if ((yield $this->getAuthorization()) === MTProto::NOT_LOGGED_IN) {
            if (isset($_POST['phone_number'])) {
                yield from $this->webPhoneLogin();
            } elseif (isset($_POST['token'])) {
                yield from $this->webBotLogin();
            } else {
                yield from $this->webEcho();
            }
        } elseif ((yield $this->getAuthorization()) === MTProto::WAITING_CODE) {
            if (isset($_POST['phone_code'])) {
                yield from $this->webCompletePhoneLogin();
            } else {
                yield from $this->webEcho(Lang::$current_lang['loginNoCode']);
            }
        } elseif ((yield $this->getAuthorization()) === MTProto::WAITING_PASSWORD) {
            if (isset($_POST['password'])) {
                yield from $this->webComplete2faLogin();
            } else {
                yield from $this->webEcho(Lang::$current_lang['loginNoPass']);
            }
        } elseif ((yield $this->getAuthorization()) === MTProto::WAITING_SIGNUP) {
            if (isset($_POST['first_name'])) {
                yield from $this->webCompleteSignup();
            } else {
                yield from $this->webEcho(Lang::$current_lang['loginNoName']);
            }
        }
        if ((yield $this->getAuthorization()) === MTProto::LOGGED_IN) {
            $this->serialize();
            return yield from $this->fullGetSelf();
        }
        exit;
    }
    private function webPhoneLogin(): \Generator
    {
        try {
            yield from $this->phoneLogin($_POST['phone_number']);
            yield from $this->webEcho();
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield from $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        } catch (\danog\MadelineProto\Exception $e) {
            yield from $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        }
    }
    private function webCompletePhoneLogin(): \Generator
    {
        try {
            yield from $this->completePhoneLogin($_POST['phone_code']);
            yield from $this->webEcho();
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield from $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        } catch (\danog\MadelineProto\Exception $e) {
            yield from $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        }
    }
    private function webComplete2faLogin(): \Generator
    {
        try {
            yield from $this->complete2faLogin($_POST['password']);
            yield from $this->webEcho();
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield from $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        } catch (\danog\MadelineProto\Exception $e) {
            yield from $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        }
    }
    private function webCompleteSignup(): \Generator
    {
        try {
            yield from $this->completeSignup($_POST['first_name'], isset($_POST['last_name']) ? $_POST['last_name'] : '');
            yield from $this->webEcho();
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield from $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        } catch (\danog\MadelineProto\Exception $e) {
            yield from $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        }
    }
    private function webBotLogin(): \Generator
    {
        try {
            yield from $this->botLogin($_POST['token']);
            yield from $this->webEcho();
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield from $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        } catch (\danog\MadelineProto\Exception $e) {
            yield from $this->webEcho(\sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        }
    }
}
