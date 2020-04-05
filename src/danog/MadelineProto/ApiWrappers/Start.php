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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\ApiWrappers;

use danog\MadelineProto\MyTelegramOrgWrapper;
use danog\MadelineProto\Tools;
use function Amp\ByteStream\getStdout;

/**
 * Manages simple logging in and out.
 */
trait Start
{
    /**
     * Start API ID generation process.
     *
     * @param array $settings Settings
     *
     * @return \Generator
     */
    private function APIStart(array $settings): \Generator
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
            $this->myTelegramOrgWrapper = new \danog\MadelineProto\MyTelegramOrgWrapper($settings);
            yield from $this->myTelegramOrgWrapper->login(yield Tools::readLine('Enter a phone number that is already registered on Telegram: '));
            yield from $this->myTelegramOrgWrapper->completeLogin(yield Tools::readLine('Enter the verification code you received in telegram: '));
            if (!(yield from $this->myTelegramOrgWrapper->hasApp())) {
                $app_title = yield Tools::readLine('Enter the app\'s name, can be anything: ');
                $short_name = yield Tools::readLine('Enter the app\'s short name, can be anything: ');
                $url = yield Tools::readLine('Enter the app/website\'s URL, or t.me/yourusername: ');
                $description = yield Tools::readLine('Describe your app: ');
                $app = (yield from $this->myTelegramOrgWrapper->createApp(['app_title' => $app_title, 'app_shortname' => $short_name, 'app_url' => $url, 'app_platform' => 'web', 'app_desc' => $description]));
            } else {
                $app = (yield from $this->myTelegramOrgWrapper->getApp());
            }
            return $app;
        }
        $this->gettingApiId = true;
        if (!isset($this->myTelegramOrgWrapper)) {
            if (isset($_POST['api_id']) && isset($_POST['api_hash'])) {
                $app['api_id'] = (int) $_POST['api_id'];
                $app['api_hash'] = $_POST['api_hash'];
                $this->gettingApiId = false;
                return $app;
            } elseif (isset($_POST['phone_number'])) {
                yield from $this->webAPIPhoneLogin($settings);
            } else {
                yield from $this->webAPIEcho();
            }
        } elseif (!$this->myTelegramOrgWrapper->loggedIn()) {
            if (isset($_POST['code'])) {
                yield from $this->webAPICompleteLogin();
                if (yield from $this->myTelegramOrgWrapper->hasApp()) {
                    return yield from $this->myTelegramOrgWrapper->getApp();
                }
                yield from $this->webAPIEcho();
            } elseif (isset($_POST['api_id']) && isset($_POST['api_hash'])) {
                $app['api_id'] = (int) $_POST['api_id'];
                $app['api_hash'] = $_POST['api_hash'];
                $this->gettingApiId = false;
                return $app;
            } elseif (isset($_POST['phone_number'])) {
                yield from $this->webAPIPhoneLogin($settings);
            } else {
                $this->myTelegramOrgWrapper = null;
                yield from $this->webAPIEcho();
            }
        } else {
            if (isset($_POST['app_title'], $_POST['app_shortname'], $_POST['app_url'], $_POST['app_platform'], $_POST['app_desc'])) {
                $app = (yield from $this->webAPICreateApp());
                $this->gettingApiId = false;
                return $app;
            }
            yield from $this->webAPIEcho("You didn't provide all of the required parameters!");
        }
        return null;
    }
    private function webAPIPhoneLogin(array $settings): \Generator
    {
        try {
            $this->myTelegramOrgWrapper = new MyTelegramOrgWrapper($settings);
            yield from $this->myTelegramOrgWrapper->login($_POST['phone_number']);
            yield from $this->webAPIEcho();
        } catch (\Throwable $e) {
            yield from $this->webAPIEcho('ERROR: '.$e->getMessage().'. Try again.');
        }
    }
    private function webAPICompleteLogin(): \Generator
    {
        try {
            yield from $this->myTelegramOrgWrapper->completeLogin($_POST['code']);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield from $this->webAPIEcho('ERROR: '.$e->getMessage().'. Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            yield from $this->webAPIEcho('ERROR: '.$e->getMessage().'. Try again.');
        }
    }
    private function webAPICreateApp(): \Generator
    {
        try {
            $params = $_POST;
            unset($params['creating_app']);
            $app = (yield from $this->myTelegramOrgWrapper->createApp($params));
            return $app;
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield from $this->webAPIEcho('ERROR: '.$e->getMessage().' Try again.');
        } catch (\danog\MadelineProto\Exception $e) {
            yield from $this->webAPIEcho('ERROR: '.$e->getMessage().' Try again.');
        }
    }
}
