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

class TL extends \danog\MadelineProto\Tools
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
            $TL_dict = json_decode(file_get_contents($filename), true);
        }

        \danog\MadelineProto\Logger::log('Translating objects...');
        $this->constructors = $TL_dict['constructors'];
        $this->constructor_id = [];
        $this->constructor_type = [];
        foreach ($this->constructors as $elem) {
            $z = new \danog\MadelineProto\TL\TLConstructor($elem);
            $this->constructor_id[$z->id] = $z;
            $this->constructor_type[$z->predicate] = $z;
        }

        \danog\MadelineProto\Logger::log('Translating methods...');
        $this->methods = $TL_dict['methods'];
        $this->method_id = [];
        $this->method_name = [];
        $this->method_name_namespaced = [];
        foreach ($this->methods as $elem) {
            $z = new \danog\MadelineProto\TL\TLMethod($elem);
            $this->method_id[$z->id] = $z;
            $this->method_name[$z->method] = $z;
            $this->method_name_namespaced[$z->method] = explode('.', $z->method);
        }
    }

    public function get_named_method_args($method, $arguments)
    {
        if (!isset($this->method_name[$method])) {
            throw new Exception('Could not extract type: '.$method);
        }
        $tl_method = $this->method_name[$method];


        if (count(array_filter(array_keys($arguments), 'is_string')) == 0) {
            $argcount = 0;
            $newargs = [];
            foreach ($tl_method->params as $current_argument) {
                $newargs[$current_argument['name']] = $arguments[$argcount++];
            }
            $arguments = $newargs;
        }

        return $arguments;
    }

    public function serialize_obj($object, $arguments)
    {
        if (!isset($this->constructor_type[$object])) {
            throw new Exception('Could not extract type: '.$object);
        }

        $tl_method = $this->constructor_type[$object];
        $serialized = \danog\PHP\Struct::pack('<i', $tl_constructor->id);

        foreach ($tl_constructor->params as $current_argument) {
            $serialized .= $this->serialize_param($current_argument['type'], $current_argument['subtype'], $arguments[$current_argument['name']]);
        }

        return $serialized;
    }

    public function serialize_method($method, $arguments)
    {
        if (!isset($this->method_name[$method])) {
            throw new Exception('Could not extract type: '.$method);
        }

        $tl_method = $this->method_name[$method];
        $serialized = \danog\PHP\Struct::pack('<i', $tl_method->id);

        foreach ($tl_method->params as $current_argument) {
            if (!isset($arguments[$current_argument['name']])) {
                if ($current_argument['name'] == 'flags') {
                    $arguments['flags'] = 0;
                } else {
                    if ($current_argument['opt']) {
                        continue;
                    }
                    throw new Exception('Missing required parameter ('.$current_argument['name'].')');
                }
            }
            $serialized .= $this->serialize_param($current_argument['type'], $current_argument['subtype'], $arguments[$current_argument['name']]);
        }

        return $serialized;
    }

    public function serialize_param($type, $subtype, $value)
    {
        switch ($type) {
            case 'int':
                if (!is_numeric($value)) {
                    throw new Exception("serialize_param: given value isn't numeric");
                }

                return \danog\PHP\Struct::pack('<i', $value);
                break;
            case '#':
                if (!is_numeric($value)) {
                    throw new Exception("serialize_param: given value isn't numeric");
                }

                return \danog\PHP\Struct::pack('<I', $value);
                break;
            case 'long':
                if (!is_numeric($value)) {
                    throw new Exception("serialize_param: given value isn't numeric");
                }

                return \danog\PHP\Struct::pack('<q', $value);
                break;
            case 'int128':
            case 'int256':
                return (string)$value;
                break;
            case 'double':
                return \danog\PHP\Struct::pack('<d', $value);
                break;
            case 'string':
            case 'bytes':
                $l = strlen($value);
                $concat = '';
                if ($l <= 253) {
                    $concat .= \danog\PHP\Struct::pack('<b', $l);
                    $concat .= $value;
                    $concat .= pack('@'.$this->posmod((-$l - 1), 4));
                } else {
                    $concat .= $this->string2bin('\xfe');
                    $concat .= substr(\danog\PHP\Struct::pack('<i', $l), 0, 3);
                    $concat .= $value;
                    $concat .= pack('@'.$this->posmod(-$l, 4));
                }

                return $concat;
                break;
            case '!X':
                return $value;
            case 'Vector t':
                $concat = \danog\PHP\Struct::pack('<i', $this->constructor_type['vector']->id);

                $concat .= \danog\PHP\Struct::pack('<l', count($value));
                foreach ($value as $curv) {
                    $concat .= $this->serialize_param($subtype, null, $curv);
                }

                return $concat;
            default:
                throw new Exception("Couldn't serialize param with type ".$type);
                break;
        }
    }

    public function get_length($bytes_io, $type = null, $subtype = null)
    {
        $this->deserialize($bytes_io, $type, $subtype);

        return ftell($bytes_io);
    }

    /**
     * :type bytes_io: io.BytesIO object.
     */
    public function deserialize($bytes_io, $type = null, $subtype = null)
    {
        if (!(get_resource_type($bytes_io) == 'file' || get_resource_type($bytes_io) == 'stream')) {
            throw new Exception('An invalid bytes_io handle was provided.');
        }
        switch ($type) {
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
                    $long_len = \danog\PHP\Struct::unpack('<I', fread($bytes_io, 3).$this->string2bin('\x00')) [0];
                    $x = fread($bytes_io, $long_len);
                    $resto = $this->posmod(-$long_len, 4);
                    if ($resto > 0) {
                        fread($bytes_io, $resto);
                    }
                } else {
                    $x = fread($bytes_io, $l);
                    $resto = $this->posmod(-($l + 1), 4);
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
                foreach ($this->range($count) as $i) {
                    $x[] = $this->deserialize($bytes_io, $subtype);
                }
                break;
            default:
                if (isset($this->constructor_type[$type])) {
                    $tl_elem = $this->constructor_type[$type];
                } else {
                    $i = \danog\PHP\Struct::unpack('<i', fread($bytes_io, 4)) [0];
                    if (!isset($this->constructor_id[$i])) {
                        throw new Exception('Could not extract type: '.$type);
                    }
                    $tl_elem = $this->constructor_id[$i];
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
        return !in_array(
            $method,
            [
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
            ]
        );
    }
}
