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

namespace danog\MadelineProto;

class WebAPI 
{
    use \danog\MadelineProto\Worker\Worker;
    use \danog\MadelineProto\Worker\WorkerTools;
    use \danog\MadelineProto\Worker\Tools;
    
    public $settings = [];

    public function __construct($settings) {
        $this->settings = $settings;
        set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);
        $uri = preg_replace(["/\?.*$/", "/^\//"], '', $settings['other']['uri'], 1);
        $this->token = preg_replace('/\/.*/', '', $uri);
        $this->method = strtolower(preg_replace('/.*\//', '', $uri, 1));
        $this->sessions_dir = $this->settings['other']['homedir'].'/sessions/';
        if (!file_exists($this->sessions_dir)) mkdir($this->sessions_dir);
        foreach ($this->settings['other']['params'] as &$param) {
            $new_param = json_decode($param, true);
            if (is_array($new_param)) $param = $new_param;
        }
    }

    public function run() {
        switch ($this->token) {
            case 'new_token':
                $token = '';
                while ($token === '' || file_exists($this->sessions_dir.$token)) {
                    for ($len = 0; $len < rand($this->settings['token']['min_length'], $this->settings['token']['min_length']); $len++) {
                        $token .= $this->base_64[rand(0, 63)];
                    }
                }
                touch($this->sessions_dir.$token);
                return ['ok' => true, 'result' => ['token' => $token, 'worker_status' => $this->start_worker_async($token)]];
            case 'check_all_workers':
                return $this->check_all_workers();
            case '':
                return ['ok' => false, 'error_code' => 404, 'error_description' => 'Invalid token provided'];
            default:
                if (!$this->check_token($this->token)) return ['ok' => false, 'error_code' => 404, 'error_description' => 'Invalid token provided'];
                if (!file_exists($this->sessions_dir.$this->token)) return ['ok' => false, 'error_code' => 404, 'error_description' => 'Invalid token provided'];
        }
        switch ($this->method) {
            case 'start_worker_sync':
                return $this->start_worker_sync($this->token);
            case 'start_worker':
            case 'start_worker_async':
                return $this->start_worker_async($this->token);
            case 'check_worker':
                return $this->check_worker($this->token);
            default:
                if (!$this->check_worker($this->token)['result']['active']) throw new Exception("Worker not active");

                $this->db_connect();
                $insert = $this->pdo->prepare('INSERT INTO worker_jobs (worker, method, params) VALUES (?, ?, ?);');
                $insert->execute([$this->token, $this->method, json_encode($this->settings['other']['params'])]);
                $id = $this->pdo->lastInsertId();
                $request_count = 0;
                while ($request_count++ < $this->settings['other']['response_wait']) {
                    usleep(250000);
                    $select = $this->pdo->prepare('SELECT response, request_id FROM worker_jobs WHERE request_id=? AND processed = ?');
                    $select->execute([$id, (int)true]);
                    $select = $select->fetchAll();
                    if (count($select) > 0) {
                        if (count($select) > 1) return ['ok' => false, 'error_code' => 400, 'error_description' => 'Got multiple responses, request id '.$id];
                        if ($select[0]['request_id'] != $id) return ['ok' => false, 'error_code' => 400, 'error_description' => 'Request id mismatch: got '.$select[0]['request_id'].', actual request id '.$id];

                        $res = json_decode($select[0]['response'], true);
                        if ($res === null) return ['ok' => false, 'error_code' => 400, 'error_description' => 'Result is null, request id '.$id];
                        $this->pdo->prepare('DELETE FROM worker_jobs WHERE request_id = ? AND processed = ?')->execute([$id, (int)true]);
                        return $res;
                    }
                }
                return ['ok' => false, 'error_code' => 400, 'error_description' => 'Timeout while fetching result, request id '.$id];
        }
    }
}