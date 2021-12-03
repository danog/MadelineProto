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

use danog\MadelineProto\Lang;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MyTelegramOrgWrapper;
use danog\MadelineProto\Settings;
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
     * @param Settings $settings Settings
     *
     * @return \Generator
     */
    private function APIStart(Settings $settings): \Generator
    {
        if (Magic::$isIpcWorker) {
            throw new \danog\MadelineProto\Exception('Not inited!');
        }
        if ($this->getWebAPITemplate() === 'legacy') {
            $this->setWebAPITemplate($settings->getTemplates()->getHtmlTemplate());
        }
        $app = null;
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            $stdout = getStdout();
            $prepare = Lang::$current_lang['apiChooseManualAuto'].PHP_EOL;
            $prepare .= \sprintf(Lang::$current_lang['apiChooseManualAutoTip'], 'https://docs.madelineproto.xyz/docs/SETTINGS.html#settingsapp_infoapi_id');
            $prepare .= PHP_EOL;
            yield $stdout->write($prepare);
            if (\strpos(yield Tools::readLine(Lang::$current_lang['apiChoosePrompt']), 'm') !== false) {
                yield $stdout->write("1) ".Lang::$current_lang['apiManualInstructions0'].PHP_EOL);
                yield $stdout->write("2) ".Lang::$current_lang['apiManualInstructions1'].PHP_EOL);
                yield $stdout->write("3) ");
                foreach (['App title', 'Short name', 'URL', 'Platform', 'Description'] as $k => $key) {
                    yield $stdout->write($k ? "    $key: " : "$key: ");
                    yield $stdout->write(Lang::$current_lang["apiAppInstructionsManual$k"].PHP_EOL);
                }
                yield $stdout->write("4) ".Lang::$current_lang['apiManualInstructions2'].PHP_EOL);

                $app['api_id'] = yield Tools::readLine("5) ".Lang::$current_lang['apiManualPrompt0']);
                $app['api_hash'] = yield Tools::readLine("6) ".Lang::$current_lang['apiManualPrompt1']);
                return $app;
            }
            $this->myTelegramOrgWrapper = new \danog\MadelineProto\MyTelegramOrgWrapper($settings);
            yield from $this->myTelegramOrgWrapper->login(yield Tools::readLine(Lang::$current_lang['apiAutoPrompt0']));
            yield from $this->myTelegramOrgWrapper->completeLogin(yield Tools::readLine(Lang::$current_lang['apiAutoPrompt1']));
            if (!(yield from $this->myTelegramOrgWrapper->hasApp())) {
                $app_title = yield Tools::readLine(Lang::$current_lang['apiAppInstructionsAuto0']);
                $short_name = yield Tools::readLine(Lang::$current_lang['apiAppInstructionsAuto1']);
                $url = yield Tools::readLine(Lang::$current_lang['apiAppInstructionsAuto2']);
                $description = yield Tools::readLine(Lang::$current_lang['apiAppInstructionsAuto4']);
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
                yield $this->webAPIEcho();
            }
        } elseif (!$this->myTelegramOrgWrapper->loggedIn()) {
            if (isset($_POST['code'])) {
                yield from $this->webAPICompleteLogin();
                if (yield from $this->myTelegramOrgWrapper->hasApp()) {
                    return yield from $this->myTelegramOrgWrapper->getApp();
                }
                yield $this->webAPIEcho();
            } elseif (isset($_POST['api_id']) && isset($_POST['api_hash'])) {
                $app['api_id'] = (int) $_POST['api_id'];
                $app['api_hash'] = $_POST['api_hash'];
                $this->gettingApiId = false;
                return $app;
            } elseif (isset($_POST['phone_number'])) {
                yield from $this->webAPIPhoneLogin($settings);
            } else {
                $this->myTelegramOrgWrapper = null;
                yield $this->webAPIEcho();
            }
        } else {
            if (isset($_POST['app_title'], $_POST['app_shortname'], $_POST['app_url'], $_POST['app_platform'], $_POST['app_desc'])) {
                $app = (yield from $this->webAPICreateApp());
                $this->gettingApiId = false;
                return $app;
            }
            yield $this->webAPIEcho(Lang::$current_lang['apiParamsError']);
        }
        return null;
    }
    private function webAPIPhoneLogin(Settings $settings): \Generator
    {
        try {
            $this->myTelegramOrgWrapper = new MyTelegramOrgWrapper($settings);
            yield from $this->myTelegramOrgWrapper->login($_POST['phone_number']);
            yield $this->webAPIEcho();
        } catch (\Throwable $e) {
            yield $this->webAPIEcho(\sprintf(Lang::$current_lang['apiError'], 'Please use manual mode: '.$e->getMessage()));
        }
    }
    private function webAPICompleteLogin(): \Generator
    {
        try {
            yield from $this->myTelegramOrgWrapper->completeLogin($_POST['code']);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield $this->webAPIEcho(\sprintf(Lang::$current_lang['apiError'], 'Please use manual mode: '.$e->getMessage()));
        } catch (\danog\MadelineProto\Exception $e) {
            yield $this->webAPIEcho(\sprintf(Lang::$current_lang['apiError'], 'Please use manual mode: '.$e->getMessage()));
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
            yield $this->webAPIEcho(\sprintf(Lang::$current_lang['apiError'], 'Please use manual mode: '.$e->getMessage()));
        } catch (\danog\MadelineProto\Exception $e) {
            yield $this->webAPIEcho(\sprintf(Lang::$current_lang['apiError'], 'Please use manual mode: '.$e->getMessage()));
        }
    }
}
