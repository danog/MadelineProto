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
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Wrappers;

use danog\MadelineProto\Tools;

use function Amp\ByteStream\getStdout;

/**
 * Manages simple logging in and out.
 */
trait ApiStart
{
    public function APIStart($settings)
    {
        if (PHP_SAPI === 'cli') {
            $stdout = getStdout();
            yield $stdout->write('You did not define a valid API ID/API hash. Do you want to define it now manually, or automatically? (m/a)
Note that you can also provide the API parameters directly in the code using the settings: https://docs.madelineproto.xyz/docs/SETTINGS.html#settingsapp_infoapi_id'.PHP_EOL);
            if (\strpos(yield Tools::readLine('Your choice (m/a): '), 'm') !== false) {
                yield $stdout->write('1) Login to my.telegram.org
2) Go to API development tools
3) App title: your app\'s name, can be anything
    Short name: your app\'s short name, can be anything
    URL: your app/website\'s URL, or t.me/yourusername
    Platform: anything
    Description: Describe your app here
4) Click on create application'.PHP_EOL);
                $app['api_id'] = yield Tools::readLine('5) Enter your API ID: ');
                $app['api_hash'] = yield Tools::readLine('6) Enter your API hash: ');

                return $app;
            }
            $this->my_telegram_org_wrapper = new \danog\MadelineProto\MyTelegramOrgWrapper($settings);
            yield $this->my_telegram_org_wrapper->login(yield Tools::readLine('Enter a phone number that is already registered on Telegram: '));
            yield $this->my_telegram_org_wrapper->completeLogin(yield Tools::readLine('Enter the verification code you received in telegram: '));
            if (!yield $this->my_telegram_org_wrapper->hasApp()) {
                $app_title = yield Tools::readLine('Enter the app\'s name, can be anything: ');
                $short_name = yield Tools::readLine('Enter the app\'s short name, can be anything: ');
                $url = yield Tools::readLine('Enter the app/website\'s URL, or t.me/yourusername: ');
                $description = yield Tools::readLine('Describe your app: ');
                $app = yield $this->my_telegram_org_wrapper->createApp(['app_title' => $app_title, 'app_shortname' => $short_name, 'app_url' => $url, 'app_platform' => 'web', 'app_desc' => $description]);
            } else {
                $app = yield $this->my_telegram_org_wrapper->getApp();
            }

            return $app;
        }
        $this->getting_api_id = true;
        if (!isset($this->my_telegram_org_wrapper)) {
            if (isset($_POST['api_id']) && isset($_POST['api_hash'])) {
                $app['api_id'] = (int) $_POST['api_id'];
                $app['api_hash'] = $_POST['api_hash'];
                $this->getting_api_id = false;

                return $app;
            } elseif (isset($_POST['phone_number'])) {
                yield $this->webAPIPhoneLogin($settings);
            } else {
                yield $this->webAPIEcho();
            }
        } elseif (!$this->my_telegram_org_wrapper->loggedIn()) {
            if (isset($_POST['code'])) {
                yield $this->webAPICompleteLogin();
                if (yield $this->my_telegram_org_wrapper->hasApp()) {
                    return yield $this->my_telegram_org_wrapper->getApp();
                }
                yield $this->webAPIEcho();
            } elseif (isset($_POST['api_id']) && isset($_POST['api_hash'])) {
                $app['api_id'] = (int) $_POST['api_id'];
                $app['api_hash'] = $_POST['api_hash'];
                $this->getting_api_id = false;

                return $app;
            } elseif (isset($_POST['phone_number'])) {
                yield $this->webAPIPhoneLogin($settings);
            } else {
                $this->my_telegram_org_wrapper = null;
                yield $this->webAPIEcho();
            }
        } else {
            if (isset($_POST['app_title'], $_POST['app_shortname'], $_POST['app_url'], $_POST['app_platform'], $_POST['app_desc'])) {
                $app = yield $this->webAPICreateApp();
                $this->getting_api_id = false;

                return $app;
            }
            yield $this->webAPIEcho("You didn't provide all of the required parameters!");
        }
        $this->asyncInitPromise = null;
        exit;
    }

    private function webAPIPhoneLogin($settings)
    {
        try {
            $this->my_telegram_org_wrapper = new \danog\MadelineProto\MyTelegramOrgWrapper($settings);
            yield $this->my_telegram_org_wrapper->login($_POST['phone_number']);
            yield $this->webAPIEcho();
        } catch (\Throwable $e) {
            yield $this->webAPIEcho('ERROR: '.$e->getMessage().'. Try again.');
        }
    }

    private function webAPICompleteLogin()
    {
        try {
            yield $this->my_telegram_org_wrapper->completeLogin($_POST['code']);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield $this->webAPIEcho('ERROR: '.$e->getMessage().'. Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            yield $this->webAPIEcho('ERROR: '.$e->getMessage().'. Try again.');
        }
    }

    private function webAPICreateApp()
    {
        try {
            $params = $_POST;
            unset($params['creating_app']);
            $app = yield $this->my_telegram_org_wrapper->createApp($params);

            return $app;
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield $this->webAPIEcho('ERROR: '.$e->getMessage().' Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            yield $this->webAPIEcho('ERROR: '.$e->getMessage().' Try again.');
        }
    }
}
