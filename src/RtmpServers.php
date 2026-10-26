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

namespace danog\MadelineProto;

/**
 * Which RTMP servers an RTMP stream may connect to, see {@see API::startRtmpStream()}: when whoever chooses the URL
 * must not make us connect anywhere (into our network, say).
 *
 * Checked at each connection, connecting to the address that was checked (keeping the name for TLS), so that neither
 * a later DNS answer nor a differently parsed URL can get around it.
 */
enum RtmpServers: int
{
    /** Any server. */
    case Any = 0;
    /**
     * Only servers on the internet: every address the name of the server resolves to must be global (no loopback,
     * private, link-local, shared or reserved address, nor an IPv6 address embedding an IPv4 one).
     */
    case Internet = 1;
    /**
     * Only Telegram's RTMP servers (`*.rtmp.t.me`, as in the URLs the Telegram apps show to stream to a livestream or a
     * live story "with another app"), on the internet too.
     */
    case Telegram = 2;

    /**
     * Whether a stream allowed to connect to these servers may be used where only those of $servers are allowed.
     */
    public function isWithin(self $servers): bool
    {
        return $this->value >= $servers->value;
    }
}
