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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\ApiWrappers;

use danog\MadelineProto\Exception;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Tools;

use const PHP_EOL;

use const PHP_SAPI;

use function Amp\ByteStream\getOutputBufferStream;
use function Amp\ByteStream\getStdout;

/**
 * Manages simple logging in and out.
 *
 * @internal
 */
trait Start
{
    /**
     * Start API ID generation process.
     *
     * @param Settings $settings Settings
     */
    private function APIStart(Settings $settings)
    {
        if (Magic::$isIpcWorker) {
            throw new Exception('Not inited!');
        }
        $app = [];
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            $stdout = getStdout();
            $stdout->write(sprintf(Lang::$current_lang['apiChooseManualAutoTip'], 'https://docs.madelineproto.xyz/docs/SETTINGS.html').PHP_EOL);
            $stdout->write('1) '.Lang::$current_lang['apiManualInstructions0'].PHP_EOL);
            $stdout->write('2) '.Lang::$current_lang['apiManualInstructions1'].PHP_EOL);
            $stdout->write('3) ');
            foreach (['App title', 'Short name', 'URL', 'Platform', 'Description'] as $k => $key) {
                $stdout->write($k ? "    $key: " : "$key: ");
                $stdout->write(Lang::$current_lang["apiAppInstructionsManual$k"].PHP_EOL);
            }
            $stdout->write('4) '.Lang::$current_lang['apiManualInstructions2'].PHP_EOL);

            $app['api_id'] = (int) Tools::readLine('5) '.Lang::$current_lang['apiManualPrompt0']);
            $app['api_hash'] = Tools::readLine('6) '.Lang::$current_lang['apiManualPrompt1']);
            return $app;
        }
        if (isset($_POST['api_id']) && isset($_POST['api_hash'])) {
            $app['api_id'] = (int) $_POST['api_id'];
            $app['api_hash'] = $_POST['api_hash'];
            return $app;
        }
        $this->webAPIEcho($settings);

        return null;
    }
    /**
     * Echo to browser.
     *
     * @param string $message Message to echo
     */
    private function webAPIEcho(Settings $settings, string $message = ''): void
    {
        $message = htmlentities($message);
        $title = MTProto::getWebWarnings();
        $title .= htmlentities(Lang::$current_lang['apiManualWeb']);
        $title .= "<br>";
        $title .= sprintf(Lang::$current_lang['apiChooseManualAutoTipWeb'], 'https://docs.madelineproto.xyz/docs/SETTINGS.html');
        $title .= "<br><b>$message</b>";
        $title .= '<ol>';
        $title .= '<li>'.str_replace('https://my.telegram.org', '<a href="https://my.telegram.org" target="_blank">https://my.telegram.org</a>', htmlentities(Lang::$current_lang['apiManualInstructions0'])).'</li>';
        $title .= '<li>'.htmlentities(Lang::$current_lang['apiManualInstructions1']).'</li>';
        $title .= '<li><ul>';
        foreach (['App title', 'Short name', 'URL', 'Platform', 'Description'] as $k => $key) {
            $title .= "<li>$key: ";
            $title .= htmlentities(Lang::$current_lang["apiAppInstructionsManual$k"]);
            $title .= '</li>';
        }
        $title .= '</li></ul>';
        $title .= '<li>'.htmlentities(Lang::$current_lang['apiManualInstructions2']).'</li>';
        $title .= '</ol>';
        $form = '<input type="string" name="api_id" placeholder="API ID" required/>';
        $form .= '<input type="string" name="api_hash" placeholder="API hash" required/>';
        getOutputBufferStream()->write(
            sprintf(
                $settings->getTemplates()->getHtmlTemplate(),
                $title,
                $form,
                Lang::$current_lang['go'],
                ''
            )
        );
    }
}
