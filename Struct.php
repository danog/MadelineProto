<?php

namespace danog\PHP;

/**
 * PHPStruct
 * PHP implementation of Python's struct module.
 * This library was created to help me develop a [client for the mtproto protocol](https://github.com/danog/MadelineProto).
 * The functions and the formats are exactly the ones used in python's struct (https://docs.python.org/3/library/struct.html)
 * For now custom byte size may not work properly on certain machines for the i, I, f and d formats.
 *
 * @author		Daniil Gentili <daniil@daniil.it>
 * @license		MIT license
 */
// Main class
class Struct
{
    /**
     * Constructor.
     *
     * Sets modifiers and gets endianness
     */
    public function __construct()
    {
        $this->BIG_ENDIAN = (pack('L', 1) === pack('N', 1));
        $this->IS64BIT = (PHP_INT_SIZE === 8);
        $this->FORMATS = [
            // These formats need to be modified after/before encoding/decoding.
            'p' => 'p', // “Pascal string”, meaning a short variable-length string stored in a fixed number of bytes, given by the count. The first byte stored is the length of the string, or 255, whichever is smaller. The bytes of the string follow. If the string passed in to pack() is too long (longer than the count minus 1), only the leading count-1 bytes of the string are stored. If the string is shorter than count-1, it is padded with null bytes so that exactly count bytes in all are used. Note that for unpack(), the 'p' format character consumes count bytes, but that the string returned can never contain more than 255 characters.

            // These formats have automatical byte size, this must be fixed.
            'i' => 'i', // should be 4 (32 bit)
            'I' => 'I', // should be 4 (32 bit)
            'f' => 'f', // should be 4 (32 bit)
            'd' => 'd', // should be 8 (64 bit)

            // These formats should work exactly as in python's struct (same byte size, etc).
            'c' => 'a',
            '?' => 'c',
            'x' => 'x',
            'b' => 'c',
            'B' => 'C',
            'h' => 's',
            'H' => 'S',
            'l' => 'l',
            'L' => 'L',
            's' => 'a',
        ];
        $this->NATIVE_FORMATS = array_merge([
            // These formats need to be modified after/before encoding/decoding.
            'P' => $this->IS64BIT ? 'Q' : 'L', // integer or long integer, depending on the size needed to hold a pointer when it has been cast to an integer type. A NULL pointer will always be returned as the Python integer 0. When packing pointer-sized values, Python integer or long integer objects may be used. For example, the Alpha and Merced processors use 64-bit pointer values, meaning a Python long integer will be used to hold the pointer; other platforms use 32-bit pointers and will use a Python integer.
            'n' => $this->IS64BIT ? 'q' : 'l',
            'N' => $this->IS64BIT ? 'Q' : 'L',
        ], $this->FORMATS);
        $this->SIZE = [
            'p' => 1,
            'i' => 4,
            'I' => 4,
            'f' => 4,
            'd' => 8,
            'c' => 1,
            '?' => 1,
            'x' => 1,
            'b' => 1,
            'B' => 1,
            'h' => 2,
            'H' => 2,
            'l' => 4,
            'L' => 4,
            's' => 1,
        ];
        $this->NATIVE_SIZE = [
            'p' => 1,
            'P' => strlen(pack($this->NATIVE_FORMATS['P'], 2323)),
            'i' => strlen(pack($this->NATIVE_FORMATS['i'], 1)),
            'I' => strlen(pack($this->NATIVE_FORMATS['I'], 1)),
            'f' => strlen(pack($this->NATIVE_FORMATS['f'], 2.0)),
            'd' => strlen(pack($this->NATIVE_FORMATS['d'], 2.0)),
            'c' => strlen(pack($this->NATIVE_FORMATS['c'], 'a')),
            '?' => strlen(pack($this->NATIVE_FORMATS['?'], false)),
            'x' => strlen(pack($this->NATIVE_FORMATS['x'])),
            'b' => strlen(pack($this->NATIVE_FORMATS['b'], 'c')),
            'B' => strlen(pack($this->NATIVE_FORMATS['B'], 'c')),
            'h' => strlen(pack($this->NATIVE_FORMATS['h'], -700)),
            'H' => strlen(pack($this->NATIVE_FORMATS['H'], 700)),
            'l' => strlen(pack($this->NATIVE_FORMATS['l'], -70000000)),
            'L' => strlen(pack($this->NATIVE_FORMATS['L'], 70000000)),
            's' => strlen(pack($this->NATIVE_FORMATS['s'], 'c')),
            'n' => strlen(pack($this->NATIVE_FORMATS['n'], 1)),
            'N' => strlen(pack($this->NATIVE_FORMATS['N'], 1)),
        ];
        $this->TYPE = [
            'p' => 'string',
            'i' => 'int',
            'I' => 'int',
            'f' => 'float',
            'd' => 'float',
            'c' => 'string',
            '?' => 'bool',
            'x' => 'unset',
            'b' => 'int',
            'B' => 'int',
            'h' => 'int',
            'H' => 'int',
            'l' => 'int',
            'L' => 'int',
            's' => 'string',
        ];
        $this->NATIVE_TYPE = array_merge([
            'P' => 'int', // integer or long integer, depending on the size needed to hold a pointer when it has been cast to an integer type. A NULL pointer will always be returned as the Python integer 0. When packing pointer-sized values, Python integer or long integer objects may be used. For example, the Alpha and Merced processors use 64-bit pointer values, meaning a Python long integer will be used to hold the pointer; other platforms use 32-bit pointers and will use a Python integer.
            'n' => 'int',
            'N' => 'int',
        ], $this->TYPE);
        if ($this->IS64BIT) {
            $this->FORMATS['q'] = 'q';
            $this->FORMATS['Q'] = 'Q';
            $this->NATIVE_FORMATS['q'] = 'q';
            $this->NATIVE_FORMATS['Q'] = 'Q';
            $this->SIZE['q'] = 8;
            $this->SIZE['Q'] = 8;
            $this->NATIVE_SIZE['q'] = strlen(pack($this->NATIVE_FORMATS['q'], -70000000));
            $this->NATIVE_SIZE['Q'] = strlen(pack($this->NATIVE_FORMATS['Q'], 70000000));
            $this->TYPE['q'] = 'int';
            $this->TYPE['Q'] = 'int';
            $this->NATIVE_TYPE['q'] = 'int';
            $this->NATIVE_TYPE['Q'] = 'int';
        }
        $this->MODIFIERS = [
            '<' => ['BIG_ENDIAN' => false, 'SIZE' => $this->SIZE, 'FORMATS' => $this->FORMATS, 'TYPE' => $this->TYPE],
            '>' => ['BIG_ENDIAN' => true, 'SIZE' => $this->SIZE, 'FORMATS' => $this->FORMATS, 'TYPE' => $this->TYPE],
            '!' => ['BIG_ENDIAN' => true, 'SIZE' => $this->SIZE, 'FORMATS' => $this->FORMATS, 'TYPE' => $this->TYPE],
            '=' => ['BIG_ENDIAN' => $this->BIG_ENDIAN, 'SIZE' => $this->SIZE, 'FORMATS' => $this->FORMATS, 'TYPE' => $this->TYPE],
            '@' => ['BIG_ENDIAN' => $this->BIG_ENDIAN, 'SIZE' => $this->NATIVE_SIZE, 'FORMATS' => $this->NATIVE_FORMATS, 'TYPE' => $this->NATIVE_TYPE],
        ];
    }

