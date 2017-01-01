<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\Worker;

/**
 * Tools for the worker.
 */
trait WorkerTools
{
    public function check_all_workers()
    {
        $result = ['ok' => true, 'result' => []];
        foreach (glob($this->sessions_dir.'*') as $session) {
            if (stripos($session, '.log') !== false) {
                continue;
            }
            $session = basename($session);
            $result['result'][] = $this->check_worker($session);
        }

        return $result;
    }

    public function start_worker_async($worker, $recursive = true)
    {
        shell_exec('curl '.escapeshellarg($this->settings['other']['endpoint'].$worker.'/start_worker_sync').'  > /dev/null 2> /dev/null & ');
        sleep(30);

        return $this->check_worker($worker, $recursive);
    }

    public function check_worker($worker, $recursive = true)
    {
        $this->lock_file = fopen($this->sessions_dir.$worker, 'c+');
        $got_lock = flock($this->lock_file, LOCK_EX | LOCK_NB, $wouldblock);
        if ($this->lock_file === false || (!$got_lock && !$wouldblock)) {
            return ['ok' => false, 'error_code' => 400, "Couldn't open/lock session file"];
        }
        if (!$got_lock && $wouldblock) {
            return ['ok' => true, 'result' => ['active' => true]];
        }
        if ($recursive) { // If worker is turned off and $recursive
            return $this->start_worker_async($worker, false);
        }

        return ['ok' => true, 'result' => ['active' => false]];
    }
}
