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

namespace danog\MadelineProto;

class Conversion
{
    public static function telethon($session, $new_session, $settings = [])
    {
        set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        if (!extension_loaded('sqlite3')) {
            throw new Exception(['extension', 'sqlite3']);
        }
        if (!isset(pathinfo($session)['extension'])) {
            $session .= '.session';
        }
        $session = Absolute::absolute($session);
        $sqlite = new \PDO("sqlite:$session");
        $sqlite->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);

        $sessions = $sqlite->query('SELECT * FROM sessions')->fetchAll();
        $MadelineProto = new \danog\MadelineProto\API($new_session, $settings);
        foreach ($sessions as $dc) {
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->auth_key = ['server_salt' => '', 'connection_inited' => true, 'id' => substr(sha1($dc['auth_key'], true), -8), 'auth_key' => $dc['auth_key']];
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->temp_auth_key = null;
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->ip = $dc['server_address'];
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->port = $dc['port'];
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->authorized = true;
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->session_id = $MadelineProto->random(8);
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->session_in_seq_no = 0;
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->session_out_seq_no = 0;
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->incoming_messages = [];
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->outgoing_messages = [];
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->new_outgoing = [];
            $MadelineProto->API->datacenter->sockets[$dc['dc_id']]->incoming = [];
        }
        $MadelineProto->API->authorized = MTProto::LOGGED_IN;
        $MadelineProto->API->init_authorization();

        return $MadelineProto;
    }

    public static function pyrogram($session, $new_session, $settings = [])
    {
        set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        if (!isset(pathinfo($session)['extension'])) {
            $session .= '.session';
        }
        $session = Absolute::absolute($session);
        $session = json_decode(file_get_contents($session), true);
        $session['auth_key'] = base64_decode(implode('', $session['auth_key']));

        $settings['connection_settings']['all']['test_mode'] = $session['test_mode'];

        $MadelineProto = new \danog\MadelineProto\API($new_session, $settings);

        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->auth_key = ['server_salt' => '', 'connection_inited' => true, 'id' => substr(sha1($session['auth_key'], true), -8), 'auth_key' => $session['auth_key']];
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->temp_auth_key = null;
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->authorized = true;
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->session_id = $MadelineProto->random(8);
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->session_in_seq_no = 0;
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->session_out_seq_no = 0;
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->incoming_messages = [];
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->outgoing_messages = [];
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->new_outgoing = [];
        $MadelineProto->API->datacenter->sockets[$session['dc_id']]->incoming = [];

        $MadelineProto->API->authorized = MTProto::LOGGED_IN;
        $MadelineProto->API->init_authorization();

        return $MadelineProto;
    }
}
