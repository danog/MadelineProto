<?php

set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).DIRECTORY_SEPARATOR.'libpy2php');
require_once 'libpy2php.php';

require_once 'os.php';
namespace danog\MadelineProto;
class TlConstructor
{
    public function __construct($json_dict)
    {
        $this->id = (int) $json_dict['id'];
        $this->type = $json_dict['type'];
        $this->predicate = $json_dict['predicate'];
        $this->params = [];
        foreach ($json_dict['params'] as $param) {
            if (($param['type'] == 'Vector<long>')) {
                $param['type'] = 'Vector t';
                $param['subtype'] = 'long';
            } elseif (($param['type'] == 'vector<%Message>')) {
                $param['type'] = 'vector';
                $param['subtype'] = 'message';
            } elseif (($param['type'] == 'vector<future_salt>')) {
                $param['type'] = 'vector';
                $param['subtype'] = 'future_salt';
            } else {
                $param['subtype'] = null;
            }
            $this->params[] = $param;
        }
    }
}
class TlMethod
{
    public function __construct($json_dict)
    {
        $this->id = (int) $json_dict['id'];
        $this->type = $json_dict['type'];
        $this->method = $json_dict['method'];
        $this->params = $json_dict['params'];
    }
}
class TLObject extends ArrayObject
{
    public function __construct($tl_elem)
    {
        parent::__construct();
        $this->name = $tl_elem->predicate;
    }
}
class TL
{
    public function __construct($filename)
    {
        $TL_dict = json_decode(file_get_contents($filename), true);
        $this->constructors = $TL_dict['constructors'];
        $this->constructor_id = [];
        $this->constructor_type = [];
        foreach ($this->constructors as $elem) {
            $z = new TlConstructor($elem);
            $this->constructor_id[$z->id] = $z;
            $this->constructor_type[$z->predicate] = $z;
        }
        $this->methods = $TL_dict['methods'];
        $this->method_id = [];
        $this->method_name = [];
        foreach ($this->methods as $elem) {
            $z = new TlMethod($elem);
            $this->method_id[$z->id] = $z;
            $this->method_name[$z->method] = $z;
        }
        $this->struct = new \danog\PHP\StructClass();
    }

    public function serialize_obj($type_, $kwargs)
    {
        $bytes_io = '';
        if (isset($this->constructor_type[$type_])) {
            $tl_constructor = $this->constructor_type[$type_];
        } else {
            throw new Exception(sprintf('Could not extract type: %s', $type_));
        }
        $bytes_io .= $this->struct->pack('<i', $tl_constructor->id);
        foreach ($tl_constructor->params as $arg) {
            $bytes_io .= $this->serialize_param($arg['type'], $kwargs[$arg['name']]);
        }

        return $bytes_io;
    }

    public function serialize_method($type_, $kwargs)
    {
        $bytes_io = '';
        if (isset($this->method_name[$type_])) {
            $tl_method = $this->method_name[$type_];
        } else {
            throw new Exception(sprintf('Could not extract type: %s', $type_));
        }
        $bytes_io .= $this->struct->pack('<i', $tl_method->id);
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
                return $this->struct->pack('<i', $value);
                break;
            case 'long':
                if (!is_numeric($value)) {
                    throw new Exception("serialize_param: given value isn't numeric");
                }
                return $this->struct->pack('<q', $value);
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
                    $concat .= $this->struct->pack('<b', $l);
                    $concat .= $value;
                    $concat .= pack('@'.posmod((-$l - 1), 4));
                } else {
                    $concat .= string2bin('\xfe');
                    $concat .= substr($this->struct->pack('<i', $l), 0, 3);
                    $concat .= $value;
                    $concat .= pack('@'.posmod(-$l, 4));
                }
                return $concat;
                break;
            default:
                break;
        }
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
                $x = $this->struct->unpack('<i', fread($bytes_io, 4)) [0];
                break;
            case '#':
                $x = $this->struct->unpack('<I', fread($bytes_io, 4)) [0];
                break;
            case 'long':
                $x = $this->struct->unpack('<q', fread($bytes_io, 8)) [0];
                break;
            case 'double':
                $x = $this->struct->unpack('<d', fread($bytes_io, 8)) [0];
                break;
            case 'int128':
                $x = fread($bytes_io, 16);
                break;
            case 'int256':
                $x = fread($bytes_io, 32);
                break;
            case 'string':
            case 'bytes':
                $l = $this->struct->unpack('<B', fread($bytes_io, 1)) [0];
                if ($l > 254) {
                    throw new Exception('Length is too big');
                }
                if ($l == 254) {
                    $long_len = $this->struct->unpack('<I', fread($bytes_io, 3).string2bin('\x00')) [0];
                    $x = fread($bytes_io, $long_len);
                    fread($bytes_io, posmod(-$long_len, 4));
                } else {
                    $x = fread($bytes_io, $l);
                    fread($bytes_io, posmod(-($l + 1), 4));
                }
                if (!is_string($x)) {
                    throw new Exception("deserialize: generated value isn't a string");
                }
                break;
            case 'vector':
                if ($subtype == null) {
                    throw new Exception("deserialize: subtype isn't null");
                }
                $count = $this->struct->unpack('<l', fread($bytes_io, 4)) [0];
                $x = [];
                foreach (pyjslib_range($count) as $i) {
                    $x[] = $this->deserialize($bytes_io, $subtype);
                }
                break;
            default:
                if (isset($this->constructor_type[$type_])) {
                    $tl_elem = $this->constructor_type[$type_];
                } else {
                    $Idata = fread($bytes_io, 4);
                    $i = $this->struct->unpack('<i', $Idata) [0];
                    if (isset($this->constructor_id[$i])) {
                        $tl_elem = $this->constructor_id[$i];
                    } else {
                        throw new Exception(sprintf('Could not extract type: %s', $type_));
                    }
                }

                $base_boxed_types = ['Vector t', 'Int', 'Long', 'Double', 'String', 'Int128', 'Int256'];
                if (in_array($tl_elem->type, $base_boxed_types)) {
                    $x = $this->deserialize($bytes_io, $tl_elem->predicate, $subtype);
                } else {
                    $x = new TLObject($tl_elem);
                    foreach ($tl_elem->params as $arg) {
                        $x[$arg['name']] = $this->deserialize($bytes_io, $arg['type'], $arg['subtype']);
                    }
                }
                break;
        }

        return $x;
    }
}
