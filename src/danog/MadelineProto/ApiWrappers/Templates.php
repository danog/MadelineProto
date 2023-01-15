<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\ApiWrappers;

use danog\MadelineProto\Lang;

use function Amp\ByteStream\getOutputBufferStream;

trait Templates
{
    /**
     * API template.
     *
     */
    private string $webApiTemplate = 'legacy';
    /**
     * Generate page from template.
     *
     * @param string $message Message
     * @param string $form    Form
     */
    private function webAPIEchoTemplate(string $message, string $form): string
    {
        return \sprintf($this->webApiTemplate, $message, $form, Lang::$current_lang['go']);
    }
    /**
     * Get web API login HTML template string.
     */
    public function getWebAPITemplate(): string
    {
        return $this->webApiTemplate;
    }
    /**
     * Set web API login HTML template string.
     */
    public function setWebAPITemplate(string $template): void
    {
        $this->webApiTemplate = $template;
    }
    /**
     * Echo to browser.
     *
     * @param string $message Message to echo
     */
    private function webAPIEcho(string $message = ''): void
    {
        $message = \htmlentities($message);
        $title = \htmlentities(Lang::$current_lang['apiManualWeb']);
        $title .= "<br>";
        $title .= \sprintf(Lang::$current_lang['apiChooseManualAutoTipWeb'], 'https://docs.madelineproto.xyz/docs/SETTINGS.html');
        $title .= "<br><b>$message</b>";
        $title .= '<ol>';
        $title .= '<li>'.\str_replace('https://my.telegram.org', '<a href="https://my.telegram.org" target="_blank">https://my.telegram.org</a>', \htmlentities(Lang::$current_lang['apiManualInstructions0'])).'</li>';
        $title .= '<li>'.\htmlentities(Lang::$current_lang['apiManualInstructions1']).'</li>';
        $title .= '<li><ul>';
        foreach (['App title', 'Short name', 'URL', 'Platform', 'Description'] as $k => $key) {
            $title .= "<li>$key: ";
            $title .= \htmlentities(Lang::$current_lang["apiAppInstructionsManual$k"]);
            $title .= '</li>';
        }
        $title .= '</li></ul>';
        $title .= '<li>'.\htmlentities(Lang::$current_lang['apiManualInstructions2']).'</li>';
        $title .= '</ol>';
        $form = '<input type="string" name="api_id" placeholder="API ID" required/>';
        $form .= '<input type="string" name="api_hash" placeholder="API hash" required/>';
        getOutputBufferStream()->write($this->webAPIEchoTemplate($title, $form));
    }
}
