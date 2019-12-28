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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Wrappers;

use function Amp\ByteStream\getOutputBufferStream;

trait ApiTemplates
{
    private $web_api_template = '<!DOCTYPE html>
        <html>
        <head>
        <title>MadelineProto</title>
        </head>
        <body>
        <h1>MadelineProto</h1>
        <p>%s</p>
        <form method="POST">
        %s
        <button type="submit"/>Go</button>
        </form>
        </body>
        </html>';

    private function webAPIEchoTemplate($message, $form)
    {
        return \sprintf($this->web_api_template, $message, $form);
    }

    /**
     * Get web API login HTML template string.
     *
     * @return string
     */
    public function getWebAPITemplate(): string
    {
        return $this->web_template;
    }

    /**
     * Set web API login HTML template string.
     *
     * @return string
     */
    public function setWebAPITemplate(string $template)
    {
        $this->web_template = $template;
    }

    private function webAPIEcho(string $message = '')
    {
        $stdout = getOutputBufferStream();
        if (!isset($this->my_telegram_org_wrapper)) {
            if (isset($_POST['type'])) {
                if ($_POST['type'] === 'manual') {
                    yield $stdout->write($this->webAPIEchoTemplate('Enter your API ID and API hash<br><b>'.$message.'</b><ol>
<li>Login to my.telegram.org</li>
<li>Go to API development tools</li>
<li>
  <ul>
    <li>App title: your app&apos;s name, can be anything</li>
    <li>Short name: your app&apos;s short name, only numbers and letters</li>
    <li>Platform: Web</li>
    <li>Description: describe your app here</li>
  </ul>
</li>
<li>Click on create application</li>
</ol>', '<input type="string" name="api_id" placeholder="API ID" required/><input type="string" name="api_hash" placeholder="API hash" required/>'));
                } else {
                    yield $stdout->write($this->webAPIEchoTemplate('Enter a phone number that is <b>already registered</b> on telegram to get the API ID<br><b>'.$message.'</b>', '<input type="text" name="phone_number" placeholder="Phone number" required/>'));
                }
            } else {
                if ($message) {
                    $message = '<br><br>'.$message;
                }
                yield $stdout->write($this->webAPIEchoTemplate('Do you want to enter the API id and the API hash manually or automatically?<br>Note that you can also provide it directly in the code using the <a href="https://docs.madelineproto.xyz/docs/SETTINGS.html#settingsapp_infoapi_id">settings</a>.<b>'.$message.'</b>', '<select name="type"><option value="automatic">Automatically</option><option value="manual">Manually</option></select>'));
            }
        } else {
            if (!$this->my_telegram_org_wrapper->loggedIn()) {
                yield $stdout->write($this->webAPIEchoTemplate('Enter your code<br><b>'.$message.'</b>', '<input type="text" name="code" placeholder="Code" required/>'));
            } else {
                yield $stdout->write($this->webAPIEchoTemplate(
                    'Enter the API info<br><b>'.$message.'</b>',
                    '<input type="hidden" name="creating_app" value="yes" required/>
                    Enter the app name, can be anything: <br><input type="text" name="app_title" required/><br>
                    <br>Enter the app&apos;s short name, alphanumeric, 5-32 chars: <br><input type="text" name="app_shortname" required/><br>
                    <br>Enter the app/website URL, or https://t.me/yourusername: <br><input type="text" name="app_url" required/><br>
                    <br>Enter the app platform: <br>
          <label>
            <input type="radio" name="app_platform" value="android" checked> Android
          </label>
          <label>
            <input type="radio" name="app_platform" value="ios"> iOS
          </label>
          <label>
            <input type="radio" name="app_platform" value="wp"> Windows Phone
          </label>
          <label>
            <input type="radio" name="app_platform" value="bb"> BlackBerry
          </label>
          <label>
            <input type="radio" name="app_platform" value="desktop"> Desktop
          </label>
          <label>
            <input type="radio" name="app_platform" value="web"> Web
          </label>
          <label>
            <input type="radio" name="app_platform" value="ubp"> Ubuntu phone
          </label>
          <label>
            <input type="radio" name="app_platform" value="other"> Other (specify in description)
          </label>
          <br><br>Enter the app description, can be anything: <br><textarea name="app_desc" required></textarea><br><br>
                    '
                ));
            }
        }
    }
}
