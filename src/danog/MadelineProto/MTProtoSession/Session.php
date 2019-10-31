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
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoSession;

/**
 * Manages MTProto session-specific data.
 */
abstract class Session
{
    use AckHandler;
    use MsgIdHandler;
    use ResponseHandler;
    use SeqNoHandler;
    use CallHandler;

    public $incoming_messages = [];
    public $outgoing_messages = [];
    public $new_incoming = [];
    public $new_outgoing = [];

    public $pending_outgoing = [];
    public $pending_outgoing_key = 'a';

    public $time_delta = 0;

    public $call_queue = [];
    public $ack_queue = [];

    /**
     * Reset MTProto session.
     *
     * @return void
     */
    public function resetSession()
    {
        $this->session_id = \danog\MadelineProto\Tools::random(8);
        $this->session_in_seq_no = 0;
        $this->session_out_seq_no = 0;
        $this->max_incoming_id = null;
        $this->max_outgoing_id = null;
    }
    /**
     * Create MTProto session if needed.
     *
     * @return void
     */
    public function createSession()
    {
        if ($this->session_id === null) {
            $this->session_id = \danog\MadelineProto\Tools::random(8);
            $this->session_in_seq_no = 0;
            $this->session_out_seq_no = 0;
        }
    }

    /**
     * Backup eventual unsent messages before session deletion.
     *
     * @return array
     */
    public function backupSession(): array
    {
        $pending = \array_values($this->pending_outgoing);
        foreach ($this->new_outgoing as $id) {
            $pending[] = $this->outgoing_messages[$id];
        }
        return $pending;
    }
}
