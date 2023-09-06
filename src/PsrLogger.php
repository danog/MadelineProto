<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

/**
 * PSR-3 wrapper for MadelineProto's Logger.
 */
final class PsrLogger extends AbstractLogger
{
    private const LEVEL_MAP = [
        LogLevel::EMERGENCY => Logger::LEVEL_FATAL,
        LogLevel::ALERT => Logger::LEVEL_FATAL,
        LogLevel::CRITICAL => Logger::LEVEL_FATAL,
        LogLevel::ERROR => Logger::LEVEL_ERROR,
        LogLevel::WARNING => Logger::LEVEL_WARNING,
        LogLevel::NOTICE => Logger::LEVEL_NOTICE,
        LogLevel::INFO => Logger::LEVEL_VERBOSE,
        LogLevel::DEBUG => Logger::LEVEL_ULTRA_VERBOSE,
    ];
    /**
     * Logger.
     */
    private Logger $logger;
    /**
     * Constructor.
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }
    /**
     * Logs with an arbitrary level.
     *
     * @param  array<mixed>             $context
     * @throws InvalidArgumentException
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logger->logger($message, self::LEVEL_MAP[$level]);
    }
}
