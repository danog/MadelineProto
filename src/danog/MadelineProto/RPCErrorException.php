<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

class RPCErrorException extends \Exception
{
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        switch ($message) {
            case 'RPC_MCGET_FAIL':
            case 'RPC_CALL_FAIL': $message = 'Telegram is having internal issues, please try again later.'; break;
            case 'CHANNEL_PRIVATE':$message = "You haven't joined this channel/supergroup"; break;
            case 'FLOOD_WAIT_666':$message = 'Spooky af m8'; break;
            case 'USER_IS_BOT':
            case 'BOT_METHOD_INVALID':$message = 'This method cannot be run by a bot'; break;
            case 'PHONE_CODE_EXPIRED': $message = 'The phone code you provided has expired, this may happen if it was sent to any chat on telegram (if the code is sent through a telegram chat (not the official account) to avoid it append or prepend to the code some chars)';
            case 'USERNAME_INVALID': $message = 'The provided username is not valid';
        }
        parent::__construct($message, $code, $previous);
        if (in_array($message, ['The provided username is not valid'])) return;
        \Rollbar\Rollbar::log($this);
    }
}
