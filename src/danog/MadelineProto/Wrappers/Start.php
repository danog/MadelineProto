<?php

/*
Copyright 2016-2018 Daniil Gentili
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
 * Manages simple logging in and out.
 */
trait Start
{
    public function start()
    {
        if ($this->authorized === self::LOGGED_IN) {
            return $this->get_self();
        }
        if (php_sapi_name() === 'cli') {
            if (!function_exists('readline')) {
                function readline($prompt = null)
                {
                    if ($prompt) {
                        echo $prompt;
                    }
                    $fp = fopen('php://stdin', 'r');
                    $line = rtrim(fgets($fp, 1024));
            
                    return $line;
                }
            }            
            if (strpos(readline('Do you want to login as user or bot (u/b)? '), 'b') !== false) {
                $this->bot_login(readline('Enter your bot token: '));
            } else {
                $this->phone_login(readline('Enter your phone number: '));
                $authorization = $this->complete_phone_login(readline('Enter the phone code: '));
                if ($authorization['_'] === 'account.password') {
                    $authorization = $this->complete_2fa_login(readline('Please enter your password (hint '.$authorization['hint'].'): '));
                }
                if ($authorization['_'] === 'account.needSignup') {
                    $authorization = $this->complete_signup(readline('Please enter your first name: '), readline('Please enter your last name (can be empty): '));
                }
            }
            $this->serialize();

            return $this->get_self();
        } else {
            if ($this->authorized === self::NOT_LOGGED_IN) {
                if (isset($_POST['phone_number'])) {
                    $this->web_phone_login();
                } elseif (isset($_POST['token'])) {
                    $this->web_bot_login();
                } else {
                    $this->web_echo();
                }
            } elseif ($this->authorized === self::WAITING_CODE) {
                if (isset($_POST['phone_code'])) {
                    $this->web_complete_phone_login();
                } else {
                    $this->web_echo("You didn't provide a phone code!");
                }
            } elseif ($this->authorized === self::WAITING_PASSWORD) {
                if (isset($_POST['password'])) {
                    $this->web_complete_2fa_login();
                } else {
                    $this->web_echo("You didn't provide the password!");
                }
            } elseif ($this->authorized === self::WAITING_SIGNUP) {
                if (isset($_POST['first_name'])) {
                    $this->web_complete_signup();
                } else {
                    $this->web_echo("You didn't provide the first name!");
                }
            }
            if ($this->authorized === self::LOGGED_IN) {
                $this->serialize();

                return $this->get_self();
            }
            exit;
        }
    }

    public function web_phone_login()
    {
        try {
            $this->phone_login($_POST['phone_number']);
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
            $this->complete_phone_login($_POST['phone_code']);
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
            $this->complete_2fa_login($_POST['password']);
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
            $this->complete_signup($_POST['first_name'], isset($_POST['last_name']) ? $_POST['last_name'] : '');
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
            $this->bot_login($_POST['token']);
            $this->web_echo();
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            $this->web_echo('ERROR: '.$e->getMessage().'. Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            $this->web_echo('ERROR: '.$e->getMessage().'. Try again.');
        }
    }
}
