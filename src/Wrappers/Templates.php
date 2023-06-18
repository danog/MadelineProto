<?php

declare(strict_types=1);

/**
 * Templates module.
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
use Amp\TimeoutCancellation;
use danog\MadelineProto\Lang;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\TL\Types\LoginQrCode;

use function Amp\ByteStream\getOutputBufferStream;

/**
 * @internal
 */
trait Templates
{
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
        if ($auth === MTProto::NOT_LOGGED_IN) {
            if (isset($_POST['type'])) {
                if ($_POST['type'] === 'phone') {
                    $title = \str_replace(':', '', Lang::$current_lang['loginUser']);
                    $phone = \htmlentities(Lang::$current_lang['loginUserPhoneWeb']);
                    $form = "<input type='text' name='phone_number' placeholder='$phone' required/>";
                } else {
                    $title = \str_replace(':', '', Lang::$current_lang['loginBot']);
                    $token = \htmlentities(Lang::$current_lang['loginBotTokenWeb']);
                    $form = "<input type='text' name='token' placeholder='$token' required/>";
                }
            } elseif (isset($_POST['waitQrCodeOrLogin'])) {
                header('Content-type: application/json');
                try {
                    /** @var ?LoginQrCode */
                    $qr = $this->qrLogin()?->waitForLoginOrQrCodeExpiration(new TimeoutCancellation(
                        5.0
                    ));
                } catch (CancelledException) {
                    /** @var ?LoginQrCode */
                    $qr = $this->qrLogin();
                }
                if ($qr) {
                    $result = [
                        'logged_in' => false,
                        'svg' => $qr->getQRSvg()
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
                $optionBot = \htmlentities(Lang::$current_lang['loginOptionBot']);
                $optionUser = \htmlentities(Lang::$current_lang['loginOptionUser']);
                $trailer = '<div id="qr-code"></div><script>
                var x = new XMLHttpRequest();
                x.onload = function() {
                    document.getElementById("demo").innerHTML = this.responseText;
                    }
                x.open("GET", "?waitQrCodeOrLogin", true);
                x.send();
                </script>';
                $form = "<select name='type'><option value='phone'>$optionUser</option><option value='bot'>$optionBot</option></select>";
            }
        } elseif ($auth === MTProto::WAITING_CODE) {
            $title = \str_replace(':', '', Lang::$current_lang['loginUserCode']);
            $phone = \htmlentities(Lang::$current_lang['loginUserPhoneCodeWeb']);
            $form = "<input type='text' name='phone_code' placeholder='$phone' required/>";
        } elseif ($auth === MTProto::WAITING_PASSWORD) {
            $title = Lang::$current_lang['loginUserPassWeb'];
            $hint = \htmlentities(\sprintf(
                Lang::$current_lang['loginUserPassHint'],
                $this->getHint(),
            ));
            $form = "<input type='password' name='password' placeholder='$hint' required/>";
        } elseif ($auth === MTProto::WAITING_SIGNUP) {
            $title = Lang::$current_lang['signupWeb'];
            $firstName = Lang::$current_lang['signupFirstNameWeb'];
            $lastName = Lang::$current_lang['signupLastNameWeb'];
            $form = "<input type='text' name='first_name' placeholder='$firstName' required/><input type='text' name='last_name' placeholder='$lastName'/>";
        } else {
            return;
        }
        $title = \htmlentities($title);
        $message = \htmlentities($message);
        getOutputBufferStream()->write(\sprintf(
            '<!DOCTYPE html><html><head><title>MadelineProto</title></head><body><h1>MadelineProto</h1><p>%s</p><form method="POST">%s<button type="submit"/>%s</button></form>%s</body></html>',
            $message,
            $form,
            Lang::$current_lang['go'],
            $trailer
        ));
    }
}
