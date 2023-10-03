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

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * RPC settings.
 */
final class RPC extends SettingsAbstract
{
    /**
     * RPC resend timeout.
     */
    protected int $rpcResendTimeout = 10;
    /**
     * RPC drop timeout.
     */
    protected int $rpcDropTimeout = 60;

    /**
     * Flood timeout: if FLOOD_WAIT_ time is bigger than this, throw exception instead of waiting asynchronously.
     */
    protected int $floodTimeout = 30;

    /**
     * Encode payload with GZIP if bigger than.
     */
    protected int $gzipEncodeIfGt = 1024 * 1024;

    /**
     * Get RPC drop timeout.
     */
    public function getRpcDropTimeout(): int
    {
        return $this->rpcDropTimeout;
    }

    /**
     * Set RPC drop timeout.
     *
     * @param int $rpcDropTimeout RPC timeout
     */
    public function setRpcDropTimeout(int $rpcDropTimeout): self
    {
        $this->rpcDropTimeout = $rpcDropTimeout;

        return $this;
    }

    /**
     * Get RPC resend timeout.
     */
    public function getRpcResendTimeout(): int
    {
        return $this->rpcResendTimeout;
    }

    /**
     * Set RPC resend timeout.
     *
     * @param int $rpcResendTimeout RPC timeout.
     */
    public function setRpcResendTimeout(int $rpcResendTimeout): self
    {
        $this->rpcResendTimeout = $rpcResendTimeout;

        return $this;
    }

    /**
     * Get flood timeout: if FLOOD_WAIT_ time is bigger than this, throw exception instead of waiting asynchronously.
     */
    public function getFloodTimeout(): int
    {
        return max(5, $this->floodTimeout);
    }

    /**
     * Set flood timeout: if FLOOD_WAIT_ time is bigger than this, throw exception instead of waiting asynchronously.
     *
     * Must be bigger than 5.
     *
     * @param int $floodTimeout Flood timeout: if FLOOD_WAIT_ time is bigger than this, throw exception instead of waiting asynchronously
     */
    public function setFloodTimeout(int $floodTimeout): self
    {
        $this->floodTimeout = $floodTimeout;

        return $this;
    }

    /**
     * Get encode payload with GZIP if bigger than.
     */
    public function getGzipEncodeIfGt(): int
    {
        return $this->gzipEncodeIfGt;
    }

    /**
     * Set encode payload with GZIP if bigger than.
     *
     * @param int $gzipEncodeIfGt Encode payload with GZIP if bigger than
     */
    public function setGzipEncodeIfGt(int $gzipEncodeIfGt): self
    {
        $this->gzipEncodeIfGt = $gzipEncodeIfGt;

        return $this;
    }
}
