<?php

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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Wrappers;

/**
 * Manages terms of service.
 */
trait TOS
{
    /**
     * Check for terms of service update.
     *
     * @return \Generator
     */
    public function checkTos(): \Generator
    {
        if ($this->authorized === self::LOGGED_IN && !$this->authorization['user']['bot']) {
            if ($this->tos['expires'] < \time()) {
                $this->logger->logger('Fetching TOS...');
                $this->tos = yield $this->methodCallAsyncRead('help.getTermsOfServiceUpdate', [], ['datacenter' => $this->datacenter->curdc]);
                $this->tos['accepted'] = $this->tos['_'] === 'help.termsOfServiceUpdateEmpty';
            }

            if (!$this->tos['accepted']) {
                $this->logger->logger('Telegram has updated their Terms Of Service', \danog\MadelineProto\Logger::ERROR);
                $this->logger->logger('Accept the TOS before proceeding by calling $MadelineProto->acceptTos().', \danog\MadelineProto\Logger::ERROR);
                $this->logger->logger('You can also decline the TOS by calling $MadelineProto->declineTos().', \danog\MadelineProto\Logger::ERROR);
                $this->logger->logger('By declining the TOS, the currently logged in account will be PERMANENTLY DELETED.', \danog\MadelineProto\Logger::FATAL_ERROR);
                $this->logger->logger('Read the following TOS very carefully: ', \danog\MadelineProto\Logger::ERROR);
                $this->logger->logger($this->tos);

                throw new \danog\MadelineProto\Exception('TOS action required, check the logs', 0, null, 'MadelineProto', 1);
            }
        }
    }

    /**
     * Accept terms of service update.
     *
     * @return \Generator
     */
    public function acceptTos(): \Generator
    {
        $this->tos['accepted'] = yield $this->methodCallAsyncRead('help.acceptTermsOfService', ['id' => $this->tos['terms_of_service']['id']], ['datacenter' => $this->datacenter->curdc]);
        if ($this->tos['accepted']) {
            $this->logger->logger('TOS accepted successfully');
        } else {
            throw new \danog\MadelineProto\Exception('An error occurred while accepting the TOS');
        }
    }

    /**
     * Decline terms of service update.
     *
     * THIS WILL DELETE YOUR ACCOUNT!
     *
     * @return \Generator
     */
    public function declineTos(): \Generator
    {
        yield $this->methodCallAsyncRead('account.deleteAccount', ['reason' => 'Decline ToS update'], ['datacenter' => $this->datacenter->curdc]);
        yield $this->logout();
    }
}
