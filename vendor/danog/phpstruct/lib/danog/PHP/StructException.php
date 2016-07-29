<?php

namespace danog\PHP;

/**
 * PHPStruct
 * PHP implementation of Python's struct module.
 * This library was created to help me develop a [client for the mtproto protocol](https://github.com/danog/MadelineProto).
 * The functions and the formats are exactly the ones used in python's struct (https://docs.python.org/3/library/struct.html)
 * For now custom byte size may not work properly on certain machines for the f and d formats.
 *
 * @author		Daniil Gentili <daniil@daniil.it>
 * @license		MIT license
 */
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
