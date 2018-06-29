<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\Wrappers;

trait ApiTemplates
{
    private $web_api_template = '<!DOCTYPE html>
        <html>
        <head>
        <title>MadelineProto</title>
        </head>
        <body>
        <h1>MadelineProto</h1>
        <form method="POST">
        %s
        <button type="submit"/>Go</button>
        </form>
        <p>%s</p>
        </body>
        </html>';

    public function web_api_echo_template($message, $form)
    {
        return sprintf($this->web_api_template, $form, $message);
    }

    public function get_web_api_template()
    {
        return $this->web_template;
    }

    public function set_web_api_template($template)
    {
        $this->web_template = $template;
    }

    public function web_api_echo($message = '')
    {
        if (!isset($this->my_telegram_org_wrapper)) {
            if (isset($_POST['type'])) {
                if ($_POST['type'] === 'manual') {
                    echo $this->web_api_echo_template('Enter your API ID and API hash<br><b>'.$message.'</b><ol>
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
</ol>', '<input type="string" name="api_id" placeholder="API ID" required/><input type="string" name="api_hash" placeholder="API hash" required/>');
                } else {
                    echo $this->web_api_echo_template('Enter your phone number<br><b>'.$message.'</b>', '<input type="text" name="phone_number" placeholder="Phone number" required/>');
                }
            } else {
                echo $this->web_api_echo_template('Do you want to enter the API id and the API hash manually or automatically?<br>Note that you can also provide it directly in the code using the <a href="https://docs.madelineproto.xyz/docs/SETTINGS.html#settingsapp_infoapi_id">settings</a>.<b>'.$message.'</b>', '<select name="type"><option value="automatic">Automatically</option><option value="manual">Manually</option></select>');
            }
        } else {
            echo $this->web_api_echo_template('Enter your code<br><b>'.$message.'</b>', '<input type="text" name="code" placeholder="Code" required/>');
        }
    }
}
