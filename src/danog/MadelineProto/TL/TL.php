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

namespace danog\MadelineProto\TL;

trait TL
{
    public $encrypted_layer = -1;
    public $constructors;
    public $methods;
    public $td_constructors;
    public $td_methods;
    public $td_descriptions;

    public function construct_tl($files)
    {
        \danog\MadelineProto\Logger::log(['Loading TL schemes...'], \danog\MadelineProto\Logger::VERBOSE);
        $this->constructors = new TLConstructor();
        $this->methods = new TLMethod();
        $this->td_constructors = new TLConstructor();
        $this->td_methods = new TLMethod();
        $this->td_descriptions = ['types' => [], 'constructors' => [], 'methods' => []];
        foreach ($files as $scheme_type => $file) {
            \danog\MadelineProto\Logger::log(['Parsing '.basename($file).'...'], \danog\MadelineProto\Logger::VERBOSE);
            $filec = file_get_contents($file);
            $TL_dict = json_decode($filec, true);
            if ($TL_dict === null) {
                $TL_dict = ['methods' => [], 'constructors' => []];
                $type = 'constructors';
                $layer = null;
                $tl_file = explode("\n", $filec);
                $key = 0;
                $e = null;
                $class = null;
                $dparams = [];
                foreach ($tl_file as $line) {
                    $line = rtrim($line);
                    if (preg_match('|^//@|', $line)) {
                        $list = explode(' @', str_replace('//', ' ', $line));
                        foreach ($list as $elem) {
                            if ($elem === '') {
                                continue;
                            }
                            $elem = explode(' ', $elem, 2);
                            if ($elem[0] === 'class') {
                                $elem = explode(' ', $elem[1], 2);
                                $class = $elem[0];
                                continue;
                            }
                            if ($elem[0] === 'description') {
                                if (!is_null($class)) {
                                    $this->td_descriptions['types'][$class] = $elem[1];
                                    $class = null;
                                } else {
                                    $e = $elem[1];
                                }
                                continue;
                            }
                            if ($elem[0] === 'param_description') {
                                $elem[0] = 'description';
                            }
                            $dparams[$elem[0]] = $elem[1];
                        }
                        continue;
                    }
                    $line = preg_replace(['|//.*|', '|^\s+$|'], '', $line);
                    if ($line === '') {
                        continue;
                    }
                    if ($line === '---functions---') {
                        $type = 'methods';
                        continue;
                    }
                    if ($line === '---types---') {
                        $type = 'constructors';
                        continue;
                    }
                    if (preg_match('|^===\d*===|', $line)) {
                        $layer = (int) preg_replace('|\D*|', '', $line);
                        continue;
                    }
                    if (preg_match('/^vector#/', $line)) {
                        continue;
                    }
                    if (preg_match('/ \?= /', $line)) {
                        continue;
                    }
                    $name = preg_replace(['/#.*/', '/\s.*/'], '', $line);
                    if (in_array($name, ['bytes', 'int128', 'int256', 'int512'])) {
                        continue;
                    }
                    $clean = preg_replace([
                        '/:bytes /',
                        '/;/',
                        '/#[a-f0-9]+ /',
                        '/ [a-zA-Z0-9_]+\:flags\.[0-9]+\?true/',
                        '/[<]/',
                        '/[>]/',
                        '/  /',
                        '/^ /',
                        '/ $/',
                        '/\?bytes /',
                        '/{/',
                        '/}/',
                     ], [
                        ':string ',
                        '',
                        ' ',
                        '',
                        ' ',
                        ' ',
                        ' ',
                        '',
                        '',
                        '?string ',
                        '',
                        '', ], $line);
                    $id = hash('crc32b', $clean);
                    if (preg_match('/^[^\s]+#/', $line)) {
                        $nid = str_pad(preg_replace(['/^[^#]+#/', '/\s.+/'], '', $line), 8, '0', \STR_PAD_LEFT);
                        if ($id !== $nid && $scheme_type !== 'botAPI') {
                            \danog\MadelineProto\Logger::log(['CRC32 mismatch ('.$id.', '.$nid.') for '.$line], \danog\MadelineProto\Logger::ERROR);
                        }
                        $id = $nid;
                    }
                    if (!is_null($e)) {
                        $this->td_descriptions[$type][$name] = ['description' => $e, 'params' => $dparams];
                        $e = null;
                        $dparams = [];
                    }
                    $TL_dict[$type][$key][$type === 'constructors' ? 'predicate' : 'method'] = $name;
                    $TL_dict[$type][$key]['id'] = strrev(hex2bin($id));
                    $TL_dict[$type][$key]['params'] = [];
                    $TL_dict[$type][$key]['type'] = preg_replace(['/.+\s/', '/;/'], '', $line);
                    if ($layer !== null) {
                        $TL_dict[$type][$key]['layer'] = $layer;
                    }
                    foreach (explode(' ', preg_replace(['/^[^\s]+\s/', '/=\s[^\s]+/', '/\s$/'], '', $line)) as $param) {
                        if ($param === '') {
                            continue;
                        }
                        if ($param[0] === '{') {
                            continue;
                        }
                        $explode = explode(':', $param);
                        $TL_dict[$type][$key]['params'][] = ['name' => $explode[0], 'type' => $explode[1]];
                    }
                    $key++;
                }
            } else {
                foreach ($TL_dict['constructors'] as $key => $value) {
                    $TL_dict['constructors'][$key]['id'] = $this->pack_signed_int($TL_dict['constructors'][$key]['id']);
                }
                foreach ($TL_dict['methods'] as $key => $value) {
                    $TL_dict['methods'][$key]['id'] = $this->pack_signed_int($TL_dict['methods'][$key]['id']);
                }
            }
            if (empty($TL_dict) || empty($TL_dict['constructors']) || !isset($TL_dict['methods'])) {
                throw new Exception('Invalid source file was provided: '.$file);
            }
            $orig = $this->encrypted_layer;
            \danog\MadelineProto\Logger::log(['Translating objects...'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);
            foreach ($TL_dict['constructors'] as $elem) {
                if ($scheme_type === 'secret') {
                    $this->encrypted_layer = max($this->encrypted_layer, $elem['layer']);
                }
                $this->{($scheme_type === 'td' ? 'td_' : '').'constructors'}->add($elem, $scheme_type);
            }

            \danog\MadelineProto\Logger::log(['Translating methods...'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);
            foreach ($TL_dict['methods'] as $elem) {
                $this->{($scheme_type === 'td' ? 'td_' : '').'methods'}->add($elem);
                if ($scheme_type === 'secret') {
                    $this->encrypted_layer = max($this->encrypted_layer, $elem['layer']);
                }
            }
            if ($this->encrypted_layer != $orig && isset($this->secret_chats)) {
                foreach ($this->secret_chats as $chat => $data) {
                    $this->notify_layer($chat);
                }
            }
        }
        if (isset($files['td']) && isset($files['telegram'])) {
            foreach ($this->td_constructors->by_id as $id => $data) {
                $name = $data['predicate'];
                if ($this->constructors->find_by_id($id) === false) {
                    unset($this->td_descriptions['constructors'][$name]);
                } else {
                    if (!count($this->td_descriptions['constructors'][$name]['params'])) {
                        continue;
                    }
                    foreach ($this->td_descriptions['constructors'][$name]['params'] as $k => $param) {
                        $this->td_descriptions['constructors'][$name]['params'][$k] = str_replace('nullable', 'optional', $param);
                    }
                }
            }
            foreach ($this->td_methods->by_id as $id => $data) {
                $name = $data['method'];
                if ($this->methods->find_by_id($id) === false) {
                    unset($this->td_descriptions['methods'][$name]);
                } else {
                    foreach ($this->td_descriptions['methods'][$name]['params'] as $k => $param) {
                        $this->td_descriptions['constructors'][$name]['params'][$k] = str_replace('nullable', 'optional', $param);
                    }
                }
            }
        }
    }

    public function get_method_namespaces()
    {
        $res = [];
        foreach ($this->methods->method_namespace as $pair) {
            $a = key($pair);
            $res[$a] = $a;
        }

        return $res;
    }

    public function get_methods_namespaced()
    {
        return $this->methods->method_namespace;
    }

    public function deserialize_bool($id)
    {
        $tl_elem = $this->constructors->find_by_id($id);
        if ($tl_elem === false) {
            throw new Exception('Could not extract boolean');
        }

        return $tl_elem['predicate'] === 'boolTrue';
    }

    public function serialize_object($type, $object, $ctx, $layer = -1)
    {
        switch ($type['type']) {
            case 'int':
                if (!is_numeric($object)) {
                    throw new Exception("given value isn't numeric");
                }

                return $this->pack_signed_int($object);
            case '#':
                if (!is_numeric($object)) {
                    throw new Exception("given value isn't numeric");
                }

                return $this->pack_unsigned_int($object);
            case 'long':
                if (is_object($object)) {
                    return str_pad(strrev($object->toBytes()), 8, chr(0));
                }

                if (is_string($object) && strlen($object) === 8) {
                    return $object;
                }
                if (is_string($object) && strlen($object) === 9 && $object[0] === 'a') {
                    return substr($object, 1);
                }
                if (!is_numeric($object)) {
                    throw new Exception("given value isn't numeric");
                }

                return $this->pack_signed_long($object);
            case 'int128':
                if (strlen($object) !== 16) {
                    throw new Exception('Given value is not 16 bytes long');
                }

                return (string) $object;
            case 'int256':
                if (strlen($object) !== 32) {
                    throw new Exception('Given value is not 32 bytes long');
                }

                return (string) $object;
            case 'int512':
                if (strlen($object) !== 64) {
                    throw new Exception('Given value is not 64 bytes long');
                }

                return (string) $object;
            case 'double':
                return $this->pack_double($object);
            case 'string':
                if (!is_string($object)) {
                    throw new Exception("You didn't provide a valid string");
                }

                $object = pack('C*', ...unpack('C*', $object));
                $l = strlen($object);
                $concat = '';
                if ($l <= 253) {
                    $concat .= chr($l);
                    $concat .= $object;
                    $concat .= pack('@'.$this->posmod((-$l - 1), 4));
                } else {
                    $concat .= chr(254);
                    $concat .= substr($this->pack_signed_int($l), 0, 3);
                    $concat .= $object;
                    $concat .= pack('@'.$this->posmod(-$l, 4));
                }

                return $concat;
            case 'bytes':
                if (!is_string($object) && !($object instanceof \danog\MadelineProto\TL\Types\Bytes)) {
                    throw new Exception("You didn't provide a valid string");
                }
                $l = strlen($object);
                $concat = '';
                if ($l <= 253) {
                    $concat .= chr($l);
                    $concat .= $object;
                    $concat .= pack('@'.$this->posmod((-$l - 1), 4));
                } else {
                    $concat .= chr(254);
                    $concat .= substr($this->pack_signed_int($l), 0, 3);
                    $concat .= $object;
                    $concat .= pack('@'.$this->posmod(-$l, 4));
                }

                return $concat;
            case 'Bool':
                return $this->constructors->find_by_predicate(((bool) $object) ? 'boolTrue' : 'boolFalse')['id'];
            case 'true':
                return;
            case '!X':
                return $object;
            case 'Vector t':
                if (!is_array($object)) {
                    throw new Exception("You didn't provide a valid array");
                }
                $concat = $this->constructors->find_by_predicate('vector')['id'];
                $concat .= $this->pack_unsigned_int(count($object));
                foreach ($object as $k => $current_object) {
                    $concat .= $this->serialize_object(['type' => $type['subtype']], $current_object, $k);
                }

                return $concat;

        }
        $auto = false;
        if ((!is_array($object) || (isset($object['_']) && $this->constructors->find_by_predicate($object['_'])['type'] !== $type['type'])) && in_array($type['type'], ['User', 'InputUser', 'Chat', 'InputChannel', 'Peer', 'InputPeer'])) {
            $object = $this->get_info($object);
            if (!isset($object[$type['type']])) {
                throw new \danog\MadelineProto\Exception('This peer is not present in the internal peer database');
            }
            $object = $object[$type['type']];
        }
        if (!isset($object['_'])) {
            $constructorData = $this->constructors->find_by_predicate($type['type'], $layer);

            if ($constructorData === false) {
                throw new Exception('Predicate (value under _) was not set!');
            }
            $auto = true;
            $object['_'] = $constructorData['predicate'];
        }
        $predicate = $object['_'];

        $constructorData = $this->constructors->find_by_predicate($predicate, $layer);
        if ($constructorData === false) {
            \danog\MadelineProto\Logger::log([$object], \danog\MadelineProto\Logger::FATAL_ERROR);

            throw new Exception('Could not extract type "'.$predicate.'"');
        }

        if ($bare = ($type['type'] != '' && $type['type'][0] === '%')) {
            $type['type'] = substr($type['type'], 1);
        }

        if ($predicate === $type['type'] && !$auto) {
            $bare = true;
        }

        $concat = '';
        if ($constructorData['predicate'] === 'messageEntityMentionName') {
            $constructorData = $this->constructors->find_by_predicate('inputMessageEntityMentionName');
        }
        if (!$bare) {
            $concat .= $constructorData['id'];
        }

        return $concat.$this->serialize_params($constructorData, $object, '', $layer);
    }

    public function serialize_method($method, $arguments)
    {
        $tl = $this->methods->find_by_method($method);
        if ($tl === false) {
            throw new Exception('Could not find method: '.$method);
        }

        return $tl['id'].$this->serialize_params($tl, $arguments, $method);
    }

    public function serialize_params($tl, $arguments, $ctx, $layer = -1)
    {
        $serialized = '';

        $arguments = $this->botAPI_to_MTProto($arguments);
        $flags = 0;
        foreach ($tl['params'] as $cur_flag) {
            if (isset($cur_flag['pow'])) {
                switch ($cur_flag['type']) {
                    case 'true':
                    case 'false':
                        $flags = (isset($arguments[$cur_flag['name']]) && $arguments[$cur_flag['name']]) ? ($flags | $cur_flag['pow']) : ($flags & ~$cur_flag['pow']);
                        unset($arguments[$cur_flag['name']]);
                        break;
                    case 'Bool':
                        $arguments[$cur_flag['name']] = (isset($arguments[$cur_flag['name']]) && $arguments[$cur_flag['name']]) && (($flags & $cur_flag['pow']) != 0);
                        if (($flags & $cur_flag['pow']) === 0) {
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
                if (isset($current_argument['pow']) && (in_array($current_argument['type'], ['true', 'false']) || ($flags & $current_argument['pow']) === 0)) {
                    //\danog\MadelineProto\Logger::log(['Skipping '.$current_argument['name'].' of type '.$current_argument['type']);
                    continue;
                }
                if ($current_argument['name'] === 'random_bytes') {
                    $serialized .= $this->serialize_object(['type' => 'bytes'], $this->random(15 + (4 * (random_int(0, PHP_INT_MAX) % 3))), 'random_bytes');
                    continue;
                }
                if ($current_argument['name'] === 'data' && isset($arguments['message'])) {
                    $serialized .= $this->serialize_object($current_argument, $this->encrypt_secret_message($arguments['peer']['chat_id'], $arguments['message']), 'data');
                    continue;
                }
                if ($current_argument['name'] === 'random_id') {
                    switch ($current_argument['type']) {
                        case 'long':
                            $serialized .= $this->random(8);
                            continue 2;
                        case 'int':
                            $serialized .= $this->random(4);
                            continue 2;
                        case 'Vector t':
                            if (isset($arguments['id'])) {
                                $serialized .= $this->constructors->find_by_predicate('vector')['id'];
                                $serialized .= $this->pack_unsigned_int(count($arguments['id']));
                                $serialized .= $this->random(8 * count($arguments['id']));
                                continue 2;
                            }
                    }
                }

                throw new Exception('Missing required parameter', $current_argument['name']);
            }
            if (!is_array($arguments[$current_argument['name']]) && $current_argument['type'] === 'InputEncryptedChat') {
                if (!isset($this->secret_chats[$arguments[$current_argument['name']]])) {
                    throw new \danog\MadelineProto\Exception('This secret peer is not present in the internal peer database');
                }
                $arguments[$current_argument['name']] = $this->secret_chats[$arguments[$current_argument['name']]]['InputEncryptedChat'];
            }
            if ($current_argument['type'] === 'DataJSON') {
                $arguments[$current_argument['name']] = ['_' => 'dataJSON', 'data' => json_encode($arguments[$current_argument['name']])];
            }

            //\danog\MadelineProto\Logger::log(['Serializing '.$current_argument['name'].' of type '.$current_argument['type']);
            $serialized .= $this->serialize_object($current_argument, $arguments[$current_argument['name']], $current_argument['name'], $layer);
        }

        return $serialized;
    }

    public function get_length($stream, $type = ['type' => ''])
    {
        if (is_string($stream)) {
            $res = fopen('php://memory', 'rw+b');
            fwrite($res, $stream);
            fseek($res, 0);
            $stream = $res;
        } elseif (!is_resource($stream)) {
            throw new Exception('An invalid stream handle was provided.');
        }
        $this->deserialize($stream, $type);

        return ftell($stream);
    }

    /**
     * :type stream: io.BytesIO object.
     */
    public function deserialize($stream, $type = ['type' => ''])
    {
        if (is_string($stream)) {
            $res = fopen('php://memory', 'rw+b');
            fwrite($res, $stream);
            fseek($res, 0);
            $stream = $res;
        } elseif (!is_resource($stream)) {
            throw new Exception('An invalid stream handle was provided.');
        }

        switch ($type['type']) {
            case 'Bool':
                return $this->deserialize_bool(stream_get_contents($stream, 4));
            case 'int':
                return $this->unpack_signed_int(stream_get_contents($stream, 4));
            case '#':
                return unpack('V', stream_get_contents($stream, 4))[1];
            case 'long':
                if (isset($type['idstrlong'])) {
                    return stream_get_contents($stream, 8);
                }

                return \danog\MadelineProto\Logger::$bigint || isset($type['strlong']) ? stream_get_contents($stream, 8) : $this->unpack_signed_long(stream_get_contents($stream, 8));
            case 'double':
                return $this->unpack_double(stream_get_contents($stream, 8));
            case 'int128':
                return stream_get_contents($stream, 16);
            case 'int256':
                return stream_get_contents($stream, 32);
            case 'int512':
                return stream_get_contents($stream, 64);
            case 'string':
            case 'bytes':
                $l = ord(stream_get_contents($stream, 1));
                if ($l > 254) {
                    throw new Exception('Length is too big');
                }
                if ($l === 254) {
                    $long_len = unpack('V', stream_get_contents($stream, 3).chr(0))[1];
                    $x = stream_get_contents($stream, $long_len);
                    $resto = $this->posmod(-$long_len, 4);
                    if ($resto > 0) {
                        stream_get_contents($stream, $resto);
                    }
                } else {
                    $x = stream_get_contents($stream, $l);
                    $resto = $this->posmod(-($l + 1), 4);
                    if ($resto > 0) {
                        stream_get_contents($stream, $resto);
                    }
                }
                if (!is_string($x)) {
                    throw new Exception("deserialize: generated value isn't a string");
                }

                return $type['type'] === 'bytes' ? (new Types\Bytes($x)) : $x;
            case 'Vector t':
                $id = stream_get_contents($stream, 4);
                $constructorData = $this->constructors->find_by_id($id);
                if ($constructorData === false) {
                    throw new Exception('Could not extract type: '.$type['type'].' with id '.bin2hex(strrev($id)));
                }
                switch ($constructorData['predicate']) {
                    case 'gzip_packed':
                        return $this->deserialize(gzdecode($this->deserialize($stream, ['type' => 'bytes', 'datacenter' => $type['datacenter']])), ['type' => '', 'datacenter' => $type['datacenter']]);
                    case 'Vector t':
                    case 'vector':
                        break;
                    default:
                        throw new Exception('Invalid vector constructor: '.$constructorData['predicate']);
                }
            case 'vector':
                $count = unpack('V', stream_get_contents($stream, 4))[1];
                $result = [];
                $type['type'] = $type['subtype'];
                for ($i = 0; $i < $count; $i++) {
                    $result[] = $this->deserialize($stream, $type);
                }

                return $result;
        }
        if ($type['type'] != '' && $type['type'][0] === '%') {
            $checkType = substr($type['type'], 1);
            $constructorData = $this->constructors->find_by_type($checkType);
            if ($constructorData === false) {
                throw new Exception('Constructor not found for type: '.$checkType);
            }
        } else {
            $constructorData = $this->constructors->find_by_predicate($type['type']);
            if ($constructorData === false) {
                $id = stream_get_contents($stream, 4);
                $constructorData = $this->constructors->find_by_id($id);
                if ($constructorData === false) {
                    throw new Exception('Could not extract type: '.$type['type'].' with id '.bin2hex(strrev($id)));
                }
            }
        }
        if ($constructorData['predicate'] === 'gzip_packed') {
            if (!isset($type['subtype'])) {
                $type['subtype'] = '';
            }

            return $this->deserialize(gzdecode($this->deserialize($stream, ['type' => 'bytes'])), ['type' => '', 'datacenter' => $type['datacenter'], 'subtype' => $type['subtype']]);
        }
        if ($constructorData['type'] === 'Vector t') {
            $constructorData['datacenter'] = $type['datacenter'];
            $constructorData['subtype'] = isset($type['subtype']) ? $type['subtype'] : '';
            $constructorData['type'] = 'vector';

            return $this->deserialize($stream, $constructorData);
        }
        if ($constructorData['type'] === 'Bool') {
            return $constructorData['predicate'] === 'boolTrue';
        }
        $x = ['_' => $constructorData['predicate']];
        foreach ($constructorData['params'] as $arg) {
            if (isset($arg['pow'])) {
                switch ($arg['type']) {
                    case 'true':
                    case 'false':
                        $x[$arg['name']] = ($x['flags'] & $arg['pow']) !== 0;
                        continue 2;
                    case 'Bool':
                        if (($x['flags'] & $arg['pow']) === 0) {
                            $x[$arg['name']] = false;
                            continue 2;
                        }
                    default:
                        if (($x['flags'] & $arg['pow']) === 0) {
                            continue 2;
                        }
                }
            }
            if (in_array($arg['name'], ['msg_ids', 'msg_id', 'bad_msg_id', 'req_msg_id', 'answer_msg_id', 'first_msg_id'])) {
                $arg['idstrlong'] = true;
            }
            if (in_array($arg['name'], ['key_fingerprint', 'server_salt', 'new_server_salt', 'server_public_key_fingerprints', 'ping_id', 'exchange_id'])) {
                $arg['strlong'] = true;
            }
            if (in_array($arg['name'], ['peer_tag', 'file_token', 'cdn_key', 'cdn_iv'])) {
                $arg['type'] = 'string';
            }

            if ($x['_'] === 'rpc_result' && $arg['name'] === 'result' && isset($this->datacenter->sockets[$type['datacenter']]->new_outgoing[$x['req_msg_id']]['type']) && stripos($this->datacenter->sockets[$type['datacenter']]->new_outgoing[$x['req_msg_id']]['type'], '<') !== false) {
                $arg['subtype'] = preg_replace(['|Vector[<]|', '|[>]|'], '', $this->datacenter->sockets[$type['datacenter']]->new_outgoing[$x['req_msg_id']]['type']);
            }
            if (isset($type['datacenter'])) {
                $arg['datacenter'] = $type['datacenter'];
            }
            $x[$arg['name']] = $this->deserialize($stream, $arg);

            if ($arg['name'] === 'random_bytes') {
                if (strlen($x[$arg['name']]) < 15) {
                    throw new \danog\MadelineProto\SecurityException('random_bytes is too small!');
                } else {
                    unset($x[$arg['name']]);
                }
            }
        }
        if (isset($x['flags'])) { // I don't think we need this anymore
            unset($x['flags']);
        }
        if ($x['_'] === 'dataJSON') {
            return json_decode($x['data'], true);
        }

        if ($x['_'] === 'message' && isset($x['reply_markup']['rows'])) {
            foreach ($x['reply_markup']['rows'] as $key => $row) {
                foreach ($row['buttons'] as $bkey => $button) {
                    $x['reply_markup']['rows'][$key]['buttons'][$bkey] = new Types\Button($this, $x, $button);
                }
            }
        }

        return $x;
    }
}
