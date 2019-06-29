<?php
/**
 * Session module.
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
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\MTProtoTools;

/**
 * Manages MTProto session-specific data
 */
class Session
{
    use MsgIdHandler;
    use SaltHandler;
    use SeqNoHandler;
    public $incoming_messages = [];
    public $outgoing_messages = [];
    public $new_incoming = [];
    public $new_outgoing = [];

    public $http_req_count = 0;
    public $http_res_count = 0;

    public $last_http_wait = 0;
    private $last_chunk = 0;

    public $time_delta = 0;

    public $call_queue = [];
    public $ack_queue = [];

    
    public function haveRead()
    {
        $this->last_chunk = microtime(true);
    }
    /**
     * Get the receive date of the latest chunk of data from the socket
     *
     * @return void
     */
    public function getLastChunk()
    {
        return $this->last_chunk;
    }
}