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

use danog\MadelineProto\Tools;

/**
 * Manages simple logging in and out.
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
        if ($this->authorized === self::LOGGED_IN) {
            return yield from $this->fullGetSelf();
        }
        if (PHP_SAPI === 'cli') {
            if (\strpos(yield Tools::readLine('Do you want to login as user or bot (u/b)? '), 'b') !== false) {
                yield from $this->botLogin(yield Tools::readLine('Enter your bot token: '));
            } else {
                yield from $this->phoneLogin(yield Tools::readLine('Enter your phone number: '));
                $authorization = (yield from $this->completePhoneLogin(yield Tools::readLine('Enter the phone code: ')));
                if ($authorization['_'] === 'account.password') {
                    $authorization = (yield from $this->complete2faLogin(yield Tools::readLine('Please enter your password (hint '.$authorization['hint'].'): ')));
                }
                if ($authorization['_'] === 'account.needSignup') {
                    $authorization = (yield from $this->completeSignup(yield Tools::readLine('Please enter your first name: '), yield Tools::readLine('Please enter your last name (can be empty): ')));
                }
            }
            $this->serialize();
            return yield from $this->fullGetSelf();
        }
        if ($this->authorized === self::NOT_LOGGED_IN) {
            if (isset($_POST['phone_number'])) {
                yield from $this->webPhoneLogin();
            } elseif (isset($_POST['token'])) {
                yield from $this->webBotLogin();
            } else {
                yield from $this->webEcho();
            }
        } elseif ($this->authorized === self::WAITING_CODE) {
            if (isset($_POST['phone_code'])) {
                yield from $this->webCompletePhoneLogin();
            } else {
                yield from $this->webEcho("You didn't provide a phone code!");
            }
        } elseif ($this->authorized === self::WAITING_PASSWORD) {
            if (isset($_POST['password'])) {
                yield from $this->webComplete2faLogin();
            } else {
                yield from $this->webEcho("You didn't provide the password!");
            }
        } elseif ($this->authorized === self::WAITING_SIGNUP) {
            if (isset($_POST['first_name'])) {
                yield from $this->webCompleteSignup();
            } else {
                yield from $this->webEcho("You didn't provide the first name!");
            }
        }
        if ($this->authorized === self::LOGGED_IN) {
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
            yield from $this->webEcho('ERROR: '.$e->getMessage().'. Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            yield from $this->webEcho('ERROR: '.$e->getMessage().'. Try again.');
        }
    }
    private function webCompletePhoneLogin(): \Generator
    {
        try {
            yield from $this->completePhoneLogin($_POST['phone_code']);
            yield from $this->webEcho();
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield from $this->webEcho('ERROR: '.$e->getMessage().'. Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            yield from $this->webEcho('ERROR: '.$e->getMessage().'. Try again.');
        }
    }
    private function webComplete2faLogin(): \Generator
    {
        try {
            yield from $this->complete2faLogin($_POST['password']);
            yield from $this->webEcho();
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield from $this->webEcho('ERROR: '.$e->getMessage().'. Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            yield from $this->webEcho('ERROR: '.$e->getMessage().'. Try again.');
        }
    }
    private function webCompleteSignup(): \Generator
    {
        try {
            yield from $this->completeSignup($_POST['first_name'], isset($_POST['last_name']) ? $_POST['last_name'] : '');
            yield from $this->webEcho();
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield from $this->webEcho('ERROR: '.$e->getMessage().'. Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            yield from $this->webEcho('ERROR: '.$e->getMessage().'. Try again.');
        }
    }
    private function webBotLogin(): \Generator
    {
        try {
            yield from $this->botLogin($_POST['token']);
            yield from $this->webEcho();
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield from $this->webEcho('ERROR: '.$e->getMessage().'. Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            yield from $this->webEcho('ERROR: '.$e->getMessage().'. Try again.');
        }
    }
}
