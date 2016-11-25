<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\TL;

class TL extends \danog\MadelineProto\Tools
{
    public function __construct($filename)
    {
        \danog\MadelineProto\Logger::log('Loading TL schemes...');
        $this->constructors = new \danog\MadelineProto\TL\TLConstructor();
        $this->methods = new \danog\MadelineProto\TL\TLMethod();
        foreach ($filename as $type => $file) {
            $type = $type === 'mtproto';
            $TL_dict = json_decode(file_get_contents($file), true);

            \danog\MadelineProto\Logger::log('Translating objects...');
            foreach ($TL_dict['constructors'] as $elem) {
                $this->constructors->add($elem, $type);
            }

            \danog\MadelineProto\Logger::log('Translating methods...');
            foreach ($TL_dict['methods'] as $elem) {
                $this->methods->add($elem);
            }
        }
    }

    public function get_named_method_args($method, $arguments)
    {
        $tl_method = $this->methods->find_by_method($method);
        if ($tl_method === false) {
            throw new Exception('Could not extract type: '.$method);
        }

        if (count(array_filter(array_keys($arguments), 'is_string')) == 0) {
            $argcount = 0;
            $newargs = [];
            foreach ($tl_method['params'] as $current_argument) {
                $newargs[$current_argument['name']] = $arguments[$argcount++];
            }
            $arguments = $newargs;
        }

        return $arguments;
    }

    public function serialize_obj($object, $arguments)
    {
        $tl_constructor = $this->constructors->find_by_predicate($object);
        if ($tl_constructor === false) {
            throw new Exception('Could not extract type: '.$object);
        }

        return $this->serialize_generic($tl_constructor, $arguments);
    }

    public function serialize_method($method, $arguments)
    {
        $tl_method = $this->methods->find_by_method($method);
        if ($tl_method === false) {
            throw new Exception('Could not extract type: '.$method);
        }

        return $this->serialize_generic($tl_method, $arguments);
    }

    public function serialize_generic($tl, $arguments)
    {
        $serialized = \danog\PHP\Struct::pack('<i', $tl['id']);
        $flags = 0;
        foreach ($tl['params'] as $cur_flag) {
            if ($cur_flag['flag']) {
                switch ($cur_flag['type']) {
                    case 'true':
                    case 'false':
                        $flags = (isset($arguments[$cur_flag['name']]) && $arguments[$cur_flag['name']]) ? ($flags | $cur_flag['pow']) : ($flags & ~$cur_flag['pow']);
                        unset($arguments[$cur_flag['name']]);
                        break;
                    case 'Bool':
                        $arguments[$cur_flag['name']] = (isset($arguments[$cur_flag['name']]) && $arguments[$cur_flag['name']]) && (($flags & $cur_flag['pow']) != 0);
                        if (($flags & $cur_flag['pow']) == 0) {
                            unset($arguments[$cur_flag['name']]);
                        }
                        break;
                    default:
                        $flags = (isset($arguments[$cur_flag['name']]) && $arguments[$cur_flag['name']] !== null) ? ($flags | $cur_flag['pow']) : ($flags & ~$cur_flag['pow']);
                        break;
                }
            }
        }
        $arguments['flags'] = $flags;
        foreach ($tl['params'] as $current_argument) {
            if (!isset($arguments[$current_argument['name']])) {
                if ($current_argument['flag'] && (in_array($current_argument['type'], ['true', 'false']) || ($flags & $current_argument['pow']) == 0)) {
                    //\danog\MadelineProto\Logger::log('Skipping '.$current_argument['name'].' of type '.$current_argument['type'].'/'.$current_argument['subtype']);
                    continue;
                }
                throw new Exception('Missing required parameter ('.$current_argument['name'].')');
            }
            //\danog\MadelineProto\Logger::log('Serializing '.$current_argument['name'].' of type '.$current_argument['type'].'/'.$current_argument['subtype']);
            $serialized .= $this->serialize_param($current_argument['type'], $current_argument['subtype'], $arguments[$current_argument['name']]);
        }

        return $serialized;
    }

    public function serialize_param($type, $subtype = null, $value = null)
    {
        switch ($type) {
            case 'Bool':
                if (!is_bool($value)) {
                    throw new Exception("serialize_param: given value isn't a boolean");
                }

                return $this->serialize_param('bool'.($value ? 'True' : 'False'));
                break;
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
                return (string) $value;
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
                $concat = \danog\PHP\Struct::pack('<i', $this->constructors->find_by_predicate('vector')['id']);

                $concat .= \danog\PHP\Struct::pack('<l', count($value));
                foreach ($value as $curv) {
                    $concat .= $this->serialize_param($subtype, null, $curv);
                }

                return $concat;
            default:
                return $this->serialize_generic($type, $value);
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
        //\danog\MadelineProto\Logger::log('Deserializing '.$type.'/'.$subtype.' at byte '.ftell($bytes_io));
        switch ($type) {
            case 'Bool':
                $id = \danog\PHP\Struct::unpack('<i', fread($bytes_io, 4)) [0];
                $tl_elem = $this->constructors->find_by_id($id);
                if ($tl_elem === false) {
                    throw new Exception('Could not extract type: '.$type.' with id '.$id);
                }
                $x = $tl_elem['predicate'] === 'boolTrue';
                break;
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
                    throw new Exception('deserialize: subtype is null');
                }
                $count = \danog\PHP\Struct::unpack('<l', fread($bytes_io, 4)) [0];
                $x = [];
                for ($i = 0; $i < $count; $i++) {
                    $x[] = $this->deserialize($bytes_io, $subtype);
                }
                break;
            default:
                $tl_elem = $this->constructors->find_by_predicate($type);
                if ($tl_elem === false) {
                    $id = \danog\PHP\Struct::unpack('<i', fread($bytes_io, 4)) [0];
                    $tl_elem = $this->constructors->find_by_id($id);
                    if ($tl_elem === false) {
                        throw new Exception('Could not extract type: '.$type.' with id '.$id);
                    }
                }

                $base_boxed_types = ['Vector t', 'Int', 'Long', 'Double', 'String', 'Int128', 'Int256'];
                if (in_array($tl_elem['type'], $base_boxed_types)) {
                    $x = $this->deserialize($bytes_io, $tl_elem['predicate'], $subtype);
                } else {
                    $x = ['_' => $tl_elem['predicate']];
                    foreach ($tl_elem['params'] as $arg) {
                        if ($arg['flag']) {
                            switch ($arg['type']) {
                                case 'true':
                                case 'false':

                                    $x[$arg['name']] = ($x['flags'] & $arg['pow']) == 1;
                                    continue 2;
                                    break;
                                case 'Bool':
                                    $default = false;
                                default:
                                    $default = null;
                                    if (($x['flags'] & $arg['pow']) == 0) {
                                        $x[$arg['name']] = $default;
                                        //\danog\MadelineProto\Logger::log('Skipping '.$arg['name'].' of type '.$arg['type'].'/'.$arg['subtype']);
                                        continue 2;
                                    }
                                    break;
                            }
                        }
                        $x[$arg['name']] = $this->deserialize($bytes_io, $arg['type'], $arg['subtype']);
                    }
                    if (isset($x['flags'])) { // I don't think we need this anymore
                        unset($x['flags']);
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
