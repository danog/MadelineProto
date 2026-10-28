<?php

declare(strict_types=1);

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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\ApiWrappers;

use Amp\ByteStream\WritableStream;
use Amp\Http\Client\HttpException;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MyTelegramOrgWrapper;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Tools;
use danog\MadelineProto\WebUI\Action;
use danog\MadelineProto\WebUI\Alert;
use danog\MadelineProto\WebUI\Field;
use danog\MadelineProto\WebUI\FieldType;
use danog\MadelineProto\WebUI\Page;
use danog\MadelineProto\WebUI\SafeHtml;
use danog\MadelineProto\WebUI\Step;
use danog\MadelineProto\WebUI\Template;
use danog\MadelineProto\WebUI\WebUI;

use const PHP_EOL;

use const PHP_SAPI;

use function Amp\ByteStream\getStdout;

/**
 * Gets the API ID and hash, manually or automatically from my.telegram.org.
 *
 * On the web, the my.telegram.org login spans multiple requests, made before the session exists: its state is
 * kept in the session directory (see {@see \danog\MadelineProto\SessionPaths::getApiSetupState()}).
 *
 * @internal
 */
trait Start
{
    /**
     * Start API ID generation process.
     *
     * @param Settings $settings Settings
     *
     * @return ?array{api_id: int, api_hash: string} Null if a page was shown, and the request should end
     */
    private function APIStart(Settings $settings): ?array
    {
        if (Magic::$isIpcWorker) {
            throw new Exception('Not inited!');
        }
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            return $this->APIStartCli($settings);
        }
        return $this->APIStartWeb($settings);
    }

    /**
     * @return array{api_id: int, api_hash: string}
     */
    private function APIStartCli(Settings $settings): array
    {
        $stdout = getStdout();
        $stdout->write(\sprintf(Lang::$current_lang['apiChooseManualAutoTip'], 'https://docs.madelineproto.xyz/docs/SETTINGS.html').PHP_EOL);
        $stdout->write(Lang::$current_lang['apiChooseManualAuto'].PHP_EOL);
        if (!str_contains(strtolower(Tools::readLine(Lang::$current_lang['apiChoosePrompt'])), 'm')) {
            $app = $this->APIStartCliAuto($settings, $stdout);
            if ($app !== null) {
                return $app;
            }
            $stdout->write(Lang::$current_lang['apiAutoFallbackManual'].PHP_EOL.PHP_EOL);
        }

        $stdout->write('1) '.Lang::$current_lang['apiManualInstructions0'].PHP_EOL);
        $stdout->write('2) '.Lang::$current_lang['apiManualInstructions1'].PHP_EOL);
        $stdout->write('3) ');
        foreach (['App title', 'Short name', 'URL', 'Platform', 'Description'] as $k => $key) {
            $stdout->write($k ? "    $key: " : "$key: ");
            $stdout->write(Lang::$current_lang["apiAppInstructionsManual$k"].PHP_EOL);
        }
        $stdout->write('4) '.Lang::$current_lang['apiManualInstructions2'].PHP_EOL);

        return [
            'api_id' => (int) Tools::readLine('5) '.Lang::$current_lang['apiManualPrompt0']),
            'api_hash' => trim(Tools::readLine('6) '.Lang::$current_lang['apiManualPrompt1'], hidden: true)),
        ];
    }

    /**
     * Get the API ID and hash from my.telegram.org on the CLI.
     *
     * @return ?array{api_id: int, api_hash: string} Null if it failed, and the user should enter them manually
     */
    private function APIStartCliAuto(Settings $settings, WritableStream $stdout): ?array
    {
        try {
            $wrapper = new MyTelegramOrgWrapper($settings);
            $wrapper->login(trim(Tools::readLine(Lang::$current_lang['apiAutoPrompt0'])));
            $wrapper->completeLogin(trim(Tools::readLine(Lang::$current_lang['apiAutoPrompt1'], hidden: true)));
            if ($wrapper->hasApp()) {
                return $wrapper->getApp();
            }
        } catch (Exception|HttpException $e) {
            $stdout->write(PHP_EOL.Lang::$current_lang['apiAutoErrorTitle'].': '.$e->getMessage().PHP_EOL);
            return null;
        }
        try {
            return $wrapper->createApp([
                'app_title' => Tools::readLine(Lang::$current_lang['apiAppInstructionsAuto0']),
                'app_shortname' => Tools::readLine(Lang::$current_lang['apiAppInstructionsAuto1']),
                'app_url' => Tools::readLine(Lang::$current_lang['apiAppInstructionsAuto2']),
                'app_platform' => 'other',
                'app_desc' => Tools::readLine(Lang::$current_lang['apiAppInstructionsAuto4']),
            ]);
        } catch (Exception|HttpException $e) {
            $stdout->write(PHP_EOL.self::getCreateAppErrorAlert($e)->toText().PHP_EOL);
            return null;
        }
    }

    /**
     * @return ?array{api_id: int, api_hash: string}
     */
    private function APIStartWeb(Settings $settings): ?array
    {
        $templates = $settings->getTemplates();
        $wrapper = $this->session->getApiSetupState();
        $action = $_POST['api_setup'] ?? null;

        if ($action === 'manual') {
            $this->session->storeApiSetupState(null);
            WebUI::write($templates, self::getManualApiPage());
            return null;
        }
        if ($action === 'reset') {
            // Actions submit the whole form, ignore its fields
            $this->session->storeApiSetupState(null);
            $wrapper = null;
            $_POST = [];
        }

        if (isset($_POST['api_id'], $_POST['api_hash'])) {
            $apiId = trim((string) $_POST['api_id']);
            $apiHash = strtolower(trim((string) $_POST['api_hash']));
            if (!ctype_digit($apiId) || (int) $apiId <= 0 || !preg_match('/^[a-f0-9]{32}$/', $apiHash)) {
                WebUI::write($templates, self::getManualApiPage()->addAlert(Alert::error(Lang::$current_lang['apiInvalidWeb'])));
                return null;
            }
            $this->session->storeApiSetupState(null);
            return ['api_id' => (int) $apiId, 'api_hash' => $apiHash];
        }

        if ($wrapper === null) {
            if (!isset($_POST['my_phone'])) {
                WebUI::write($templates, self::getAutoApiPage());
                return null;
            }
            $phone = trim((string) $_POST['my_phone']);
            try {
                $wrapper = new MyTelegramOrgWrapper($settings);
                $wrapper->login($phone);
            } catch (Exception|HttpException $e) {
                WebUI::write($templates, self::getAutoApiPage($phone)->addAlert(self::getAutoErrorAlert($e)));
                return null;
            }
            $this->session->storeApiSetupState($wrapper);
            WebUI::redirectToSelf() || WebUI::write($templates, self::getCodeApiPage($wrapper->getPhoneNumber()));
            return null;
        }

        if (!$wrapper->loggedIn()) {
            if (!isset($_POST['my_code'])) {
                WebUI::write($templates, self::getCodeApiPage($wrapper->getPhoneNumber()));
                return null;
            }
            try {
                $wrapper->completeLogin(trim((string) $_POST['my_code']));
                if ($wrapper->hasApp()) {
                    $this->session->storeApiSetupState(null);
                    return $wrapper->getApp();
                }
            } catch (Exception|HttpException $e) {
                $this->session->storeApiSetupState($wrapper);
                WebUI::write($templates, self::getCodeApiPage($wrapper->getPhoneNumber())->addAlert(self::getAutoErrorAlert($e)));
                return null;
            }
            // Logged in, with the creation hash of the app
            $this->session->storeApiSetupState($wrapper);
            WebUI::redirectToSelf() || WebUI::write($templates, self::getCreateApiPage());
            return null;
        }

        if (!isset($_POST['app_title'], $_POST['app_shortname'])) {
            WebUI::write($templates, self::getCreateApiPage());
            return null;
        }
        $app = [
            'app_title' => trim((string) $_POST['app_title']),
            'app_shortname' => trim((string) $_POST['app_shortname']),
            'app_url' => trim((string) ($_POST['app_url'] ?? '')),
            'app_platform' => 'other',
            'app_desc' => trim((string) ($_POST['app_desc'] ?? '')),
        ];
        if ($app['app_title'] === '' || $app['app_shortname'] === '') {
            WebUI::write($templates, self::getCreateApiPage()->addAlert(Alert::error(Lang::$current_lang['apiParamsError'])));
            return null;
        }
        try {
            $result = $wrapper->createApp($app);
        } catch (Exception|HttpException $e) {
            // Keep the login: the error may be fixable (a short name already taken), and from a datacenter IP
            // the page offers the manual setup
            $this->session->storeApiSetupState($wrapper);
            WebUI::write($templates, self::getCreateApiPage()->addAlert(self::getCreateAppErrorAlert($e)));
            return null;
        }
        $this->session->storeApiSetupState(null);
        return $result;
    }

    private static function getCreateAppErrorAlert(\Throwable $e): Alert
    {
        return Alert::error(
            SafeHtml::linkify(rtrim($e->getMessage(), '.!').'. '.Lang::$current_lang['apiCreateVpsWarning']),
            Lang::$current_lang['apiCreateErrorTitle'],
        );
    }

    private static function getAutoErrorAlert(\Throwable $e): Alert
    {
        return Alert::error(
            \sprintf(Lang::$current_lang['webError'], rtrim($e->getMessage(), '.!')),
            Lang::$current_lang['apiAutoErrorTitle'],
        );
    }

    private static function getAutoApiPage(string $phone = ''): Page
    {
        return new Page(
            title: Lang::$current_lang['apiSetupTitleWeb'],
            view: 'api.auto',
            lead: Lang::$current_lang['apiSetupLeadWeb'],
            step: Step::APP,
            fields: [
                new Field(
                    name: 'my_phone',
                    label: Lang::$current_lang['loginUserPhoneWeb'],
                    type: FieldType::PHONE,
                    value: $phone,
                    placeholder: '+39 123 456 7890',
                    hint: Lang::$current_lang['apiAutoPhoneHintWeb'],
                    autofocus: true,
                ),
            ],
            submit: Lang::$current_lang['apiSendCodeWeb'],
            alerts: MTProto::getWebAlerts(),
            actions: [new Action(Lang::$current_lang['apiChooseManualWeb'], 'api_setup', 'manual')],
        );
    }

    private static function getCodeApiPage(string $phoneNumber): Page
    {
        return new Page(
            title: Lang::$current_lang['apiAutoCodeTitleWeb'],
            view: 'api.code',
            data: ['phoneNumber' => $phoneNumber],
            lead: Lang::$current_lang['apiAutoCodeLeadWeb'],
            step: Step::APP,
            fields: [
                // my.telegram.org codes are alphanumeric
                new Field(
                    name: 'my_code',
                    label: Lang::$current_lang['apiAutoCodeLabelWeb'],
                    type: FieldType::PASSWORD,
                    autocomplete: 'one-time-code',
                    autofocus: true,
                ),
            ],
            submit: Lang::$current_lang['webContinue'],
            alerts: MTProto::getWebAlerts(),
            actions: [
                new Action(Lang::$current_lang['apiStartOverWeb'], 'api_setup', 'reset'),
                new Action(Lang::$current_lang['apiChooseManualWeb'], 'api_setup', 'manual'),
            ],
        );
    }

    private static function getCreateApiPage(): Page
    {
        return new Page(
            title: Lang::$current_lang['apiCreateTitleWeb'],
            view: 'api.create',
            lead: Lang::$current_lang['apiCreateLeadWeb'],
            step: Step::APP,
            fields: [
                new Field(
                    name: 'app_title',
                    label: Lang::$current_lang['apiAppTitleLabelWeb'],
                    value: (string) ($_POST['app_title'] ?? ''),
                    hint: ucfirst(Lang::$current_lang['apiAppInstructionsManual0']),
                    autofocus: true,
                ),
                new Field(
                    name: 'app_shortname',
                    label: Lang::$current_lang['apiAppShortNameLabelWeb'],
                    value: (string) ($_POST['app_shortname'] ?? ''),
                    hint: ucfirst(Lang::$current_lang['apiAppInstructionsManual1']),
                    maxLength: 32,
                ),
                new Field(
                    name: 'app_url',
                    label: Lang::$current_lang['apiAppUrlLabelWeb'],
                    value: (string) ($_POST['app_url'] ?? ''),
                    hint: ucfirst(Lang::$current_lang['apiAppInstructionsManual2']),
                    required: false,
                ),
                new Field(
                    name: 'app_desc',
                    label: Lang::$current_lang['apiAppDescLabelWeb'],
                    type: FieldType::TEXTAREA,
                    value: (string) ($_POST['app_desc'] ?? ''),
                    required: false,
                ),
            ],
            submit: Lang::$current_lang['apiCreateWeb'],
            alerts: MTProto::getWebAlerts(),
            actions: [
                new Action(Lang::$current_lang['apiStartOverWeb'], 'api_setup', 'reset'),
                new Action(Lang::$current_lang['apiChooseManualWeb'], 'api_setup', 'manual'),
            ],
        );
    }

    private static function getManualApiPage(): Page
    {
        $appFields = '';
        foreach (['App title', 'Short name', 'URL', 'Platform', 'Description'] as $k => $key) {
            $appFields .= '<li>'.SafeHtml::escape("$key: ".Lang::$current_lang["apiAppInstructionsManual$k"])->html.'</li>';
        }
        return new Page(
            title: Lang::$current_lang['apiManualWeb'],
            view: 'api.manual',
            lead: new SafeHtml(\sprintf(Lang::$current_lang['apiChooseManualAutoTipWeb'], 'https://docs.madelineproto.xyz/docs/SETTINGS.html')),
            step: Step::APP,
            fields: [
                new Field(
                    name: 'api_id',
                    label: Lang::$current_lang['apiIdLabelWeb'],
                    value: trim((string) ($_POST['api_id'] ?? '')),
                    placeholder: '1234567',
                    autofocus: true,
                ),
                new Field(
                    name: 'api_hash',
                    label: Lang::$current_lang['apiHashLabelWeb'],
                    type: FieldType::PASSWORD,
                    value: trim((string) ($_POST['api_hash'] ?? '')),
                    autocomplete: 'off',
                    placeholder: '0123456789abcdef0123456789abcdef',
                    maxLength: 32,
                ),
            ],
            submit: Lang::$current_lang['apiSaveWeb'],
            alerts: MTProto::getWebAlerts(),
            actions: [new Action(Lang::$current_lang['apiChooseAutoWeb'], 'api_setup', 'reset')],
            instructions: [
                SafeHtml::linkify(Lang::$current_lang['apiManualInstructions0']),
                Lang::$current_lang['apiManualInstructions1'],
                new SafeHtml(SafeHtml::escape(Template::translate('apiAppWeb'))->html.":<ul>$appFields</ul>"),
                Lang::$current_lang['apiManualInstructions2'],
            ],
        );
    }
}
