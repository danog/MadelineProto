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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Tgcalls;

use danog\MadelineProto\Logger;
use Psr\Log\AbstractLogger;

/**
 * Forwards php-rtc's PSR debug output (DTLS, SRTP, SCTP and RTP routing diagnostics) into a call's
 * log at VERBOSE level, when the `MP_RTC_DEBUG=1` environment variable asks for it.
 *
 * @internal
 */
final class RtcDebugLogger extends AbstractLogger
{
    public function __construct(private readonly CallControllerInterface $call)
    {
    }

    /**
     * @param string|\Stringable $message
     */
    #[\Override]
    public function log($level, $message, array $context = []): void
    {
        $this->call->log("[rtc $level] $message", Logger::VERBOSE);
    }
}
