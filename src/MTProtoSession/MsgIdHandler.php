<?php

declare(strict_types=1);

/**
 * MsgIdHandler module.
 *
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

namespace danog\MadelineProto\MTProtoSession;

use danog\MadelineProto\Connection;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;

/**
 * Manages message ids.
 *
 * @internal
 */
final class MsgIdHandler
{
    /**
     * Session instance.
     */
    private Connection $session;

    /**
     * Constructor.
     *
     * @param Connection $session Session
     */
    public function __construct(Connection $session)
    {
        $this->session = $session;
    }

    /**
     * Maximum incoming ID.
     *
     */
    private int $maxIncomingId = 0;
    /**
     * Maximum outgoing ID.
     *
     */
    private int $maxOutgoingId = 0;

    /**
     * Check validity of given message ID.
     */
    public function checkOutgoingMessageId(int $newMessageId): void
    {
        $minMessageId = (time() + $this->session->time_delta - 300) << 32;
        if ($newMessageId < $minMessageId) {
            $this->session->API->logger->logger('Given message id ('.$newMessageId.') is too old compared to the min value ('.$minMessageId.').', Logger::WARNING);
        }
        $maxMessageId = (time() + $this->session->time_delta + 30) << 32;
        if ($newMessageId > $maxMessageId) {
            throw new Exception('Given message id ('.$newMessageId.') is too new compared to the max value ('.$maxMessageId.'). Please sync your date using NTP.');
        }
        if ($newMessageId % 4) {
            throw new Exception('Given message id ('.$newMessageId.') is not divisible by 4. Please sync your date using NTP.');
        }
        if ($newMessageId <= $this->maxOutgoingId) {
            throw new Exception('Given message id ('.$newMessageId.') is lower than or equal to the current limit ('.$this->maxOutgoingId.'). Please sync your date using NTP.');
        }
        $this->maxOutgoingId = $newMessageId;
    }
    /**
     * Check validity of given message ID.
     */
    public function checkIncomingMessageId(int $newMessageId, bool $container): void
    {
        $minMessageId = (time() + $this->session->time_delta - 300) << 32;
        if ($newMessageId < $minMessageId) {
            $this->session->API->logger->logger('Given message id ('.$newMessageId.') is too old compared to the min value ('.$minMessageId.').', Logger::WARNING);
        }
        $maxMessageId = (time() + $this->session->time_delta + 30) << 32;
        if ($newMessageId > $maxMessageId) {
            throw new Exception('Given message id ('.$newMessageId.') is too new compared to the max value ('.$maxMessageId.'). Please sync your date using NTP.');
        }
        if (!($newMessageId % 2)) {
            throw new Exception('message id mod 4 != 1 or 3');
        }
        $key = $this->maxIncomingId;
        if ($container) {
            if ($newMessageId >= $key) {
                $this->session->API->logger->logger('Given message id ('.$newMessageId.') is bigger than or equal to the current limit ('.$key.'). Please sync your date using NTP.', Logger::ULTRA_VERBOSE);
            }
        } else {
            if ($newMessageId <= $key) {
                $this->session->API->logger->logger('Given message id ('.$newMessageId.') is lower than or equal to the current limit ('.$key.'). Please sync your date using NTP.', Logger::ULTRA_VERBOSE);
            }
        }
        $this->maxIncomingId = $newMessageId;
    }
    /**
     * Generate message ID.
     */
    public function generateMessageId(): int
    {
        $messageId = (time() + $this->session->time_delta) << 32;
        $messageId += hrtime(true) % 1000_000;
        $messageId += 4 - ($messageId % 4);
        if ($messageId <= $this->maxOutgoingId) {
            $messageId = $this->maxOutgoingId + 4;
        }
        $this->checkOutgoingMessageId($messageId);
        return $messageId;
    }
    /**
     * Get maximum message ID.
     *
     * @param boolean $incoming Incoming or outgoing message ID
     */
    public function getMaxId(bool $incoming)
    {
        return $this->{$incoming ? 'maxIncomingId' : 'maxOutgoingId'};
    }

    /**
     * Cleanup incoming and outgoing messages.
     */
    public function cleanup(): void
    {
        $usage = memory_get_usage();
        $this->session->cleanupSession();
        $cleaned = round(($usage - memory_get_usage())/1024/1024, 1);
        if ($cleaned && !Magic::$suspendPeriodicLogging) {
            $this->session->API->logger->logger("Zend hashmap reallocation done. Cleaned memory: $cleaned Mb", Logger::VERBOSE);
        }
    }
}
