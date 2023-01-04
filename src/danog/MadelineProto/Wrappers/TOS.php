<?php

declare(strict_types=1);

/**
 * TOS module.
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

namespace danog\MadelineProto\Wrappers;

use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;

/**
 * Manages terms of service.
 */
trait TOS
{
    /**
     * Check for terms of service update.
     *
     * Will throw a \danog\MadelineProto\Exception if a new TOS is available.
     */
    public function checkTos(): void
    {
        if ($this->authorized === self::LOGGED_IN && !$this->authorization['user']['bot']) {
            if ($this->tos['expires'] < \time()) {
                $this->logger->logger('Fetching TOS...');
                $this->tos = $this->methodCallAsyncRead('help.getTermsOfServiceUpdate', []);
                $this->tos['accepted'] = $this->tos['_'] === 'help.termsOfServiceUpdateEmpty';
            }
            if (!$this->tos['accepted']) {
                $this->logger->logger('Telegram has updated their Terms Of Service', Logger::ERROR);
                $this->logger->logger('Accept the TOS before proceeding by calling $MadelineProto->acceptTos().', Logger::ERROR);
                $this->logger->logger('You can also decline the TOS by calling $MadelineProto->declineTos().', Logger::ERROR);
                $this->logger->logger('By declining the TOS, the currently logged in account will be PERMANENTLY DELETED.', Logger::FATAL_ERROR);
                $this->logger->logger('Read the following TOS very carefully: ', Logger::ERROR);
                $this->logger->logger($this->tos);
                throw new Exception('TOS action required, check the logs', 0, null, 'MadelineProto', 1);
            }
        }
    }
    /**
     * Accept terms of service update.
     */
    public function acceptTos(): void
    {
        $this->tos['accepted'] = $this->methodCallAsyncRead('help.acceptTermsOfService', ['id' => $this->tos['terms_of_service']['id']]);
        if ($this->tos['accepted']) {
            $this->logger->logger('TOS accepted successfully');
        } else {
            throw new Exception('An error occurred while accepting the TOS');
        }
    }
    /**
     * Decline terms of service update.
     *
     * THIS WILL DELETE YOUR ACCOUNT!
     */
    public function declineTos(): void
    {
        $this->methodCallAsyncRead('account.deleteAccount', ['reason' => 'Decline ToS update']);
        $this->logout();
    }
}
