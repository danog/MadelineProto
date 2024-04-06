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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
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
                        $result = Tools::readLine(Lang::$current_lang['loginQr'].PHP_EOL.Lang::$current_lang['loginManual'], $cancel);
                        break;
                    } catch (CancelledException) {
                        if ($login->isRequested()) {
                            $stdout->write(PHP_EOL.PHP_EOL.Lang::$current_lang['loginQrCodeSuccessful'].PHP_EOL);
                            if ($this->getAuthorization() === \danog\MadelineProto\API::WAITING_PASSWORD) {
                                $this->complete2faLogin(Tools::readLine(sprintf(Lang::$current_lang['loginUserPass'], $this->getHint())));
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
                $this->completePhoneLogin(Tools::readLine(Lang::$current_lang['loginUserCode']));
            }
            if ($this->getAuthorization() === \danog\MadelineProto\API::WAITING_PASSWORD) {
                $this->complete2faLogin(Tools::readLine(sprintf(Lang::$current_lang['loginUserPass'], $this->getHint())));
            }
            if ($this->getAuthorization() === \danog\MadelineProto\API::WAITING_SIGNUP) {
                $this->completeSignup(Tools::readLine(Lang::$current_lang['signupFirstName']), Tools::readLine(Lang::$current_lang['signupLastName']));
            }
            $this->serialize();
            return $this->fullGetSelf();
        }
        if ($this->getAuthorization() === API::NOT_LOGGED_IN) {
            if (isset($_POST['phone_number'])) {
                $this->webPhoneLogin();
            } elseif (isset($_POST['token'])) {
                $this->webBotLogin();
            } else {
                $this->webEcho();
            }
        } elseif ($this->getAuthorization() === \danog\MadelineProto\API::WAITING_CODE) {
            if (isset($_POST['phone_code'])) {
                $this->webCompletePhoneLogin();
            } else {
                $this->webEcho(Lang::$current_lang['loginNoCode']);
            }
        } elseif ($this->getAuthorization() === \danog\MadelineProto\API::WAITING_PASSWORD) {
            if (isset($_POST['password'])) {
                $this->webComplete2faLogin();
            } else {
                $this->webEcho(Lang::$current_lang['loginUserPassWeb']);
            }
        } elseif ($this->getAuthorization() === \danog\MadelineProto\API::WAITING_SIGNUP) {
            if (isset($_POST['first_name'])) {
                $this->webCompleteSignup();
            } else {
                $this->webEcho(Lang::$current_lang['loginNoName']);
            }
        }
        if ($this->getAuthorization() === \danog\MadelineProto\API::LOGGED_IN) {
            $this->serialize();
            return $this->fullGetSelf();
        }
        die;
    }
    private function webPhoneLogin(): void
    {
        try {
            $this->phoneLogin($_POST['phone_number']);
            $this->webEcho();
        } catch (RPCErrorException $e) {
            $this->webEcho(sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        } catch (Exception $e) {
            $this->webEcho(sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        }
    }
    private function webCompletePhoneLogin(): void
    {
        try {
            $this->completePhoneLogin($_POST['phone_code']);
            $this->webEcho();
        } catch (RPCErrorException $e) {
            $this->webEcho(sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        } catch (Exception $e) {
            $this->webEcho(sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        }
    }
    private function webComplete2faLogin(): void
    {
        try {
            $this->complete2faLogin($_POST['password']);
            $this->webEcho();
        } catch (RPCErrorException $e) {
            $this->webEcho(sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        } catch (Exception $e) {
            $this->webEcho(sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        }
    }
    private function webCompleteSignup(): void
    {
        try {
            $this->completeSignup($_POST['first_name'], $_POST['last_name'] ?? '');
            $this->webEcho();
        } catch (RPCErrorException $e) {
            $this->webEcho(sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        } catch (Exception $e) {
            $this->webEcho(sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        }
    }
    private function webBotLogin(): void
    {
        try {
            $this->botLogin($_POST['token']);
            $this->webEcho();
        } catch (RPCErrorException $e) {
            $this->webEcho(sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        } catch (Exception $e) {
            $this->webEcho(sprintf(Lang::$current_lang['apiError'], $e->getMessage()));
        }
    }

    /**
     * Echo page to console.
     *
     * @param string $message Error message
     */
    private function webEcho(string $message = ''): void
    {
        $auth = $this->getAuthorization();
        $form = null;
        $trailer = '';
        if ($auth === API::NOT_LOGGED_IN) {
            if (isset($_POST['type'])) {
                if ($_POST['type'] === 'phone') {
                    $title = str_replace(':', '', Lang::$current_lang['loginUser']);
                    $phone = htmlentities(Lang::$current_lang['loginUserPhoneWeb']);
                    $form = "<input type='text' name='phone_number' placeholder='$phone' required/>";
                } else {
                    $title = str_replace(':', '', Lang::$current_lang['loginBot']);
                    $token = htmlentities(Lang::$current_lang['loginBotTokenWeb']);
                    $form = "<input type='text' name='token' placeholder='$token' required/>";
                }
            } elseif (isset($_GET['waitQrCodeOrLogin']) || isset($_GET['getQrCode'])) {
                header('Content-type: application/json');
                try {
                    $qr = $this->qrLogin();
                    if (isset($_GET['waitQrCodeOrLogin'])) {
                        $qr = $qr?->waitForLoginOrQrCodeExpiration(Tools::getTimeoutCancellation(5.0));
                    }
                } catch (CancelledException) {
                    $qr = $this->qrLogin();
                }
                if ($qr) {
                    $result = [
                        'logged_in' => false,
                        'svg' => $qr->getQRSvg(400, 2),
                    ];
                } else {
                    $result = [
                        'logged_in' => true,
                    ];
                }
                getOutputBufferStream()->write(json_encode($result));
                return;
            } else {
                $title = Lang::$current_lang['loginChoosePromptWeb'];
                $optionBot = htmlentities(Lang::$current_lang['loginOptionBot']);
                $optionUser = htmlentities(Lang::$current_lang['loginOptionUser']);
                \assert(isset($_SERVER['REQUEST_URI']));
                $trailer = '
                <div id="qr-code-container" style="display: none">
                    <p>'.htmlentities(Lang::$current_lang['loginWebQr']).'</p>
                    <div id="qr-code"></div>
                </div>

                <script>
                function longPollQr(query) {
                    var x = new XMLHttpRequest();
                    x.onload = function() {
                        var res = JSON.parse(this.responseText);
                        if (res.logged_in) {
                            window.location = window.location;
                        } else {
                            document.getElementById("qr-code-container").style = "";
                            document.getElementById("qr-code").innerHTML = res.svg;
                            longPollQr("waitQrCodeOrLogin");
                        }
                    };
                    x.open("GET", "'.(explode('?', $_SERVER['REQUEST_URI'], 2)[0] ?? '').'?"+query, true);
                    x.send();
                }
                longPollQr("getQrCode");
                </script>';
                $form = "<select name='type'><option value='phone'>$optionUser</option><option value='bot'>$optionBot</option></select>";
            }
        } elseif ($auth === \danog\MadelineProto\API::WAITING_CODE) {
            $title = str_replace(':', '', Lang::$current_lang['loginUserCode']);
            $phone = htmlentities(Lang::$current_lang['loginUserPhoneCodeWeb']);
            $form = "<input type='text' name='phone_code' placeholder='$phone' required/>";
        } elseif ($auth === \danog\MadelineProto\API::WAITING_PASSWORD) {
            $title = Lang::$current_lang['loginUserPassWeb'];
            $hint = htmlentities(sprintf(
                Lang::$current_lang['loginUserPassHint'],
                $this->getHint(),
            ));
            $form = "<input type='password' name='password' placeholder='$hint' required/>";
        } elseif ($auth === \danog\MadelineProto\API::WAITING_SIGNUP) {
            $title = Lang::$current_lang['signupWeb'];
            $firstName = Lang::$current_lang['signupFirstNameWeb'];
            $lastName = Lang::$current_lang['signupLastNameWeb'];
            $form = "<input type='text' name='first_name' placeholder='$firstName' required/><input type='text' name='last_name' placeholder='$lastName'/>";
        } else {
            return;
        }
        $title = htmlentities($title);
        $message = htmlentities($message).MTProto::getWebWarnings();
        getOutputBufferStream()->write(sprintf(
            $this->getSettings()->getTemplates()->getHtmlTemplate(),
            "$title<br><b>$message</b>",
            $form,
            Lang::$current_lang['go'],
            $trailer
        ));
    }
}
