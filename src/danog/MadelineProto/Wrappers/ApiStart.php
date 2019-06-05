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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Wrappers;

use function Amp\ByteStream\getStdout;

/**
 * Manages simple logging in and out.
 */
trait ApiStart
{
    public function api_start_async($settings)
    {
        if (php_sapi_name() === 'cli') {
            $stdout = getStdout();
            yield $stdout->write('You did not define a valid API ID/API hash. Do you want to define it now manually, or automatically? (m/a)
Note that you can also provide the API parameters directly in the code using the settings: https://docs.madelineproto.xyz/docs/SETTINGS.html#settingsapp_infoapi_id'.PHP_EOL);
            if (strpos(yield $this->readLine('Your choice (m/a): '), 'm') !== false) {
                yield $stdout->write('1) Login to my.telegram.org
2) Go to API development tools
3) App title: your app\'s name, can be anything
    Short name: your app\'s short name, can be anything
    URL: your app/website\'s URL, or t.me/yourusername
    Platform: anything
    Description: Describe your app here
4) Click on create application'.PHP_EOL);
                $app['api_id'] = yield $this->readLine('5) Enter your API ID: ');
                $app['api_hash'] = yield $this->readLine('6) Enter your API hash: ');

                return $app;
            } else {
                $this->my_telegram_org_wrapper = new \danog\MadelineProto\MyTelegramOrgWrapper($settings);
                yield $this->my_telegram_org_wrapper->login_async(yield $this->readLine('Enter a phone number that is already registered on Telegram: '));
                yield $this->my_telegram_org_wrapper->complete_login_async(yield $this->readLine('Enter the verification code you received in telegram: '));
                if (!yield $this->my_telegram_org_wrapper->has_app_async()) {
                    $app_title = yield $this->readLine('Enter the app\'s name, can be anything: ');
                    $short_name = yield $this->readLine('Enter the app\'s short name, can be anything: ');
                    $url = yield $this->readLine('Enter the app/website\'s URL, or t.me/yourusername: ');
                    $description = yield $this->readLine('Describe your app: ');
                    $app = yield $this->my_telegram_org_wrapper->create_app_async(['app_title' => $app_title, 'app_shortname' => $short_name, 'app_url' => $url, 'app_platform' => 'web', 'app_desc' => $description]);
                } else {
                    $app = yield $this->my_telegram_org_wrapper->get_app_async();
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
                    yield $this->web_api_phone_login_async($settings);
                } else {
                    yield $this->web_api_echo_async();
                }
            } elseif (!$this->my_telegram_org_wrapper->logged_in()) {
                if (isset($_POST['code'])) {
                    yield $this->web_api_complete_login_async();
                    if (yield $this->my_telegram_org_wrapper->has_app_async()) {
                        return yield $this->my_telegram_org_wrapper->get_app_async();
                    }
                    yield $this->web_api_echo_async();
                } elseif (isset($_POST['api_id']) && isset($_POST['api_hash'])) {
                    $app['api_id'] = (int) $_POST['api_id'];
                    $app['api_hash'] = $_POST['api_hash'];
                    $this->getting_api_id = false;

                    return $app;
                } elseif (isset($_POST['phone_number'])) {
                    yield $this->web_api_phone_login_async($settings);
                } else {
                    $this->my_telegram_org_wrapper = null;
                    yield $this->web_api_echo_async();
                }
            } else {
                if (isset($_POST['app_title'], $_POST['app_shortname'], $_POST['app_url'], $_POST['app_platform'], $_POST['app_desc'])) {
                    $app = yield $this->web_api_create_app_async();
                    $this->getting_api_id = false;

                    return $app;
                } else {
                    yield $this->web_api_echo_async("You didn't provide all of the required parameters!");
                }
            }
            $this->asyncInitPromise = null;
            exit;
        }
    }

    public function web_api_phone_login_async($settings)
    {
        try {
            $this->my_telegram_org_wrapper = new \danog\MadelineProto\MyTelegramOrgWrapper($settings);
            yield $this->my_telegram_org_wrapper->login_async($_POST['phone_number']);
            yield $this->web_api_echo_async();
        } catch (\Throwable $e) {
            yield $this->web_api_echo_async('ERROR: '.$e->getMessage().'. Try again.');
        }
    }

    public function web_api_complete_login_async()
    {
        try {
            yield $this->my_telegram_org_wrapper->complete_login_async($_POST['code']);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield $this->web_api_echo_async('ERROR: '.$e->getMessage().'. Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            yield $this->web_api_echo_async('ERROR: '.$e->getMessage().'. Try again.');
        }
    }

    public function web_api_create_app_async()
    {
        try {
            $params = $_POST;
            unset($params['creating_app']);
            $app = yield $this->my_telegram_org_wrapper->create_app_async($params);

            return $app;
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield $this->web_api_echo_async('ERROR: '.$e->getMessage().' Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            yield $this->web_api_echo_async('ERROR: '.$e->getMessage().' Try again.');
        }
    }
}
