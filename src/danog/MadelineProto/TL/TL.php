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

    public function construct_tl($files)
    {
        \danog\MadelineProto\Logger::log(['Loading TL schemes...'], \danog\MadelineProto\Logger::VERBOSE);
        $this->constructors = new \danog\MadelineProto\TL\TLConstructor();
        $this->methods = new \danog\MadelineProto\TL\TLMethod();
        $this->td_constructors = new \danog\MadelineProto\TL\TLConstructor();
        $this->td_methods = new \danog\MadelineProto\TL\TLMethod();
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
                    if (preg_match('|^===\d*===$|', $line)) {
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
                    $TL_dict[$type][$key]['id'] = \danog\PHP\Struct::unpack('<i', \danog\PHP\Struct::pack('<I', hexdec($id)))[0];
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
            foreach ($this->td_constructors->id as $key => $id) {
                $name = $this->td_constructors->predicate[$key];
                if ($this->constructors->find_by_id($id) === false) {
                    unset($this->td_descriptions['constructors'][$name]);
                } else {
                    foreach ($this->td_descriptions['constructors'][$name]['params'] as &$param) {
                        $param = str_replace('nullable', 'optional', $param);
                    }
                }
            }
            foreach ($this->td_methods->id as $key => $id) {
                $name = $this->td_methods->method[$key];
                if ($this->methods->find_by_id($id) === false) {
                    unset($this->td_descriptions['methods'][$name]);
                } else {
                    foreach ($this->td_descriptions['methods'][$name]['params'] as &$param) {
                        $param = str_replace('nullable', 'optional', $param);
                    }
                }
            }
        }
    }

    public function get_method_namespaces()
    {
        return array_unique(array_values($this->methods->method_namespace));
    }

    public function serialize_bool($bool)
    {
        return \danog\PHP\Struct::pack('<i', $this->constructors->find_by_predicate('bool'.($bool ? 'True' : 'False'))['id']);
    }

    public function deserialize_bool($data)
    {
        $id = \danog\PHP\Struct::unpack('<i', $data)[0];
        $tl_elem = $this->constructors->find_by_id($id);
        if ($tl_elem === false) {
            throw new Exception('Could not extract boolean');
        }

        return $tl_elem['predicate'] === 'boolTrue';
    }

    public function serialize_object($type, $object, $layer = -1)
    {
        switch ($type['type']) {
            case 'int':
                if (!is_numeric($object)) {
                    throw new Exception('given value ('.$object.") isn't numeric");
                }

                return \danog\PHP\Struct::pack('<i', $object);
            case '#':
                if (!is_numeric($object)) {
                    throw new Exception('given value ('.$object.") isn't numeric");
                }

                return \danog\PHP\Struct::pack('<I', $object);
            case 'long':
                if (is_object($object)) {
                    return str_pad(strrev($object->toBytes()), 8, chr(0));
                }

                if (is_string($object)) {
                    if (strlen($object) !== 8) {
                        throw new Exception('Given value is not 8 bytes long');
                    }

                    return $object;
                }

                if (!is_numeric($object)) {
                    throw new Exception('given value ('.$object.") isn't numeric");
                }

                return \danog\PHP\Struct::pack('<q', $object);
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
                return \danog\PHP\Struct::pack('<d', $object);
            case 'string':
                $object = pack('C*', ...unpack('C*', $object));
            case 'bytes':
                $l = strlen($object);
                $concat = '';
                if ($l <= 253) {
                    $concat .= chr($l);
                    $concat .= $object;
                    $concat .= pack('@'.$this->posmod((-$l - 1), 4));
                } else {
                    $concat .= chr(254);
                    $concat .= substr(\danog\PHP\Struct::pack('<i', $l), 0, 3);
                    $concat .= $object;
                    $concat .= pack('@'.$this->posmod(-$l, 4));
                }

                return $concat;
            case 'Bool':
                return $this->serialize_bool((bool) $object);
            case 'true':
                return;
            case '!X':
                return $object;
            case 'Vector t':
                $concat = \danog\PHP\Struct::pack('<i', $this->constructors->find_by_predicate('vector')['id']);
                $concat .= \danog\PHP\Struct::pack('<i', count($object));
                foreach ($object as $current_object) {
                    $concat .= $this->serialize_object(['type' => $type['subtype']], $current_object);
                }

                return $concat;

        }
        $auto = false;

        if ((!is_array($object) || (isset($object['_']) && $this->constructors->find_by_predicate($object['_'])['type'] !== $type['type'])) && in_array($type['type'], ['User', 'InputUser', 'Chat', 'InputChannel', 'Peer', 'InputPeer'])) {
            $object = $this->get_info($object)[$type['type']];
        }
        if (!isset($object['_'])) {
            $constructorData = $this->constructors->find_by_predicate($type['type'], $layer);
            if ($constructorData === false) {
                throw new Exception('Predicate was not set!');
            }
            $auto = true;
            $object['_'] = $constructorData['predicate'];
        }
        $predicate = $object['_'];

        $constructorData = $this->constructors->find_by_predicate($predicate, $layer);
        if ($constructorData === false) {
            \danog\MadelineProto\Logger::log([$object], \danog\MadelineProto\Logger::FATAL_ERROR);
            throw new Exception('Could not extract type');
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
            $concat .= \danog\PHP\Struct::pack('<i', $constructorData['id']);
        }

        return $concat.$this->serialize_params($constructorData, $object, $layer);
    }

    public function serialize_method($method, $arguments)
    {
        $tl = $this->methods->find_by_method($method);
        if ($tl === false) {
            throw new Exception('Could not find method: '.$method);
        }

        return \danog\PHP\Struct::pack('<i', $tl['id']).$this->serialize_params($tl, $arguments);
    }

    public function serialize_params($tl, $arguments, $layer = -1)
    {
        $serialized = '';

        $arguments = $this->botAPI_to_MTProto($arguments);
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
                if ($current_argument['flag'] && (in_array($current_argument['type'], ['true', 'false']) || ($flags & $current_argument['pow']) === 0)) {
                    //\danog\MadelineProto\Logger::log(['Skipping '.$current_argument['name'].' of type '.$current_argument['type']);
                    continue;
                }
                if ($current_argument['name'] === 'random_bytes') {
                    $serialized .= $this->serialize_object(['type' => 'bytes'], $this->random(15 + (4 * (random_int(0, PHP_INT_MAX) % 3))));
                    continue;
                }
                if ($current_argument['name'] === 'data' && isset($arguments['message'])) {
                    $serialized .= $this->serialize_object($current_argument, $this->encrypt_secret_message($arguments['peer']['chat_id'], $arguments['message']));
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
                                $serialized .= \danog\PHP\Struct::pack('<i', $this->constructors->find_by_predicate('vector')['id']);
                                $serialized .= \danog\PHP\Struct::pack('<i', count($arguments['id']));
                                $serialized .= $this->random(8 * count($arguments['id']));
                                continue 2;
                            }
                    }
                }
                throw new Exception('Missing required parameter ('.$current_argument['name'].')');
            }
            if (!is_array($arguments[$current_argument['name']]) && $current_argument['type'] === 'InputEncryptedChat') {
                $arguments[$current_argument['name']] = $this->secret_chats[$arguments[$current_argument['name']]]['InputEncryptedChat'];
            }
            //\danog\MadelineProto\Logger::log(['Serializing '.$current_argument['name'].' of type '.$current_argument['type']);
            $serialized .= $this->serialize_object($current_argument, $arguments[$current_argument['name']], $layer);
        }

        return $serialized;
    }

    public function get_length($bytes_io, $type = ['type' => ''])
    {
        $this->deserialize($bytes_io, $type);

        return $bytes_io->pos;
    }

    /**
     * :type bytes_io: io.BytesIO object.
     */
    public function deserialize($bytes_io, $type = ['type' => ''])
    {
        if (is_string($bytes_io)) {
            $bytes_io = new \danog\MadelineProto\Stream($bytes_io);
        } elseif (!is_object($bytes_io)) {
            throw new Exception('An invalid bytes_io handle was provided.');
        }
        //\danog\MadelineProto\Logger::log(['Deserializing '.$type['type'].' at byte '.$bytes_io->pos);
        switch ($type['type']) {
            case 'Bool':
                return $this->deserialize_bool($bytes_io->read(4));
            case 'int':
                return \danog\PHP\Struct::unpack('<i', $bytes_io->read(4))[0];
            case '#':
                return \danog\PHP\Struct::unpack('<I', $bytes_io->read(4))[0];
            case 'long':
                return $this->bigint || isset($type['strlong']) ? $bytes_io->read(8) : \danog\PHP\Struct::unpack('<q', $bytes_io->read(8))[0];
            case 'double':
                return \danog\PHP\Struct::unpack('<d', $bytes_io->read(8))[0];
            case 'int128':
                return $bytes_io->read(16);
            case 'int256':
                return $bytes_io->read(32);
            case 'int512':
                return $bytes_io->read(64);
            case 'string':
            case 'bytes':
                $l = \danog\PHP\Struct::unpack('<B', $bytes_io->read(1))[0];
                if ($l > 254) {
                    throw new Exception('Length is too big');
                }
                if ($l === 254) {
                    $long_len = \danog\PHP\Struct::unpack('<I', $bytes_io->read(3).chr(0))[0];
                    $x = $bytes_io->read($long_len);
                    $resto = $this->posmod(-$long_len, 4);
                    if ($resto > 0) {
                        $bytes_io->pos += $resto;
                    }
                } else {
                    $x = $bytes_io->read($l);
                    $resto = $this->posmod(-($l + 1), 4);
                    if ($resto > 0) {
                        $bytes_io->pos += $resto;
                    }
                }
                if (!is_string($x)) {
                    throw new Exception("deserialize: generated value isn't a string");
                }

                return $x;
            case 'true':
                return true;
            case 'Vector t':
                $id = \danog\PHP\Struct::unpack('<i', $bytes_io->read(4))[0];
                $constructorData = $this->constructors->find_by_id($id);
                if ($constructorData === false) {
                    throw new Exception('Could not extract type: '.$type['type'].' with id '.$id);
                }
                switch ($constructorData['predicate']) {
                    case 'gzip_packed':
                        return $this->deserialize(gzdecode($this->deserialize($bytes_io, ['type' => 'bytes'])));
                    case 'Vector t':
                    case 'vector':
                        break;
                    default:
                        throw new Exception('Invalid vector constructor: '.$constructorData['predicate']);
                }
            case 'vector':
                $count = \danog\PHP\Struct::unpack('<i', $bytes_io->read(4))[0];
                $result = [];
                $type['type'] = $type['subtype'];
                for ($i = 0; $i < $count; $i++) {
                    $result[] = $this->deserialize($bytes_io, $type);
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
                $id = \danog\PHP\Struct::unpack('<i', $bytes_io->read(4))[0];
                $constructorData = $this->constructors->find_by_id($id);
                if ($constructorData === false) {
                    throw new Exception('Could not extract type: '.$type['type'].' with id '.$id);
                }
            }
        }
        if ($constructorData['predicate'] === 'gzip_packed') {
            return $this->deserialize(gzdecode($this->deserialize($bytes_io, ['type' => 'bytes'])));
        }
        $x = ['_' => $constructorData['predicate']];
        foreach ($constructorData['params'] as $arg) {
            if ($arg['flag']) {
                switch ($arg['type']) {
                    case 'true':
                    case 'false':
                        $x[$arg['name']] = ($x['flags'] & $arg['pow']) !== 0;
                        continue 2;
                        break;
                    case 'Bool':
                        if (($x['flags'] & $arg['pow']) === 0) {
                            $x[$arg['name']] = false;
                            continue 2;
                        }
                    default:
                        if (($x['flags'] & $arg['pow']) === 0) {
                            //$x[$arg['name']] = $default;
                            continue 2;
                        }
                        break;
                }
            }
            if (in_array($arg['name'], ['msg_ids', 'msg_id', 'bad_msg_id', 'req_msg_id', 'answer_msg_id', 'first_msg_id', 'key_fingerprint', 'server_salt', 'new_server_salt', 'server_public_key_fingerprints', 'ping_id', 'exchange_id'])) {
                $arg['strlong'] = true;
            }
            $x[$arg['name']] = $this->deserialize($bytes_io, $arg);
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

        return $x;
    }
}
