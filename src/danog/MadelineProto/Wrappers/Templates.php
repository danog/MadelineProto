<?php

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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Wrappers;

use function Amp\ByteStream\getOutputBufferStream;

trait Templates
{
    private function webEcho(string $message = '')
    {
        $stdout = getOutputBufferStream();
        switch ($this->authorized) {
            case self::NOT_LOGGED_IN:
            if (isset($_POST['type'])) {
                if ($_POST['type'] === 'phone') {
                    yield $stdout->write($this->webEchoTemplate('Enter your phone number<br><b>'.$message.'</b>', '<input type="text" name="phone_number" placeholder="Phone number" required/>'));
                } else {
                    yield $stdout->write($this->webEchoTemplate('Enter your bot token<br><b>'.$message.'</b>', '<input type="text" name="token" placeholder="Bot token" required/>'));
                }
            } else {
                yield $stdout->write($this->webEchoTemplate('Do you want to login as user or bot?<br><b>'.$message.'</b>', '<select name="type"><option value="phone">User</option><option value="bot">Bot</option></select>'));
            }
            break;

            case self::WAITING_CODE:
            yield $stdout->write($this->webEchoTemplate('Enter your code<br><b>'.$message.'</b>', '<input type="text" name="phone_code" placeholder="Phone code" required/>'));
            break;

            case self::WAITING_PASSWORD:
            yield $stdout->write($this->webEchoTemplate('Enter your password<br><b>'.$message.'</b>', '<input type="password" name="password" placeholder="Hint: '.$this->authorization['hint'].'" required/>'));
            break;

            case self::WAITING_SIGNUP:
            yield $stdout->write($this->webEchoTemplate('Sign up please<br><b>'.$message.'</b>', '<input type="text" name="first_name" placeholder="First name" required/><input type="text" name="last_name" placeholder="Last name"/>'));
            break;
        }
    }

    private $web_template = '<!DOCTYPE html>
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

    private function webEchoTemplate($message, $form): string
    {
        return \sprintf($this->web_template, $form, $message);
    }

    /**
     * Get web template.
     *
     * @return string
     */
    public function getWebTemplate(): string
    {
        return $this->web_template;
    }

    /**
     * Set web template.
     *
     * @param string $template Template
     *
     * @return void
     */
    public function setWebTemplate(string $template): void
    {
        $this->web_template = $template;
    }
}
