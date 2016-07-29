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
// Struct class (for dynamic access)
class StructClass
{
    public $struct = null; // Will store an instance of the StructTools class
    public $format = null; // Will contain the format
    public $size = null; // Will contain the size

    /**
     * Constructor.
     *
     * Stores instance of the StructTools class and optional format/size
     */
    public function __construct($format = null)
    {
        $this->struct = new \danog\PHP\StructTools();
        if ($format !== null) {
            $this->format = $format;
            $this->size = $this->struct->calcsize($format);
        }
    }

    /**
     * pack.
     *
     * Packs data into bytes
     *
     * @param	...$data	Parameters to encode (may contain format string)
     *
     * @return Encoded data
     */
    public function pack(...$data)
    {
        if ($this->format === null) {
            $format = array_shift($data);
        } else {
            $format = $this->format;
        }

        return $this->struct->pack($format, ...$data);
    }

    /**
     * unpack.
     *
     * Unpacks data into an array
     *
     * @param	$format_maybe_data	Format string (may be data if class is istantiated with format string)
     * @param	$data	            Data to decode
     *
     * @return Decoded data
     */
    public function unpack($format_maybe_data, $data = null)
    {
        if ($this->format === null) {
            $format = $format_maybe_data;
        } else {
            $format = $this->format;
            $data = $format_maybe_data;
        }

        return $this->struct->unpack($format, $data);
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
    public function calcsize($format = null)
    {
        return ($this->format !== null && $this->size !== null) ? $this->size : $this->struct->calcsize($format);
    }
}
