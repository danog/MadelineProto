<?php
namespace danog\MadelineProto;

class Exception extends \Exception {
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