    /**
     * ExceptionErrorHandler.
     *
     * Error handler for pack and unpack
     */
    public function ExceptionErrorHandler($errno = 0, $errstr = null, $errfile = null, $errline = null)
    {
        // If error is suppressed with @, don't throw an exception
        if (error_reporting() === 0) {
            return true; // return true to continue through the others error handlers
        }
        throw new StructException($errstr, $errno);
    }

    /**
     * pack.
     *
     * Packs data into bytes
     *
     * @param	$format		Format string
     * @param	...$data	Parameters to encode
     *
     * @return Encoded data
     */
    public function pack($format, ...$data)
    {
        if (!(isset($this) && get_class($this) == __CLASS__)) {
            $struct = new \danog\PHP\Struct();

            return $struct->pack($format, ...$data);
        }
        $result = null; // Data to return
        $packcommand = $this->parseformat($format, $this->array_each_strlen($data)); // Get pack parameters
        set_error_handler([$this, 'ExceptionErrorHandler']);
        foreach ($packcommand as $key => $command) {
            try {
                switch ($command['modifiers']['TYPE']) {
                    case 'int':
                        if (!is_int($data[$command['datakey']])) {
                            throw new StructException('Required argument is not an integer.');
                        }
                        break;
                    case 'float':
                        if (!is_float($data[$command['datakey']])) {
                            throw new StructException('Required argument is not a float.');
                        }

                        break;
                    case 'string':
                        if (!is_string($data[$command['datakey']])) {
                            throw new StructException('Required argument is not a string.');
                        }
                        break;
                    case 'bool':
                        //if(!is_bool($data[$command["datakey"]])) throw new StructException("Required argument is not a bool."); // Ignore boolean type
                        break;
                    default:
                        break;
                }
                switch ($command['format']) {
                    case 'x':
                        $curresult = pack($command['phpformat'].$command['count']); // Pack current char
                        break;
                    case 'p':
                        $tempstring = pack('a'.($command['count'] - 1), $data[$command['datakey']]);
                        $curresult = pack('v', ($command['count'] - 1 > 255) ? 255 : $command['count'] - 1)[0].$tempstring;
                        break;
                    case '?':
                        $curresult = pack($command['phpformat'], (bool) $data[$command['datakey']]);
                        break;
                    default:
                        $curresult = pack($command['phpformat'].$command['count'], $data[$command['datakey']]); // Pack current char
                        break;
                }
            } catch (StructException $e) {
                throw new StructException('An error occurred while packing data at offset '.$key.' ('.$e->getMessage().').');
            }
            if ($this->BIG_ENDIAN != $command['modifiers']['BIG_ENDIAN'] && !in_array($command['format'], ['x', 'c', 'b', 'B', '?', 's', 'p'])) {
                $curresult = strrev($curresult);
            } // Reverse if wrong endianness
            if (strlen($curresult) > $command['modifiers']['SIZE'] * $command['count']) {
                if ($command['modifiers']['BIG_ENDIAN']) {
                    $curresult = strrev($curresult);
                }
                $remains = array_slice(str_split($curresult), $command['modifiers']['SIZE'], strlen($curresult) - $command['modifiers']['SIZE']);
                foreach ($remains as $rem) {
                    if ($rem != '') {
                        throw new StructException('Error while trimming result at offset '.$key.' (format char '.$command['format']."): data to trim isn't empty.");
                    }
                }
                $curresult = implode('', substr($curresult, 0, $command['modifiers']['SIZE']));
                if ($command['modifiers']['BIG_ENDIAN']) {
                    $curresult = strrev($curresult);
                }
            }
            $result .= $curresult;
        }
        restore_error_handler();
        if (strlen($result) != $this->calcsize($format)) {
            throw new StructException('Length of generated data ('.strlen($result).') is different from length calculated using format string ('.$this->calcsize($format).').');
        }

        return $result;
    }

