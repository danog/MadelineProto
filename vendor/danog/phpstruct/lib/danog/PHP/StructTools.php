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
// Working class
class StructTools
{
    /**
     * Constructor.
     *
     * Sets modifiers and gets endianness
     */
    public function __construct($format = null)
    {
        $this->BIG_ENDIAN = (pack('L', 1) === pack('N', 1));
        $this->IS64BIT = (PHP_INT_SIZE === 8);
        $this->FORMATS = [
            // Integer formats
            'b' => 'c', // should be 1 (8 bit)
            'B' => 'C', // should be 1 (8 bit)
            'h' => 's', // should be 2 (16 bit)
            'H' => 'S', // should be 2 (16 bit)
            'i' => 'l', // should be 4 (32 bit)
            'I' => 'L', // should be 4 (32 bit)
            'l' => 'l', // should be 4 (32 bit)
            'L' => 'L', // should be 4 (32 bit)
            'q' => 'q', // should be 8 (64 bit)
            'Q' => 'Q', // should be 8 (64 bit)

            // Floating point numbers
            'f' => 'f', // should be 4 (32 bit)
            'd' => 'd', // should be 8 (64 bit)

            // String formats (note that these format chars require a single parameter, regardless of the format char count)
            's' => 'a',
            'p' => 'p', // “Pascal string”, meaning a short variable-length string stored in a fixed number of bytes, given by the count. The first byte stored is the length of the string, or 255, whichever is smaller. The bytes of the string follow. If the string passed in to pack() is too long (longer than the count minus 1), only the leading count-1 bytes of the string are stored. If the string is shorter than count-1, it is padded with null bytes so that exactly count bytes in all are used. Note that for unpack(), the 'p' format character consumes count bytes, but that the string returned can never contain more than 255 characters.

            // char formats
            'c' => 'a',

            // Boolean formats
            '?' => '?',

            // Null
            'x' => 'x',
        ];
        $this->NATIVE_FORMATS = array_replace($this->FORMATS, [
            // These formats need to be modified after/before encoding/decoding.
            'P' => $this->IS64BIT ? 'Q' : 'L', // integer or long integer, depending on the size needed to hold a pointer when it has been cast to an integer type. A NULL pointer will always be returned as the Python integer 0. When packing pointer-sized values, Python integer or long integer objects may be used. For example, the Alpha and Merced processors use 64-bit pointer values, meaning a Python long integer will be used to hold the pointer; other platforms use 32-bit pointers and will use a Python integer.
            'n' => $this->IS64BIT ? 'q' : 'l',
            'N' => $this->IS64BIT ? 'Q' : 'L',
            'l' => $this->IS64BIT ? 'q' : 'l',
            'L' => $this->IS64BIT ? 'Q' : 'L',
        ]);
        $this->SIZE = [
            // Integer formats
            'b' => 1,
            'B' => 1,
            'h' => 2,
            'H' => 2,
            'i' => 4,
            'I' => 4,
            'l' => 4,
            'L' => 4,
            'q' => 8,
            'Q' => 8,

            // Floating point numbers
            'f' => 4,
            'd' => 8,

            // String formats (note that these format chars require a single parameter, regardless of the format char count)
            's' => 1,
            'p' => 1,

            // char formats
            'c' => 1,

            // Boolean formats
            '?' => 1,

            // Null
            'x' => 1,
        ];
        // Native length table for the @ modifier
        $this->NATIVE_SIZE = [
            // Integer formats
            'b' => strlen(pack($this->NATIVE_FORMATS['b'], 11)),
            'B' => strlen(pack($this->NATIVE_FORMATS['B'], 11)),
            'h' => strlen(pack($this->NATIVE_FORMATS['h'], -700)),
            'H' => strlen(pack($this->NATIVE_FORMATS['H'], 700)),
            'i' => strlen(pack($this->NATIVE_FORMATS['i'], 1)),
            'I' => strlen(pack($this->NATIVE_FORMATS['I'], 1)),
            'l' => strlen(pack($this->NATIVE_FORMATS['l'], -700)),
            'L' => strlen(pack($this->NATIVE_FORMATS['L'], 700)),
            'q' => $this->IS64BIT ? strlen(pack($this->NATIVE_FORMATS['q'], 700)) : 8,
            'Q' => $this->IS64BIT ? strlen(pack($this->NATIVE_FORMATS['Q'], 700)) : 8,

            // Floating point formats
            'f' => strlen(pack($this->NATIVE_FORMATS['f'], 2.0)),
            'd' => strlen(pack($this->NATIVE_FORMATS['d'], 2.0)),

            // String formats (note that these format chars require a single parameter, regardless of the format char count)
            'p' => 1,
            's' => strlen(pack($this->NATIVE_FORMATS['s'], 'c')),

            // Char formats
            'c' => strlen(pack($this->NATIVE_FORMATS['c'], 'a')),

            // Boolean formats
            '?' => strlen(pack('c', false)),

            // Null
            'x' => strlen(pack($this->NATIVE_FORMATS['x'])),

            // Automatical length formats
            'P' => strlen(pack($this->NATIVE_FORMATS['P'], 2323)),
            'n' => strlen(pack($this->NATIVE_FORMATS['n'], 1)),
            'N' => strlen(pack($this->NATIVE_FORMATS['N'], 1)),
        ];
        $this->TYPE = [
            // Integer formats
            'b' => 'int',
            'B' => 'int',
            'h' => 'int',
            'H' => 'int',
            'i' => 'int',
            'I' => 'int',
            'l' => 'int',
            'L' => 'int',
            'q' => 'int',
            'Q' => 'int',

             // Floating point formats
            'f' => 'float',
            'd' => 'float',

            // String formats
            'p' => 'string',
            's' => 'string',

            // Char formats
            'c' => 'string',

            // Boolean formats
            '?' => 'bool',

            // Null
            'x' => 'unset',
        ];
        $this->NATIVE_TYPE = array_merge([
            // These formats need to be modified after/before encoding/decoding.
            'P' => $this->IS64BIT ? $this->TYPE['Q'] : $this->TYPE['L'], // integer or long integer, depending on the size needed to hold a pointer when it has been cast to an integer type. A NULL pointer will always be returned as the Python integer 0. When packing pointer-sized values, Python integer or long integer objects may be used. For example, the Alpha and Merced processors use 64-bit pointer values, meaning a Python long integer will be used to hold the pointer; other platforms use 32-bit pointers and will use a Python integer.
            'n' => $this->IS64BIT ? $this->TYPE['q'] : $this->TYPE['l'],
            'N' => $this->IS64BIT ? $this->TYPE['Q'] : $this->TYPE['L'],
        ], $this->TYPE);
        $this->ENDIANNESS_TABLE = [
            'h' => true,
            'H' => true,
            'i' => true,
            'I' => true,
            'l' => true,
            'L' => true,
            'q' => true,
            'Q' => true,

            'n' => true,
            'N' => true,
            'P' => true,
            'f' => $this->BIG_ENDIAN,
            'd' => $this->BIG_ENDIAN,

        ];
        $this->LITTLE_ENDIAN_TABLE = array_merge($this->ENDIANNESS_TABLE, array_fill_keys(['x', 'c', 'b', 'B', '?', 's', 'p'], false));
        $this->BIG_ENDIAN_TABLE = array_merge($this->ENDIANNESS_TABLE, array_fill_keys(['x', 'c', 'b', 'B', '?', 's', 'p'], true));
        $this->NATIVE_ENDIAN_TABLE = $this->BIG_ENDIAN ? $this->BIG_ENDIAN_TABLE : $this->LITTLE_ENDIAN_TABLE;

        $this->MODIFIERS = [
            '<' => [
                'BIG_ENDIAN' => false,
                'ENDIANNESS' => $this->LITTLE_ENDIAN_TABLE,
                'SIZE'       => $this->SIZE,
                'FORMATS'    => $this->FORMATS,
                'TYPE'       => $this->TYPE,
                'MODIFIER'   => '<',
            ],
            '>' => [
                'BIG_ENDIAN' => true,
                'ENDIANNESS' => $this->BIG_ENDIAN_TABLE,
                'SIZE'       => $this->SIZE,
                'FORMATS'    => $this->FORMATS,
                'TYPE'       => $this->TYPE,
                'MODIFIER'   => '>',
            ],
            '!' => [
                'BIG_ENDIAN' => true,
                'ENDIANNESS' => $this->BIG_ENDIAN_TABLE,
                'SIZE'       => $this->SIZE,
                'FORMATS'    => $this->FORMATS,
                'TYPE'       => $this->TYPE,
                'MODIFIER'   => '!',
            ],
            '=' => [
                'BIG_ENDIAN' => $this->BIG_ENDIAN,
                'ENDIANNESS' => $this->NATIVE_ENDIAN_TABLE,
                'SIZE'       => $this->SIZE,
                'FORMATS'    => $this->FORMATS,
                'TYPE'       => $this->TYPE,
                'MODIFIER'   => '=',
            ],
            '@' => [
                'BIG_ENDIAN' => $this->BIG_ENDIAN,
                'ENDIANNESS' => $this->NATIVE_ENDIAN_TABLE,
                'SIZE'       => $this->NATIVE_SIZE,
                'FORMATS'    => $this->NATIVE_FORMATS,
                'TYPE'       => $this->NATIVE_TYPE,
                'MODIFIER'   => '@',
            ],
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
        throw new StructException($errstr.' on line '.$errline, $errno);
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
        $format = $this->padformat($format);
        $result = null; // Data to return
        $size = $this->calcsize($format);
        $packcommand = $this->parseformat($format, $this->array_each_strlen($data)); // Get pack parameters
        set_error_handler([$this, 'ExceptionErrorHandler']);
        foreach ($packcommand as $key => $command) {
            try {
                switch ($command['modifiers']['TYPE']) {
                    case 'int':
                        if (!is_int($data[$command['datakey']]) && !is_float($data[$command['datakey']])) {
                            $data[$command['datakey']] = (int) $data[$command['datakey']];
                        }
                        break;
                    case 'float':
                        if (!is_float($data[$command['datakey']])) {
                            $data[$command['datakey']] = (float) $data[$command['datakey']];
                        }
                        break;
                    case 'string':
                        if (!is_string($data[$command['datakey']])) {
                            $data[$command['datakey']] = (string) $data[$command['datakey']];
                        }
                        break;
                    case 'bool':
                        if (!is_bool($data[$command['datakey']])) {
                            $data[$command['datakey']] = (bool) $data[$command['datakey']];
                        }
                        break;
                    default:
                        break;
                }
                switch ($command['phpformat']) {
                    case 'x':
                        $curresult = pack($command['phpformat'].$command['count']); // Pack current char
                        break;
                    case 'p':
                        $curresult = pack('c', ($command['count'] - 1 > 255) ? 255 : $command['count'] - 1).pack('a'.($command['count'] - 1), $data[$command['datakey']]);
                        break;

                    case 'q':
                    case 'Q':
                    case 'l':
                    case 'L':
                    case 'i':
                    case 'I':
                    case 's':
                    case 'S':
                    case 'c':
                    case 'C':
                        $curresult = $this->num_pack($data[$command['datakey']], $command['modifiers']['SIZE'], ctype_upper($command['phpformat']));

                        break;

                    case '?':
                        $curresult = pack('c'.$command['count'], $data[$command['datakey']]); // Pack current char
                        break;
                    default:
                        $curresult = pack($command['phpformat'].$command['count'], $data[$command['datakey']]); // Pack current char
                        break;
                }
                if (strlen($curresult) != $command['modifiers']['SIZE'] * $command['count']) {
                    trigger_error('Size of packed data '.strlen($curresult)." isn't equal to expected size ".$command['modifiers']['SIZE'] * $command['count'].'.');
                }
            } catch (StructException $e) {
                throw new StructException('An error occurred while packing '.$data[$command['datakey']].' at format key '.$command['format'].' ('.$e->getMessage().').');
            }
            if ($command['modifiers']['FORMAT_ENDIANNESS'] != $command['modifiers']['BIG_ENDIAN']) {
                $curresult = strrev($curresult);
            } // Reverse if wrong endianness
            /*
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
            }*/
            $result .= $curresult;
        }
        restore_error_handler();
        if (strlen($result) != $size) {
            throw new StructException('Length of generated data ('.strlen($result).') is different from length calculated using format string ('.$size.').');
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
        $format = $this->padformat($format);
        $size = $this->calcsize($format);
        if (strlen($data) != $size) {
            throw new StructException('Length of given data ('.strlen($data).') is different from length calculated using format string ('.$size.').');
        }
        $dataarray = $this->data2array($format, $data);

        if ($this->array_total_strlen($dataarray) != $size) {
            throw new StructException('Length of array ('.$this->array_total_strlen($dataarray).') is different from length calculated using format string ('.$size.').');
        }
        $result = []; // Data to return
        $packcommand = $this->parseformat($format, $this->array_each_strlen($dataarray), true); // Get unpack parameters
        set_error_handler([$this, 'ExceptionErrorHandler']);
        $arraycount = 0;
        foreach ($packcommand as $key => $command) {
            if ($command['modifiers']['FORMAT_ENDIANNESS'] != $command['modifiers']['BIG_ENDIAN']) {
                $dataarray[$command['datakey']] = strrev($dataarray[$command['datakey']]);
            } // Reverse if wrong endianness
            try {
                switch ($command['phpformat']) {
                    case 'p':
                        $templength = unpack('c', $dataarray[$command['datakey']])[1];
                        $result[$arraycount] = implode('', unpack('a'.$templength, substr($dataarray[$command['datakey']], 1)));
                        break;
                    case '?':
                        if (implode('', unpack('c'.$command['count'], $dataarray[$command['datakey']])) == 0) {
                            $result[$arraycount] = false;
                        } else {
                            $result[$arraycount] = true;
                        }
                        break;

                    case 'q':
                    case 'Q':
                    case 'l':
                    case 'L':
                    case 'i':
                    case 'I':
                    case 's':
                    case 'S':
                    case 'c':
                    case 'C':
                        $result[$arraycount] = $this->num_unpack($dataarray[$command['datakey']], $command['modifiers']['SIZE'], ctype_upper($command['phpformat']));
                        break;

                    default:
                        $result[$arraycount] = implode('', unpack($command['phpformat'].$command['count'], $dataarray[$command['datakey']])); // Unpack current char
                        break;
                }
            } catch (StructException $e) {
                throw new StructException('An error occurred while unpacking data at offset '.$command['datakey'].' ('.$e->getMessage().').');
            }
            switch ($command['modifiers']['TYPE']) {
                case 'int':
                    if (!is_int($result[$arraycount]) && !is_float($result[$arraycount])) {
                        $result[$arraycount] = (int) $result[$arraycount];
                    }
/*                    if (is_float($result[$arraycount]) && $result[$arraycount] < PHP_INT_MAX) {
                        $result[$arraycount] = (int) $result[$arraycount];
                    }*/
                    break;
                case 'float':
                    if (!is_float($result[$arraycount])) {
                        $result[$arraycount] = (float) $result[$arraycount];
                    }

                    break;
                case 'unset':
                    unset($result[$arraycount]);
                    $arraycount--;
                    break;
                case 'string':
                    if (!is_string($result[$arraycount])) {
                        $result[$arraycount] = (string) $result[$arraycount];
                    }
                    break;
                case 'bool':
                    if (!is_bool($result[$arraycount])) {
                        $result[$arraycount] = (bool) $result[$arraycount];
                    }
                    break;
                default:
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
     *
     * @return int with size of the struct.
     */
    public function calcsize($format)
    {
        $format = $this->padformat($format);
        $size = 0;
        $modifier = $this->MODIFIERS['@'];
        $count = null;
        if ($format == '') {
            return 0;
        }
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
        $totallength = 0;
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
                    $result[$formatcharcount]['modifiers'] = [
                        'BIG_ENDIAN'        => $modifier['BIG_ENDIAN'],
                        'FORMAT_ENDIANNESS' => $modifier['ENDIANNESS'][$currentformatchar],
                        'SIZE'              => $modifier['SIZE'][$currentformatchar],
                        'TYPE'              => $modifier['TYPE'][$currentformatchar],
                    ];
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

    /**
     * binadd.
     *
     *  Convert a binary string to an array based on the given format string
     *
     * @param	$data   Data to convert to array
     *
     * @return array
     **/
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
     * pdaformt.
     *
     *  Pad format string with x format where needed
     *
     * @param	$format Format string to pad
     *
     * @return Padded format string
     **/
    public function padformat($format)
    {
        $modifier = $this->MODIFIERS['@'];
        $result = null; // Result gormat string
        $count = null;
        $totallength = 0;
        foreach (str_split($format) as $offset => $currentformatchar) { // Current format char
            if (isset($this->MODIFIERS[$currentformatchar])) { // If current format char is a modifier
                $modifier = $this->MODIFIERS[$currentformatchar]; // Set the modifiers for the current format char
                $result = $currentformatchar;
            } elseif (is_numeric($currentformatchar) && ((int) $currentformatchar >= 0 || (int) $currentformatchar <= 9)) {
                $count .= (int) $currentformatchar; // Set the count for the current format char
            } elseif (isset($modifier['FORMATS'][$currentformatchar])) {
                if (!isset($count) || $count == null) {
                    $count = 1; // Set count to 1 by default.
                }
                $count = (int) $count;
                if ($currentformatchar == 's' || $currentformatchar == 'p') {
                    $result .= $count.$currentformatchar;
                    $totallength += $modifier['SIZE'][$currentformatchar] * $count;
                } else {
                    for ($x = 0; $x < $count; $x++) {
                        if ($modifier['MODIFIER'] == '@') {
                            $result .= str_pad('', $this->posmod(-$totallength, $modifier['SIZE'][$currentformatchar]), 'x');

                            $totallength += $this->posmod(-$totallength, $modifier['SIZE'][$currentformatchar]) + $modifier['SIZE'][$currentformatchar];
                        }
                        $result .= $currentformatchar;
                    }
                }
                $count = null;
            } else {
                throw new StructException('Unkown format or modifier supplied at offset '.$offset.' ('.$currentformatchar.').');
            }
        }

        return $result;
    }

    /**
     * decbin.
     *
     *  Returns a string containing a big endian binary representation of the given decimal number.
     *  Also pads binary number with zeros to match given $length
     *
     * @param	$number		Decimal number to turn into binary
     * @param	$length		Length to reach through padding
     *
     * @return binary version of the given number
     **/
    public function decbin($number, $length)
    {
        $concat = '';
        if ($number < 0) {
            $negative = true;
            $number = -$number;
        } else {
            $negative = false;
        }
        do {
            $concat = $this->posmod($number, 2).$concat;
            $number = intval($number / 2);
        } while ($number > 0);
        $concat = str_pad($concat, $length, '0', STR_PAD_LEFT);
        if ($negative) {
            $concat = $this->binadd($this->stringnot($concat), '1');
        }
        if (strlen($concat) == $length + 1 && $concat == str_pad('1', $length + 1, '0', STR_PAD_RIGHT)) {
            $concat = str_pad('', $length, '0');
        }
        if (strlen($concat) > $length) {
            trigger_error('Converted binary number '.$concat.' is too long ('.strlen($concat).' > '.$length.').');
        }

        return $concat;
    }

    /**
     * bindec.
     *
     *  Converts a binary number to a decimal.
     *
     * @param	$binary		binary number to turn into decimal
     * @param	$unsigned	if set to false will interpret binary string as signed
     *
     * @return deciaml version of the given number
     **/
    public function bindec($binary, $unsigned = true)
    {
        $decimal = 0;
        if (!$unsigned && $binary[0] == '1') {
            $binary = $this->binadd($this->stringnot($binary), '1');
            $negative = true;
        } else {
            $negative = false;
        }

        foreach (str_split(strrev($binary)) as $n => $bit) {
            $decimal += (pow(2, $n) * $bit);
        }

        return $negative ? -$decimal : $decimal;
    }

    /**
     * stringnot.
     *
     *  Performs a NOT operation on every bit in the string (by bit I mean a literal 1 or 0)
     *
     * @param	$string String to xor
     *
     * @return xored string
     **/
    public function stringnot($string)
    {
        foreach (str_split($string) as $key => $char) {
            if ($char == '0') {
                $string[$key] = '1';
            } elseif ($char == '1') {
                $string[$key] = '0';
            } else {
                trigger_error('Found unrecognized char '.$char.' at string offset '.$key);
            }
        }

        return $string;
    }

    /**
     * binadd.
     *
     *  Add two binary numbers
     *
     * @param	$x First binary number
     * @param	$y Second binary number
     *
     * @return sum of the two numbers
     **/
    public function binadd($x, $y)
    {
        $maxlen = max(strlen($x), strlen($y));

        //Normalize lengths
        $x = str_pad($x, $maxlen, '0', STR_PAD_LEFT);
        $y = str_pad($y, $maxlen, '0', STR_PAD_LEFT);

        $result = '';
        $carry = 0;
        foreach (array_reverse($this->range(0, $maxlen)) as $i) {
            $r = $carry;
            $r += ($x[$i] == '1') ? 1 : 0;
            $r += ($y[$i] == '1') ? 1 : 0;

            // r can be 0,1,2,3 (carry + x[i] + y[i])
            // and among these, for r==1 and r==3 you will have result bit = 1
            // for r==2 and r==3 you will have carry = 1

            $result = (($r % 2 == 1) ? '1' : '0').$result;
            $carry = ($r < 2) ? 0 : 1;
        }
        if ($carry != 0) {
            $result = '1'.$result;
        }

        return str_pad($result, $maxlen, '0', STR_PAD_LEFT);
    }

    /**
     * num_pack.
     *
     * Convert a number to a byte string.
     * If optional blocksize is given and greater than zero, pad the front of the
     * byte string with binary zeros so that the length is the
     * blocksize.
     *
     * @param	$n		    Number to pack
     * @param	$blocksize	Block size
     * @param   $unsigned Boolean that determines whether to work in signed or unsigned mode
     *
     * @return Byte string
     **/
    public function num_pack($n, $blocksize, $unsigned)
    {
        $bitnumber = $blocksize * 8;
        if ($unsigned) {
            $min = 0;
            switch ($bitnumber) {
                case '8':
                    $max = 255;
                    break;
                case '16':
                    $max = 65535;
                    break;
                case '32':
                    $max = 4294967295;
                    break;
                case '64':
                    $max = 18446744073709551615;
                    break;
                default:
                    $max = pow(2, $bitnumber) - 1;
                    break;
            }
        } else {
            switch ($bitnumber) {
                case '8':
                    $min = -127;
                    $max = 127;
                    break;
                case '16':
                    $min = -32767;
                    $max = 32767;
                    break;
                case '32':
                    $min = -2147483647;
                    $max = 2147483647;
                    break;
                case '64':
                    $min = -9223372036854775807;
                    $max = 9223372036854775807;
                    break;
                default:
                    $max = pow(2, $bitnumber - 1) - 1;
                    $min = -pow(2, $bitnumber - 1);
                    break;
            }
        }
        if ($n < $min || $n > $max) {
            trigger_error('Number is not within required range ('.$min.' <= number <= '.$max.').');
        }
        $bits = $this->decbin($n, $bitnumber);
        $s = null;
        foreach (explode('2', wordwrap($bits, 8, '2', true)) as $byte) {
            $s .= chr($this->bindec($byte));
        }
        $break = true;
        foreach ($this->range(strlen($s)) as $i) {
            if ($s[$i] != pack('@')[0]) {
                $break = false;
                break;
            }
        }
        if ($break) {
            $s = pack('@1');
            $i = 0;
        }
        $s = substr($s, $i);
        if (strlen($s) < $blocksize) {
            $s = pack('@'.($blocksize - strlen($s))).$s;
        } elseif (strlen($s) > $blocksize) {
            trigger_error('Generated data length ('.strlen($s).') is bigger than required length ('.$blocksize.').');
        }

        return $s;
    }

    /**
     * num_unpack.
     *
     * Convert a byte string to an integer.
     * This is (essentially) the inverse of num_pack().
     *
     * @param	$s		    Data to unpack
     * @param	$blocksize	Block size
     * @param   $unsigned Boolean that determines whether to work in signed or unsigned mode
     *
     * @return float or int with the unpack value
     **/
    public function num_unpack($s, $blocksize, $unsigned)
    {
        $length = strlen($s);
        $bitnumber = $blocksize * 8;
        if ($length != $blocksize) {
            trigger_error('Given data length ('.$length.') is different from the required length ('.$blocksize.').');
        }
        $bits = '';
        foreach (str_split($s) as $i) {
            $bits .= $this->decbin(ord($i), 8);
        }

        return $this->bindec($bits, $unsigned);
    }

    /**
     * posmod(numeric,numeric) : numeric
     * Works just like the % (modulus) operator, only returns always a postive number.
     */
    public function posmod($a, $b)
    {
        $resto = $a % $b;
        if ($resto < 0) {
            $resto += abs($b);
        }

        return $resto;
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
     * range.
     *
     * Generate range
     *
     * @param	$start		Beginning of the range (or stop if no other params are specified)
     * @param	$stop		End of the range
     * @param	$step		Step to use in range
     *
     * @return array with the range
     **/
    public function range($start, $stop = null, $step = 1)
    {
        if ($stop === null) {
            $stop = $start;
            $start = 0;
        }
        if ($stop <= $start && $step < 0) {
            $arr = range($stop, $start, -$step);
            array_pop($arr);

            return array_reverse($arr, false);
        }
        if ($step > 1 && $step > ($stop - $start)) {
            $arr = [$start];
        } else {
            $arr = range($start, $stop, $step);
            array_pop($arr);
        }

        return $arr;
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
