<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with the MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\MTProtoTools;

/**
 * Manages method and object calls.
 */
class CallHandler extends AuthKeyHandler
{
    public function method_call($method, $args)
    {
        $opts = $this->tl->get_opts($method);
        foreach (range(1, $this->settings['max_tries']['query']) as $i) {
            try {
                $this->send_message($this->tl->serialize_method($method, $args), $this->tl->content_related($method));
                if ($opts['requires_answer'] || true) {
                    $server_answer = $this->recv_message();
                }
            } catch (Exception $e) {
                $this->log->log('An error occurred while calling method '.$method.': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine().'. Recreating connection and retrying to call method...');
                unset($this->sock);
                $this->sock = new Connection($this->settings['connection']['ip_address'], $this->settings['connection']['port'], $this->settings['connection']['protocol']);
                continue;
            }
            if ($server_answer == null) {
                throw new Exception('An error occurred while calling method '.$method.'.');
            }
            $deserialized = $this->tl->deserialize(\danog\MadelineProto\Tools::fopen_and_write('php://memory', 'rw+b', $server_answer));

            return $this->handle_response($deserialized, $method, $args);
        }
        throw new Exception('An error occurred while calling method '.$method.'.');
    }

    public function object_call($object, $kwargs)
    {
        foreach (range(1, $this->settings['max_tries']['query']) as $i) {
            try {
                $this->send_message($this->tl->serialize_obj($object, $kwargs), $this->tl->content_related($object));
//                $server_answer = $this->recv_message();
            } catch (Exception $e) {
                $this->log->log('An error occurred while calling object '.$object.': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine().'. Recreating connection and retrying to call object...');
                unset($this->sock);
                $this->sock = new Connection($this->settings['connection']['ip_address'], $this->settings['connection']['port'], $this->settings['connection']['protocol']);
                continue;
            }

            return;
//            if ($server_answer == null) {
//                throw new Exception('An error occurred while calling object '.$object.'.');
//            }
//            $deserialized = $this->tl->deserialize(\danog\MadelineProto\Tools::fopen_and_write('php://memory', 'rw+b', $server_answer));
//            return $deserialized;
        }
        throw new Exception('An error occurred while calling object '.$object.'.');
    }

}
