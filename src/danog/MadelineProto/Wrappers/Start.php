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
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Wrappers;

/**
 * Manages simple logging in and out.
 */
trait Start
{
    public function start_async()
    {
        if ($this->authorized === self::LOGGED_IN) {
            return yield $this->get_self_async();
        }
        if (php_sapi_name() === 'cli') {
            if (!function_exists('readline')) {
                $readline = function ($prompt = null) {
                    if ($prompt) {
                        echo $prompt;
                    }
                    $fp = fopen('php://stdin', 'r');
                    $line = rtrim(fgets($fp, 1024));

                    return $line;
                };
            } else {
                $readline = 'readline';
            }
            if (strpos($readline('Do you want to login as user or bot (u/b)? '), 'b') !== false) {
                yield $this->bot_login_async($readline('Enter your bot token: '));
            } else {
                yield $this->phone_login_async($readline('Enter your phone number: '));
                $authorization = yield $this->complete_phone_login_async($readline('Enter the phone code: '));
                if ($authorization['_'] === 'account.password') {
                    $authorization = yield $this->complete_2fa_login_async($readline('Please enter your password (hint '.$authorization['hint'].'): '));
                }
                if ($authorization['_'] === 'account.needSignup') {
                    $authorization = yield $this->complete_signup_async($readline('Please enter your first name: '), $readline('Please enter your last name (can be empty): '));
                }
            }
            $this->serialize();

            return yield $this->get_self_async();
        } else {
            if ($this->authorized === self::NOT_LOGGED_IN) {
                if (isset($_POST['phone_number'])) {
                    yield $this->web_phone_login();
                } elseif (isset($_POST['token'])) {
                    yield $this->web_bot_login();
                } else {
                    $this->web_echo();
                }
            } elseif ($this->authorized === self::WAITING_CODE) {
                if (isset($_POST['phone_code'])) {
                    yield $this->web_complete_phone_login();
                } else {
                    $this->web_echo("You didn't provide a phone code!");
                }
            } elseif ($this->authorized === self::WAITING_PASSWORD) {
                if (isset($_POST['password'])) {
                    yield $this->web_complete_2fa_login();
                } else {
                    $this->web_echo("You didn't provide the password!");
                }
            } elseif ($this->authorized === self::WAITING_SIGNUP) {
                if (isset($_POST['first_name'])) {
                    yield $this->web_complete_signup();
                } else {
                    $this->web_echo("You didn't provide the first name!");
                }
            }
            if ($this->authorized === self::LOGGED_IN) {
                $this->serialize();

                return yield $this->get_self_async();
            }
            exit;
        }
    }

    public function web_phone_login()
    {
        try {
            yield $this->phone_login_async($_POST['phone_number']);
            $this->web_echo();
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            $this->web_echo('ERROR: '.$e->getMessage().'. Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            $this->web_echo('ERROR: '.$e->getMessage().'. Try again.');
        }
    }

    public function web_complete_phone_login()
    {
        try {
            yield $this->complete_phone_login_async($_POST['phone_code']);
            $this->web_echo();
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            $this->web_echo('ERROR: '.$e->getMessage().'. Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            $this->web_echo('ERROR: '.$e->getMessage().'. Try again.');
        }
    }

    public function web_complete_2fa_login()
    {
        try {
            yield $this->complete_2fa_login_async($_POST['password']);
            $this->web_echo();
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            $this->web_echo('ERROR: '.$e->getMessage().'. Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            $this->web_echo('ERROR: '.$e->getMessage().'. Try again.');
        }
    }

    public function web_complete_signup()
    {
        try {
            yield $this->complete_signup_async($_POST['first_name'], isset($_POST['last_name']) ? $_POST['last_name'] : '');
            $this->web_echo();
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            $this->web_echo('ERROR: '.$e->getMessage().'. Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            $this->web_echo('ERROR: '.$e->getMessage().'. Try again.');
        }
    }

    public function web_bot_login()
    {
        try {
            yield $this->bot_login_async($_POST['token']);
            $this->web_echo();
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            $this->web_echo('ERROR: '.$e->getMessage().'. Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            $this->web_echo('ERROR: '.$e->getMessage().'. Try again.');
        }
    }
}
