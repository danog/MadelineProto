<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\Wrappers;

/**
 * Manages logging in and out.
 */
trait TOS
{
    public function check_tos()
    {
        if ($this->authorized === self::LOGGED_IN && !$this->authorization['user']['bot']) {
            if ($this->tos['expires'] < time()) {
                $this->logger->logger('Fetching TOS...');
                $this->tos = $this->method_call('help.getTermsOfServiceUpdate', [], ['datacenter' => $this->datacenter->curdc]);
                $this->tos['accepted'] = $this->tos['_'] === 'help.termsOfServiceUpdateEmpty';
            }

            if (!$this->tos['accepted']) {
                $this->logger->logger('Telegram has updated their Terms Of Service', \danog\MadelineProto\Logger::ERROR);
                $this->logger->logger('Accept the TOS before proceeding by calling $MadelineProto->accept_tos().', \danog\MadelineProto\Logger::ERROR);
                $this->logger->logger('You can also decline the TOS by calling $MadelineProto->decline_tos().', \danog\MadelineProto\Logger::ERROR);
                $this->logger->logger('By declining the TOS, the currently logged in account will be PERMANENTLY DELETED.', \danog\MadelineProto\Logger::FATAL_ERROR);
                $this->logger->logger('Read the following TOS very carefully: ', \danog\MadelineProto\Logger::ERROR);
                $this->logger->logger($this->tos);

                throw new \danog\MadelineProto\Exception('TOS action required, check the logs', 0, null, 'MadelineProto', 1);
            }
        }
    }

    public function accept_tos()
    {
        $this->tos['accepted'] = $this->method_call('help.acceptTermsOfService', ['id' => $this->tos['terms_of_service']['id']], ['datacenter' => $this->datacenter->curdc]);
        if ($this->tos['accepted']) {
            $this->logger->logger('TOS accepted successfully');
        } else {
            throw new \danog\MadelineProto\Exception('An error occurred while accepting the TOS');
        }
    }

    public function decline_tos()
    {
        $this->method_call('account.deleteAccount', ['reason' => 'Decline ToS update'], ['datacenter' => $this->datacenter->curdc]);
        $this->logout();
    }
}
