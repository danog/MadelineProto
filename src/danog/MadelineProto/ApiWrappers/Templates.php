<?php

/**
 * ApiTemplates module.
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

use Amp\Promise;
use danog\MadelineProto\Lang;

use function Amp\ByteStream\getOutputBufferStream;

trait Templates
{
    /**
     * API template.
     *
     * @var string
     */
    private $webApiTemplate = 'legacy';
    /**
     * Generate page from template.
     *
     * @param string $message Message
     * @param string $form    Form
     *
     * @return string
     */
    private function webAPIEchoTemplate(string $message, string $form): string
    {
        return \sprintf($this->webApiTemplate, $message, $form, Lang::$current_lang['go']);
    }
    /**
     * Get web API login HTML template string.
     *
     * @return string
     */
    public function getWebAPITemplate(): string
    {
        return $this->webApiTemplate;
    }
    /**
     * Set web API login HTML template string.
     *
     * @return void
     */
    public function setWebAPITemplate(string $template): void
    {
        $this->webApiTemplate = $template;
    }
    /**
     * Echo to browser.
     *
     * @param string $message Message to echo
     *
     * @return Promise
     */
    private function webAPIEcho(string $message = ''): Promise
    {
        $message = \htmlentities($message);
        if (!isset($this->myTelegramOrgWrapper)) {
            if (isset($_POST['type'])) {
                if ($_POST['type'] === 'manual') {
                    $title = \htmlentities(Lang::$current_lang['apiManualWeb']);
                    $title .= "<br><b>$message</b>";
                    $title .= "<ol>";
                    $title .= "<li>".\str_replace('https://my.telegram.org', '<a href="https://my.telegram.org" target="_blank">https://my.telegram.org</a>', \htmlentities(Lang::$current_lang['apiManualInstructions0']))."</li>";
                    $title .= "<li>".\htmlentities(Lang::$current_lang['apiManualInstructions1'])."</li>";
                    $title .= "<li><ul>";
                    foreach (['App title', 'Short name', 'URL', 'Platform', 'Description'] as $k => $key) {
                        $title .= "<li>$key: ";
                        $title .= \htmlentities(Lang::$current_lang["apiAppInstructionsManual$k"]);
                        $title .= "</li>";
                    }
                    $title .= "</li></ul>";
                    $title .= "<li>".\htmlentities(Lang::$current_lang['apiManualInstructions2'])."</li>";
                    $title .= "</ol>";
                    $form = '<input type="string" name="api_id" placeholder="API ID" required/>';
                    $form .= '<input type="string" name="api_hash" placeholder="API hash" required/>';
                } else {
                    $title = Lang::$current_lang['apiAutoWeb'];
                    $title .= "<br><b>$message</b>";
                    $phone = \htmlentities(Lang::$current_lang['loginUserPhoneWeb']);
                    $form = "<input type='text' name='phone_number' placeholder='$phone' required/>";
                }
            } else {
                if ($message) {
                    $message = '<br><br>'.$message;
                }
                $title = \htmlentities(Lang::$current_lang['apiChooseManualAutoWeb']);
                $title .= "<br>";
                $title .= \sprintf(Lang::$current_lang['apiChooseManualAutoTipWeb'], 'https://docs.madelineproto.xyz/docs/SETTINGS.html#settingsapp_infoapi_id');
                $title .= "<b>$message</b>";

                $automatically = \htmlentities(Lang::$current_lang['apiChooseAutomaticallyWeb']);
                $manually = \htmlentities(Lang::$current_lang['apiChooseManuallyWeb']);

                $form = "<select name='type'><option value='automatic'>$automatically</option><option value='manual'>$manually</option></select>";
            }
        } else {
            if (!$this->myTelegramOrgWrapper->loggedIn()) {
                $title = \htmlentities(Lang::$current_lang['loginUserCode']);
                $title .= "<br><b>$message</b>";

                $code = \htmlentities(Lang::$current_lang['loginUserPhoneCodeWeb']);
                $form = "<input type='text' name='code' placeholder='$code' required/>";
            } else {
                $title = \htmlentities(Lang::$current_lang['apiAppWeb']);
                $title .= "<br><b>$message</b>";

                $form = '<input type="hidden" name="creating_app" value="yes" required/>';
                foreach (['app_title', 'app_shortname', 'app_url', 'app_platform', 'app_desc'] as $k => $field) {
                    $desc = \htmlentities(Lang::$current_lang["apiAppInstructionsAuto$k"]);
                    if ($field == 'app_platform') {
                        $form .= "$desc<br>";
                        foreach ([
                            'android' => 'Android',
                            'ios' => 'iOS',
                            'wp' => 'Windows Phone',
                            'bb' => 'BlackBerry',
                            'desktop' => 'Desktop',
                            'web' => 'Web',
                            'ubp' => 'Ubuntu phone',
                            'other' => \htmlentities(Lang::$current_lang['apiAppInstructionsAutoTypeOther'])
                        ] as $key => $desc) {
                            $form .= "<label><input type='radio' name='app_platform' value='$key' checked> $desc</label>";
                        }
                    } elseif ($field === 'app_desc') {
                        $form .= "$desc<br><textarea name='$field' required></textarea><br><br>";
                    } else {
                        $form .= "$desc<br><input type='text' name='$field' required/><br><br>";
                    }
                }
            }
        }
        return getOutputBufferStream()->write($this->webAPIEchoTemplate($title, $form));
    }
}
