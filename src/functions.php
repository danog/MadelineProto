<?php

namespace danog\MadelineProto;

use danog\MadelineProto\Logger;

/**
 * equals to Logger::logger('hello' , Logger::NOTICE)
 * @param string|array<string> $messages
 * @return void
 */
function logger(...$messages)
{
    Logger::log(implode("\n\t", $messages));
}

/**
 * equals to Logger::logger('hello' , Logger::NOTICE)
 * @param string|array<string> $messages
 * @return void
 */
function notice(...$messages)
{
    Logger::log(implode("\n\t", $messages) , Logger::NOTICE);
}

/**
 * equals to Logger::logger('hello' , Logger::VERBOSE)
 * @param string|array<string> $messages
 * @return void
 */
function verbose(...$messages)
{
    Logger::log(implode("\n\t", $messages) , Logger::VERBOSE);
}

/**
 * equals to Logger::logger('hello' , Logger::ULTRA_VERBOSE)
 * @param string|array<string> $messages
 * @return void
 */
function ultraVebose(...$messages)
{
    Logger::log(implode("\n\t", $messages) , Logger::ULTRA_VERBOSE);
}

/**
 * equals to Logger::logger('hello' , Logger::WARNING)
 * @param string|array<string> $messages
 * @return void
 */
function warning(...$messages)
{
    Logger::log(implode("\n\t", $messages) , Logger::WARNING);
}

/**
 * equals to Logger::logger('hello' , Logger::ERROR)
 * @param string|array<string> $messages
 * @return void
 */
function error(...$messages)
{
    Logger::log(implode("\n\t", $messages) , Logger::ERROR);
}

/**
 * equals to Logger::logger('hello' , Logger::FATAL_ERROR)
 * @param string|array<string> $messages
 * @return void
 */
function fatal(...$messages)
{
    Logger::log(implode("\n\t", $messages) , Logger::FATAL_ERROR);
}

