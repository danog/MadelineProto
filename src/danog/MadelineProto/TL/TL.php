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

namespace danog\MadelineProto\TL;

class TL
{
    public function __construct($filename)
    {
        if (is_array($filename)) {
            $TL_dict = ['constructors' => [], 'methods' => []];
            foreach ($filename as $file) {
                $TL_dict['constructors'] = array_merge(json_decode(file_get_contents($file), true)['constructors'], $TL_dict['constructors']);
                $TL_dict['methods'] = array_merge(json_decode(file_get_contents($file), true)['methods'], $TL_dict['methods']);
            }
        } else {
            $TL_dict = json_decode(file_get_contents($file), true);
        }
        $this->constructors = $TL_dict['constructors'];
        $this->constructor_id = [];
        $this->constructor_type = [];
        foreach ($this->constructors as $elem) {
            $z = new TLConstructor($elem);
            $this->constructor_id[$z->id] = $z;
            $this->constructor_type[$z->predicate] = $z;
        }
        $this->methods = $TL_dict['methods'];
        $this->method_id = [];
        $this->method_name = [];
        foreach ($this->methods as $elem) {
            $z = new TLMethod($elem);
            $this->method_id[$z->id] = $z;
            $this->method_name[$z->method] = $z;
        }
    }

    public function serialize_obj($type_, $kwargs)
    {
        $bytes_io = '';
        if (isset($this->constructor_type[$type_])) {
            $tl_constructor = $this->constructor_type[$type_];
        } else {
            throw new Exception('Could not extract type: '.$type_);
        }
        $bytes_io .= \danog\PHP\Struct::pack('<i', $tl_constructor->id);
        foreach ($tl_constructor->params as $arg) {
            $bytes_io .= $this->serialize_param($arg['type'], $kwargs[$arg['name']]);
        }

        return $bytes_io;
    }

    public function get_named_method_args($type_, $kwargs)
    {
        if (isset($this->method_name[$type_])) {
            $tl_method = $this->method_name[$type_];
        } else {
            throw new Exception('Could not extract type: '.$type_);
        }

        if (count(array_filter(array_keys($kwargs), 'is_string')) == 0) {
            $argcount = 0;
            $newargs = [];
            foreach ($tl_method->params as $arg) {
                $newargs[$arg['name']] = $kwargs[$argcount++];
            }
            $kwargs = $newargs;
        }

        return $kwargs;
    }

    public function serialize_method($type_, $kwargs)
    {
        $bytes_io = '';
        if (isset($this->method_name[$type_])) {
            $tl_method = $this->method_name[$type_];
        } else {
            throw new Exception('Could not extract type: '.$type_);
        }
        $bytes_io .= \danog\PHP\Struct::pack('<i', $tl_method->id);
        foreach ($tl_method->params as $arg) {
            $bytes_io .= $this->serialize_param($arg['type'], $kwargs[$arg['name']]);
        }

        return $bytes_io;
    }

    public function serialize_param($type_, $value)
    {
        switch ($type_) {
            case 'int':
                if (!is_numeric($value)) {
                    throw new Exception("serialize_param: given value isn't numeric");
                }
                if (!(strlen(decbin($value)) <= 32)) {
                    throw new Exception('Given value is too long.');
                }

                return \danog\PHP\Struct::pack('<i', $value);
                break;
            case 'long':
                if (!is_numeric($value)) {
                    throw new Exception("serialize_param: given value isn't numeric");
                }

                return \danog\PHP\Struct::pack('<q', $value);
                break;
            case 'int128':
            case 'int256':
                if (!is_string($value)) {
                    throw new Exception("serialize_param: given value isn't a string");
                }

                return $value;
                break;
            case 'string':
            case 'bytes':
                $l = strlen($value);
                $concat = '';
                if ($l <= 253) {
                    $concat .= \danog\PHP\Struct::pack('<b', $l);
                    $concat .= $value;
                    $concat .= pack('@'.\danog\MadelineProto\Tools::posmod((-$l - 1), 4));
                } else {
                    $concat .= \danog\MadelineProto\Tools::string2bin('\xfe');
                    $concat .= substr(\danog\PHP\Struct::pack('<i', $l), 0, 3);
                    $concat .= $value;
                    $concat .= pack('@'.\danog\MadelineProto\Tools::posmod(-$l, 4));
                }

                return $concat;
                break;

            default:
                throw new Exception("Couldn't serialize param with type ".$type_);
                break;
        }
    }

