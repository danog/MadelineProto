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

namespace danog\MadelineProto\MTProtoTools;

use Amp\Cancellation;
use danog\MadelineProto\DataCenter;
use danog\MadelineProto\Logger;
use phpseclib3\Math\BigInteger;

/**
 * @property DataCenter $datacenter
 *
 * @internal
 */
trait AuthKeyHandler
{
    /**
     * Get diffie-hellman configuration.
     */
    public function getDhConfig(?Cancellation $cancellation = null): array
    {
        $dh_config = $this->methodCallAsyncRead('messages.getDhConfig', ['version' => $this->dh_config['version'], 'random_length' => 0, 'cancellation' => $cancellation]);
        if ($dh_config['_'] === 'messages.dhConfigNotModified') {
            $this->logger->logger('DH configuration not modified', Logger::VERBOSE);
            return $this->dh_config;
        }
        $dh_config['p'] = new BigInteger((string) $dh_config['p'], 256);
        $dh_config['g'] = new BigInteger($dh_config['g']);
        Crypt::checkPG($dh_config['p'], $dh_config['g']);
        return $this->dh_config = $dh_config;
    }
}
