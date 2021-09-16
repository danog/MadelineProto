<?php

namespace danog\MadelineProto;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * PSR-3 wrapper for MadelineProto's Logger.
 */
class PsrLogger extends AbstractLogger
{
    private const LEVEL_MAP = [
        LogLevel::EMERGENCY => Logger::LEVEL_FATAL,
        LogLevel::ALERT => Logger::LEVEL_FATAL,
        LogLevel::CRITICAL => Logger::LEVEL_FATAL,
        LogLevel::ERROR => Logger::LEVEL_ERROR,
        LogLevel::WARNING => Logger::LEVEL_WARNING,
        LogLevel::NOTICE => Logger::LEVEL_NOTICE,
        LogLevel::INFO => Logger::LEVEL_VERBOSE,
        LogLevel::DEBUG => Logger::LEVEL_ULTRA_VERBOSE
    ];
    /**
     * Logger.
     */
    private Logger $logger;
    /**
     * Constructor.
     *
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed   $level
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logger->logger($message, self::LEVEL_MAP[$level]);
    }
}