    /**
     * unpack.
     *
     * Unpacks data into an array
     *
     * @param	$format	Format string
     * @param	$data	Data to decode
     *
     * @return Decoded data
     */
    public function unpack($format, $data)
    {
        if (!(isset($this) && get_class($this) == __CLASS__)) {
            $struct = new \danog\PHP\Struct();

            return $struct->unpack($format, $data);
        }
        if (strlen($data) != $this->calcsize($format)) {
            throw new StructException('Length of given data ('.strlen($data).') is different from length calculated using format string ('.$this->calcsize($format).').');
        }
        $dataarray = $this->data2array($format, $data);

        if ($this->array_total_strlen($dataarray) != $this->calcsize($format)) {
            throw new StructException('Length of given data array ('.$this->array_total_strlen($dataarray).') is different from length calculated using format string ('.$this->calcsize($format).').');
        }
        $result = []; // Data to return
        $packcommand = $this->parseformat($format, $this->array_each_strlen($dataarray), true); // Get unpack parameters
        set_error_handler([$this, 'ExceptionErrorHandler']);
        $arraycount = 0;
        foreach ($packcommand as $key => $command) {
            if (isset($command['modifiers']['BIG_ENDIAN']) && $this->BIG_ENDIAN != $command['modifiers']['BIG_ENDIAN'] && !in_array($command['format'], ['x', 'c', 'b', 'B', '?', 's', 'p'])) {
                $dataarray[$command['datakey']] = strrev($dataarray[$command['datakey']]);
            } // Reverse if wrong endianness
            try {
                switch ($command['format']) {
                    case 'p':
                        $templength = unpack('s', $dataarray[$command['datakey']][0].pack('x'))[1];
                        $result[$arraycount] = implode('', unpack('a'.$templength, substr($dataarray[$command['datakey']], 1)));
                        break;
                    case '?':
                        if (implode('', unpack($command['phpformat'].$command['count'], $dataarray[$command['datakey']])) == 0) {
                            $result[$arraycount] = false;
                        } else {
                            $result[$arraycount] = true;
                        }
                        break;
                    default:
                        $result[$arraycount] = implode('', unpack($command['phpformat'].$command['count'], $dataarray[$command['datakey']])); // Unpack current char
                        break;
                }
            } catch (StructException $e) {
                throw new StructException('An error occurred while unpacking data at offset '.$key.' ('.$e->getMessage().').');
            }
            switch ($command['modifiers']['TYPE']) {
                case 'int':
                    $result[$arraycount] = (int) $result[$arraycount];
                    break;
                case 'float':
                    $result[$arraycount] = (float) $result[$arraycount];
                    break;
                case 'string':
                    $result[$arraycount] = (string) $result[$arraycount];
                    break;
                case 'bool':
                    $result[$arraycount] = (bool) $result[$arraycount];
                    break;
                case 'unset':
                    unset($result[$arraycount]);
                    $arraycount--;
                    break;
                default:
                    $result[$arraycount] = (string) $result[$arraycount];
                    break;
            }
            $arraycount++;
        }
        restore_error_handler();

        return $result;
    }

