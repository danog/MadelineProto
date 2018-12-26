<?php

/**
 * ApiStart module.
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
trait ApiStart
{
    public function api_start()
    {
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
            echo 'You did not define a valid API ID/API hash. Do you want to define it now manually, or automatically? (m/a)
Note that you can also provide the API parameters directly in the code using the settings: https://docs.madelineproto.xyz/docs/SETTINGS.html#settingsapp_infoapi_id'.PHP_EOL;
            if (strpos($res = $readline('Your choice (m/a): '), 'm') !== false) {
                echo '1) Login to my.telegram.org
2) Go to API development tools
3) App title: your app\'s name, can be anything
    Short name: your app\'s short name, can be anything
    URL: your app/website\'s URL, or t.me/yourusername
    Platform: anything
    Description: Describe your app here
4) Click on create application'.PHP_EOL;
                $app['api_id'] = $readline('5) Enter your API ID: ');
                $app['api_hash'] = $readline('6) Enter your API hash: ');

                return $app;
            } else {
                $this->my_telegram_org_wrapper = new \danog\MadelineProto\MyTelegramOrgWrapper($readline('Enter a phone number that is already registered on Telegram: '));
                $this->my_telegram_org_wrapper->complete_login($readline('Enter the verification code you received in telegram: '));
                if (!$this->my_telegram_org_wrapper->has_app()) {
                    $app_title = $readline('Enter the app\'s name, can be anything: ');
                    $short_name = $readline('Enter the app\'s short name, can be anything: ');
                    $url = $readline('Enter the app/website\'s URL, or t.me/yourusername: ');
                    $description = $readline('Describe your app: ');
                    $app = $this->my_telegram_org_wrapper->create_app(['app_title' => $app_title, 'app_shortname' => $short_name, 'app_url' => $short_name, 'app_platform' => 'web', 'app_desc' => $description]);
                } else {
                    $app = $this->my_telegram_org_wrapper->get_app();
                }

                return $app;
            }
        } else {
            $this->getting_api_id = true;
            if (!isset($this->my_telegram_org_wrapper)) {
                if (isset($_POST['api_id']) && isset($_POST['api_hash'])) {
                    $app['api_id'] = (int) $_POST['api_id'];
                    $app['api_hash'] = $_POST['api_hash'];
                    $this->getting_api_id = false;

                    return $app;
                } elseif (isset($_POST['phone_number'])) {
                    $this->web_api_phone_login();
                } else {
                    $this->web_api_echo();
                }
            } elseif (!$this->my_telegram_org_wrapper->logged_in()) {
                if (isset($_POST['code'])) {
                    $this->web_api_complete_login();
                    if ($this->my_telegram_org_wrapper->has_app()) {
                        return $this->my_telegram_org_wrapper->get_app();
                    }
                    $this->web_api_echo();
                } else {
                    $this->web_api_echo("You didn't provide a phone code!");
                }
            } else {
                if (isset($_POST['app_title'], $_POST['app_shortname'], $_POST['app_url'], $_POST['app_platform'], $_POST['app_desc'])) {
                    $app = $this->web_api_create_app();
                    $this->getting_api_id = false;

                    return $app;
                } else {
                    $this->web_api_echo("You didn't provide all of the required parameters!");
                }
            }
            exit;
        }
    }

    public function web_api_phone_login()
    {
        try {
            $this->my_telegram_org_wrapper = new \danog\MadelineProto\MyTelegramOrgWrapper($_POST['phone_number']);
            $this->web_api_echo();
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            $this->web_api_echo('ERROR: '.$e->getMessage().'. Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            $this->web_api_echo('ERROR: '.$e->getMessage().'. Try again.');
        }
    }

    public function web_api_complete_login()
    {
        try {
            $this->my_telegram_org_wrapper->complete_login($_POST['code']);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            $this->web_api_echo('ERROR: '.$e->getMessage().'. Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            $this->web_api_echo('ERROR: '.$e->getMessage().'. Try again.');
        }
    }

    public function web_api_create_app()
    {
        try {
            $params = $_POST;
            unset($params['creating_app']);
            $app = $this->my_telegram_org_wrapper->create_app($params);

            return $app;
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            $this->web_api_echo('ERROR: '.$e->getMessage().' Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            $this->web_api_echo('ERROR: '.$e->getMessage().' Try again.');
        }
    }
}