    public function get_length($bytes_io, $type_ = null, $subtype = null)
    {
        $this->deserialize($bytes_io, $type_, $subtype);

        return ftell($bytes_io);
    }

    /**
     * :type bytes_io: io.BytesIO object.
     */
    public function deserialize($bytes_io, $type_ = null, $subtype = null)
    {
        if (!(get_resource_type($bytes_io) == 'file' || get_resource_type($bytes_io) == 'stream')) {
            throw new Exception('An invalid bytes_io handle provided.');
        }
        switch ($type_) {
            case 'int':
                $x = \danog\PHP\Struct::unpack('<i', fread($bytes_io, 4)) [0];
                break;
            case '#':
                $x = \danog\PHP\Struct::unpack('<I', fread($bytes_io, 4)) [0];
                break;
            case 'long':
                $x = \danog\PHP\Struct::unpack('<q', fread($bytes_io, 8)) [0];
                break;
            case 'double':
                $x = \danog\PHP\Struct::unpack('<d', fread($bytes_io, 8)) [0];
                break;
            case 'int128':
                $x = fread($bytes_io, 16);
                break;
            case 'int256':
                $x = fread($bytes_io, 32);
                break;
            case 'string':
            case 'bytes':
                $l = \danog\PHP\Struct::unpack('<B', fread($bytes_io, 1)) [0];
                if ($l > 254) {
                    throw new Exception('Length is too big');
                }
                if ($l == 254) {
                    $long_len = \danog\PHP\Struct::unpack('<I', fread($bytes_io, 3).\danog\MadelineProto\Tools::string2bin('\x00')) [0];
                    $x = fread($bytes_io, $long_len);
                    $resto = \danog\MadelineProto\Tools::posmod(-$long_len, 4);
                    if ($resto > 0) {
                        fread($bytes_io, $resto);
                    }
                } else {
                    $x = fread($bytes_io, $l);
                    $resto = \danog\MadelineProto\Tools::posmod(-($l + 1), 4);
                    if ($resto > 0) {
                        fread($bytes_io, $resto);
                    }
                }
                if (!is_string($x)) {
                    throw new Exception("deserialize: generated value isn't a string");
                }
                break;
            case 'vector':
                if ($subtype == null) {
                    throw new Exception("deserialize: subtype isn't null");
                }
                $count = \danog\PHP\Struct::unpack('<l', fread($bytes_io, 4)) [0];
                $x = [];
                foreach (\danog\MadelineProto\Tools::range($count) as $i) {
                    $x[] = $this->deserialize($bytes_io, $subtype);
                }
                break;
            default:
                if (isset($this->constructor_type[$type_])) {
                    $tl_elem = $this->constructor_type[$type_];
                } else {
                    $Idata = fread($bytes_io, 4);
                    $i = \danog\PHP\Struct::unpack('<i', $Idata) [0];
                    if (isset($this->constructor_id[$i])) {
                        $tl_elem = $this->constructor_id[$i];
                    } else {
                        throw new Exception('Could not extract type: '.$type_);
                    }
                }

                $base_boxed_types = ['Vector t', 'Int', 'Long', 'Double', 'String', 'Int128', 'Int256'];
                if (in_array($tl_elem->type, $base_boxed_types)) {
                    $x = $this->deserialize($bytes_io, $tl_elem->predicate, $subtype);
                } else {
                    $x = ['_' => $tl_elem->predicate];
                    foreach ($tl_elem->params as $arg) {
                        $x[$arg['name']] = $this->deserialize($bytes_io, $arg['type'], $arg['subtype']);
                    }
                }
                break;
        }

        return $x;
    }

    public function content_related($method)
    {
        return !in_array($method, [
            'rpc_result',
            'rpc_error',
            'rpc_drop_answer',
            'rpc_answer_unknown',
            'rpc_answer_dropped_running',
            'rpc_answer_dropped',
            'get_future_salts',
            'future_salt',
            'future_salts',
            'ping',
            'pong',
            'ping_delay_disconnect',
            'destroy_session',
            'destroy_session_ok',
            'destroy_session_none',
            'new_session_created',
            'msg_container',
            'msg_copy',
            'gzip_packed',
            'http_wait',
            'msgs_ack',
            'bad_msg_notification',
            'bad_server_salt',
            'msgs_state_req',
            'msgs_state_info',
            'msgs_all_info',
            'msg_detailed_info',
            'msg_new_detailed_info',
            'msg_resend_req',
            'msg_resend_ans_req',
        ]);
    }
}