    /**
     * calcsize.
     *
     * Return the size of the struct (and hence of the string) corresponding to the given format.

     *
     * @param	$format	Format string
     *
     * @return int with size of the struct.
     */
    public function calcsize($format)
    {
        if (!(isset($this) && get_class($this) == __CLASS__)) {
            $struct = new \danog\PHP\Struct();

            return $struct->calcsize($format);
        }
        $size = 0;
        $modifier = $this->MODIFIERS['@'];
        $count = null;
        foreach (str_split($format) as $offset => $currentformatchar) {
            if (isset($this->MODIFIERS[$currentformatchar])) {
                $modifier = $this->MODIFIERS[$currentformatchar]; // Set the modifiers for the current format char
            } elseif (is_numeric($currentformatchar) && ((int) $currentformatchar > 0 || (int) $currentformatchar <= 9)) {
                $count .= $currentformatchar; // Set the count for the current format char
            } elseif (isset($modifier['SIZE'][$currentformatchar])) {
                if (!isset($count) || $count == null) {
                    $count = 1; // Set count to 1 if something's wrong.
                }
                $size += $count * $modifier['SIZE'][$currentformatchar];
                $count = null;
            } else {
                throw new StructException('Unkown format or modifier supplied ('.$currentformatchar.' at offset '.$offset.').');
            }
        }

        return $size;
    }

    /**
     * parseformat.
     *
     * Parses format string.
     *
     * @param	$format		Format string to parse
     * @param	$arraycount Array containing the number of chars contained in each element of the array to pack
     *
     * @throws StructException if format string is too long or there aren't enough parameters or if an unkown format or modifier is supplied.
     *
     * @return array with format and modifiers for each object to encode/decode
     */
    public function parseformat($format, $arraycount, $unpack = false)
    {
        $datarraycount = 0; // Current element of the array to pack/unpack
        $formatcharcount = 0; // Current element to pack/unpack (sometimes there isn't a correspondant element in the array)
        $modifier = $this->MODIFIERS['@'];
        $result = []; // Array with the results
        $count = null;
        $loopcount = 0;
        foreach (str_split($format) as $offset => $currentformatchar) { // Current format char
            if (!isset($result[$formatcharcount]) || !is_array($result[$formatcharcount])) {
                $result[$formatcharcount] = []; // Create array for current element
            }
            if (isset($this->MODIFIERS[$currentformatchar])) { // If current format char is a modifier
                $modifier = $this->MODIFIERS[$currentformatchar]; // Set the modifiers for the current format char
            } elseif (is_numeric($currentformatchar) && ((int) $currentformatchar >= 0 || (int) $currentformatchar <= 9)) {
                $count .= (int) $currentformatchar; // Set the count for the current format char
            } elseif (isset($modifier['FORMATS'][$currentformatchar])) {
                if (!isset($count) || $count == null) {
                    $count = 1; // Set count to 1 by default.
                }
                $count = (int) $count;
                if ($currentformatchar == 's' || $currentformatchar == 'p') {
                    $loopcount = 1;
                } else {
                    $loopcount = $count;
                    $count = 1;
                }
                for ($x = 0; $x < $loopcount; $x++) {
                    $result[$formatcharcount]['format'] = $currentformatchar; // Set format
                    $result[$formatcharcount]['phpformat'] = $modifier['FORMATS'][$currentformatchar]; // Set format
                    $result[$formatcharcount]['count'] = $count;
                    $result[$formatcharcount]['modifiers'] = ['BIG_ENDIAN' => $modifier['BIG_ENDIAN'], 'SIZE' => $modifier['SIZE'][$currentformatchar], 'TYPE' => $modifier['TYPE'][$currentformatchar]];
                    if ($unpack) {
                        if ($arraycount[$datarraycount] != $result[$formatcharcount]['count'] * $result[$formatcharcount]['modifiers']['SIZE']) {
                            throw new StructException('Length for format string '.$result[$formatcharcount]['format'].' at offset '.$offset.' ('.$result[$formatcharcount]['count'] * $result[$formatcharcount]['modifiers']['SIZE'].") isn't equal to the length of associated parameter (".$arraycount[$datarraycount].').');
                        }
                        $result[$formatcharcount]['datakey'] = $datarraycount;
                        $datarraycount++;
                    } else {
                        if ($currentformatchar != 'x') {
                            $result[$formatcharcount]['datakey'] = $datarraycount;
                            $datarraycount++;
                        }
                    }
                    if ($datarraycount > count($arraycount)) {
                        throw new StructException('Format string too long or not enough parameters at offset '.$offset.' ('.$currentformatchar.').');
                    }
                    $formatcharcount++; // Increase element count
                }
                $count = null;
            } else {
                throw new StructException('Unkown format or modifier supplied at offset '.$offset.' ('.$currentformatchar.').');
            }
        }

        return $result;
    }

