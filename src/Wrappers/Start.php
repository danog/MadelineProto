<?php

declare(strict_types=1);

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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Wrappers;

use Amp\CancelledException;
use Amp\CompositeCancellation;
use danog\MadelineProto\API;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\Lang;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Tools;
use danog\MadelineProto\WebUI\Alert;
use danog\MadelineProto\WebUI\Field;
use danog\MadelineProto\WebUI\FieldType;
use danog\MadelineProto\WebUI\Page;
use danog\MadelineProto\WebUI\Step;
use danog\MadelineProto\WebUI\WebUI;

use const PHP_SAPI;

use function Amp\ByteStream\getOutputBufferStream;
use function Amp\ByteStream\getStdout;

/**
 * Manages simple logging in and out.
 *
 * @property Settings $settings Settings
 *
 * @internal
 */
trait Start
{
    /**
     * Log in to telegram (via CLI or web).
     */
    public function start(): array
    {
        if ($this->getAuthorization() === \danog\MadelineProto\API::LOGGED_IN) {
            return $this instanceof Client ? $this->getSelf() : $this->fullGetSelf();
        }
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            if ($this->getAuthorization() === API::NOT_LOGGED_IN) {
                $stdout = getStdout();
                do {
                    $qr = $this->qrLogin();
                    if (!$qr) {
                        $this->serialize();
                        return $this->fullGetSelf();
                    }
                    $stdout->write($qr->getQRText(2));

                    $expire = $qr->getExpirationCancellation();
                    $login = $qr->getLoginCancellation();

                    $cancel = new CompositeCancellation($expire, $login);

                    try {
                        // A phone number, or a bot token, censored after its colon.
                        $result = Tools::readLine(Lang::$current_lang['loginQr'].PHP_EOL.Lang::$current_lang['loginManual'], $cancel, hideAfter: ':');
                        break;
                    } catch (CancelledException) {
                        if ($login->isRequested()) {
                            $stdout->write(PHP_EOL.PHP_EOL.Lang::$current_lang['loginQrCodeSuccessful'].PHP_EOL);
                            if ($this->getAuthorization() === \danog\MadelineProto\API::WAITING_PASSWORD) {
                                $this->complete2faLogin(Tools::readLine($this->getPasswordPrompt(), hidden: true));
                            }
                            $this->serialize();
                            return $this->fullGetSelf();
                        }

                        $stdout->write(PHP_EOL.Lang::$current_lang['loginQrCodeExpired'].PHP_EOL);
                    }
                } while (true);
                if (str_contains($result, ':')) {
                    $this->botLogin($result);
                } else {
                    $this->phoneLogin($result);
                }
            }
            if ($this->getAuthorization() === \danog\MadelineProto\API::WAITING_CODE) {
                $this->completePhoneLogin(Tools::readLine(Lang::$current_lang['loginUserCode'], hidden: true));
            }
            if ($this->getAuthorization() === \danog\MadelineProto\API::WAITING_PASSWORD) {
                $this->complete2faLogin(Tools::readLine($this->getPasswordPrompt(), hidden: true));
            }
            if ($this->getAuthorization() === \danog\MadelineProto\API::WAITING_SIGNUP) {
                $this->completeSignup(Tools::readLine(Lang::$current_lang['signupFirstName']), Tools::readLine(Lang::$current_lang['signupLastName']));
            }
            $this->serialize();
            return $this->fullGetSelf();
        }
        if (isset($_GET['waitQrCodeOrLogin']) || isset($_GET['getQrCode'])) {
            $this->webQrLogin();
            die;
        }
        $auth = $this->getAuthorization();
        $post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
        if ($auth === API::NOT_LOGGED_IN) {
            $type = $_POST['type'] ?? (isset($_POST['token']) ? 'bot' : 'phone');
            if ($type === 'bot' && isset($_POST['token'])) {
                $this->webStep(fn () => $this->botLogin(trim((string) $_POST['token'])));
            } elseif ($type === 'phone' && isset($_POST['phone_number'])) {
                $this->webStep(fn () => $this->phoneLogin(trim((string) $_POST['phone_number'])));
            } else {
                $this->webEcho();
            }
        } elseif ($auth === API::WAITING_CODE) {
            if (isset($_POST['phone_code'])) {
                $this->webStep(fn () => $this->completePhoneLogin(trim((string) $_POST['phone_code'])));
            } else {
                $this->webEcho($post ? Alert::error(Lang::$current_lang['loginNoCode']) : null);
            }
        } elseif ($auth === API::WAITING_PASSWORD) {
            if (isset($_POST['password'])) {
                $this->webStep(fn () => $this->complete2faLogin((string) $_POST['password']));
            } else {
                $this->webEcho($post ? Alert::error(Lang::$current_lang['loginNoPass']) : null);
            }
        } elseif ($auth === API::WAITING_SIGNUP) {
            if (isset($_POST['first_name'])) {
                $this->webStep(fn () => $this->completeSignup(trim((string) $_POST['first_name']), trim((string) ($_POST['last_name'] ?? ''))));
            } else {
                $this->webEcho($post ? Alert::error(Lang::$current_lang['loginNoName']) : null);
            }
        }
        if ($this->getAuthorization() === API::LOGGED_IN) {
            $this->serialize();
            return $this->fullGetSelf();
        }
        die;
    }
    /**
     * Run a login step submitted from the web UI, then show the next one.
     *
     * @param callable(): mixed $step
     */
    private function webStep(callable $step): void
    {
        try {
            $step();
        } catch (RPCErrorException|Exception $e) {
            $this->webEcho(
                Alert::error(sprintf(Lang::$current_lang['webError'], rtrim($e->getMessage(), '.!'))),
                $e instanceof RPCErrorException ? $e->rpc : null,
            );
            return;
        }
        if ($this->getAuthorization() === API::LOGGED_IN) {
            // The web app reloads the page: the script runs in that request, once
            if (WebUI::writeLoggedIn()) {
                $this->serialize();
                die;
            }
            // Otherwise, the script continues in this request: it must not run again after a redirect
            return;
        }
        if (!WebUI::redirectToSelf()) {
            $this->webEcho();
        }
    }
    /**
     * Answer the long polling requests of the QR code login panel.
     */
    private function webQrLogin(): void
    {
        $result = ['logged_in' => true];
        if ($this->getAuthorization() === API::NOT_LOGGED_IN) {
            try {
                $qr = $this->qrLogin();
                if (isset($_GET['waitQrCodeOrLogin'])) {
                    $qr = $qr?->waitForLoginOrQrCodeExpiration(
                        Tools::getTimeoutCancellation(5.0, "Timeout while waiting for QR code or login")
                    );
                }
            } catch (CancelledException) {
                $qr = $this->qrLogin();
            }
            if ($qr) {
                $result = [
                    'logged_in' => false,
                    'link' => $qr->link,
                    'svg' => $qr->getQRSvg(400, 2),
                ];
            }
        }
        // Otherwise, logged in (or waiting for the password): the page reloads
        if (!headers_sent()) {
            header('Content-Type: application/json');
            header('Cache-Control: no-store');
        }
        getOutputBufferStream()->write(json_encode($result));
    }

    /**
     * Show the page of the current login step.
     *
     * @param ?string $rpcError Type of the RPC error of the submitted step, for the web app
     */
    private function webEcho(?Alert $alert = null, ?string $rpcError = null): void
    {
        $page = $this->getWebLoginPage();
        if ($page === null) {
            return;
        }
        if ($alert !== null) {
            $page->addAlert($alert);
        }
        if ($rpcError !== null) {
            $page->data['rpcError'] = $rpcError;
        }
        WebUI::write($this->getSettings()->getTemplates(), $page);
    }

    /**
     * Get the page of the current login step.
     */
    private function getWebLoginPage(): ?Page
    {
        $alerts = MTProto::getWebAlerts();
        switch ($this->getAuthorization()) {
            case API::NOT_LOGGED_IN:
                $type = ($_POST['type'] ?? 'phone') === 'bot' ? 'bot' : 'phone';
                return new Page(
                    title: Lang::$current_lang['loginTitleWeb'],
                    view: 'login.signIn',
                    lead: Lang::$current_lang['loginLeadWeb'],
                    step: Step::ACCOUNT,
                    fields: [
                        new Field(
                            name: 'type',
                            label: Lang::$current_lang['loginTypeWeb'],
                            type: FieldType::CHOICE,
                            value: $type,
                            options: [
                                'phone' => Lang::$current_lang['loginOptionUser'],
                                'bot' => Lang::$current_lang['loginOptionBot'],
                            ],
                        ),
                        new Field(
                            name: 'phone_number',
                            label: Lang::$current_lang['loginUserPhoneWeb'],
                            type: FieldType::PHONE,
                            value: trim((string) ($_POST['phone_number'] ?? '')),
                            placeholder: '+39 123 456 7890',
                            hint: Lang::$current_lang['loginUserPhoneHintWeb'],
                            showWhen: ['type', 'phone'],
                            autofocus: $type === 'phone',
                        ),
                        new Field(
                            name: 'token',
                            label: Lang::$current_lang['loginBotTokenWeb'],
                            type: FieldType::PASSWORD,
                            placeholder: '123456789:AAE…',
                            autocomplete: 'off',
                            hint: Lang::$current_lang['loginBotTokenHintWeb'],
                            showWhen: ['type', 'bot'],
                            autofocus: $type === 'bot',
                        ),
                    ],
                    submit: Lang::$current_lang['webContinue'],
                    alerts: $alerts,
                    qrEndpoint: WebUI::getSelfUrl(),
                );
            case API::WAITING_CODE:
                return new Page(
                    title: Lang::$current_lang['loginCodeTitleWeb'],
                    view: 'login.code',
                    data: $this->getWebLoginInfo(),
                    lead: Lang::$current_lang['loginCodeLeadWeb'],
                    step: Step::VERIFY,
                    fields: [
                        new Field(
                            name: 'phone_code',
                            label: Lang::$current_lang['loginUserPhoneCodeWeb'],
                            type: FieldType::CODE,
                            autofocus: true,
                            maxLength: 12,
                        ),
                    ],
                    submit: Lang::$current_lang['webLogin'],
                    alerts: $alerts,
                );
            case API::WAITING_PASSWORD:
                $hint = $this->getHint();
                return new Page(
                    title: Lang::$current_lang['loginPasswordTitleWeb'],
                    view: 'login.password',
                    data: ['hint' => $hint],
                    lead: Lang::$current_lang['loginPasswordLeadWeb'],
                    step: Step::VERIFY,
                    fields: [
                        new Field(
                            name: 'password',
                            label: Lang::$current_lang['loginPasswordLabelWeb'],
                            type: FieldType::PASSWORD,
                            hint: $hint === '' ? '' : sprintf(Lang::$current_lang['loginUserPassHint'], $hint),
                            autofocus: true,
                        ),
                    ],
                    submit: Lang::$current_lang['webLogin'],
                    alerts: $alerts,
                );
            case API::WAITING_SIGNUP:
                return new Page(
                    title: Lang::$current_lang['signupTitleWeb'],
                    view: 'login.signUp',
                    data: $this->getWebLoginInfo(),
                    lead: Lang::$current_lang['signupLeadWeb'],
                    step: Step::VERIFY,
                    fields: [
                        new Field(
                            name: 'first_name',
                            label: Lang::$current_lang['signupFirstNameWeb'],
                            value: trim((string) ($_POST['first_name'] ?? '')),
                            autocomplete: 'given-name',
                            autofocus: true,
                        ),
                        new Field(
                            name: 'last_name',
                            label: Lang::$current_lang['signupLastNameWeb'],
                            value: trim((string) ($_POST['last_name'] ?? '')),
                            required: false,
                            autocomplete: 'family-name',
                        ),
                    ],
                    submit: Lang::$current_lang['webContinue'],
                    alerts: $alerts,
                );
        }
        return null;
    }

    /**
     * The CLI prompt of the 2FA password, with the hint of the account if it has one.
     */
    private function getPasswordPrompt(): string
    {
        $hint = $this->getHint();
        return $hint === ''
            ? Lang::$current_lang['loginUserPassWeb']
            : sprintf(Lang::$current_lang['loginUserPass'], $hint);
    }
}
