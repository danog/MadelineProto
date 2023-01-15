<?php

namespace danog\MadelineProto;

use danog\MadelineProto\Logger;


function logger(...$messages)
{
    if(isset($messages[0]) && is_array($messages[0]))
    {
        $messages = $messages[0];
    }
    Logger::log(implode("\n\t", $messages));
}

function notice(...$messages)
{
    Logger::log(implode("\n\t", $messages) , Logger::NOTICE);
}

function verbose(...$messages)
{
    Logger::log(implode("\n\t", $messages) , Logger::VERBOSE);
}

function ultraVebose(...$messages)
{
    Logger::log(implode("\n\t", $messages) , Logger::ULTRA_VERBOSE);
}

function warning(...$messages)
{
    Logger::log(implode("\n\t", $messages) , Logger::WARNING);
}
function error(...$messages)
{
    Logger::log(implode("\n\t", $messages) , Logger::ERROR);
}

function fatal(...$messages)
{
    Logger::log(implode("\n\t", $messages) , Logger::FATAL_ERROR);
}