    public function data2array($format, $data)
    {
        $dataarray = [];
        $dataarraykey = 0;
        $datakey = 0;
        $count = null;
        $loopcount = 0;

        $modifier = $this->MODIFIERS['@'];
        foreach (str_split($format) as $offset => $currentformatchar) {
            if (isset($this->MODIFIERS[$currentformatchar])) {
                $modifier = $this->MODIFIERS[$currentformatchar]; // Set the modifiers for the current format char
            } elseif (is_numeric($currentformatchar) && ((int) $currentformatchar > 0 || (int) $currentformatchar <= 9)) {
                $count .= $currentformatchar; // Set the count for the current format char
            } elseif (isset($modifier['SIZE'][$currentformatchar])) {
                if (!isset($count) || $count == null) {
                    $count = 1; // Set count to 1 if something's wrong.
                }
                $count = (int) $count;

                if ($currentformatchar == 's' || $currentformatchar == 'p') {
                    $loopcount = 1;
                } else {
                    $loopcount = $count;
                    $count = 1;
                }
                for ($x = 0; $x < $loopcount; $x++) {
                    if (!isset($dataarray[$dataarraykey])) {
                        $dataarray[$dataarraykey] = null;
                    }
                    for ($a = 0; $a < $count * $modifier['SIZE'][$currentformatchar]; $a++) {
                        $dataarray[$dataarraykey] .= $data[$datakey];
                        $datakey++;
                    }
                    $dataarraykey++;
                }
                $count = null;
            } else {
                throw new StructException('Unkown format or modifier supplied ('.$currentformatchar.' at offset '.$offset.').');
            }
        }

        return $dataarray;
    }

    /**
     * array_each_strlen.
     *
     * Get length of each array element.
     *
     * @param	$array		Array to parse
     *
     * @return array with lengths
     **/
    public function array_each_strlen($array)
    {
        foreach ($array as &$value) {
            $value = $this->count($value);
        }

        return $array;
    }

    /**
     * array_total_strlen.
     *
     * Get total length of every array element.
     *
     * @param	$array		Array to parse
     *
     * @return int with the total length
     **/
    public function array_total_strlen($array)
    {
        $count = 0;
        foreach ($array as $value) {
            $count += $this->count($value);
        }

        return $count;
    }

    /**
     * count.
     *
     * Get the length of a string or of an array
     *
     * @param	$input		String or array to parse
     *
     * @return int with the length
     **/
    public function count($input)
    {
        if (is_array($input)) {
            return count($input);
        }

        return strlen($input);
    }
}

/* Just an exception class */
class StructException extends \Exception
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        // some code
        if (isset($GLOBALS['doingphptests']) && $GLOBALS['doingphptests']) {
            var_dump($message);
        }

        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }
}
