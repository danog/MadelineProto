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
 * worker.
 */
trait Worker
{
    public $last_serialization = 0;

    public function start_worker_sync($worker)
    {
        $this->db_connect();
        set_time_limit(0);
        ignore_user_abort(1);
        $this->lock_file = fopen($this->sessions_dir.$worker, 'c+');
        $got_lock = flock($this->lock_file, LOCK_EX | LOCK_NB, $wouldblock);
        if ($this->lock_file === false || (!$got_lock && !$wouldblock)) {
            return ['ok' => false, 'error_code' => 400, "Couldn't open/lock session file"];
        }
        if (!$got_lock && $wouldblock) {
            return ['ok' => true, 'result' => 'This worker is already running'];
        }
        // Deserialize contents if needed
        fseek($this->lock_file, 0);
        $result = stream_get_contents($this->lock_file);

        if ($result !== '') {
            try {
                $this->MadelineProto = unserialize($result);
            } catch (\danog\MadelineProto\Exception $e) {
                error_log('An error occurred while deserializing '.$worker);
                $this->MadelineProto = new \danog\MadelineProto\API(['logger' => ['logger' => 2, 'logger_param' => $this->sessions_dir.$worker.'.log']]);
            }
        } else {
            $this->MadelineProto = new \danog\MadelineProto\API(['logger' => ['logger' => 2, 'logger_param' => $this->sessions_dir.$worker.'.log']]);
        }
        $this->serialize_worker();
        $stop = false;
        /*
        echo json_encode(['ok' => true, 'result' => 'Worker started successfully!']);

        */
        while (true) {
            $actions = $this->pdo->prepare('SELECT * FROM worker_jobs WHERE worker = ? AND processed = ?');
            $actions->execute([$worker, (int) false]);
            $actions = $actions->fetchAll();
            foreach ($actions as $action) {
                $result = ['ok' => false, 'error_code' => 404, 'error_description' => 'The method '.$this->method.' does not exist'];
                $params = json_decode($action['params']);
                try {
                    switch ($action['method']) {
                        case 'stop_worker':
                            $stop = true;
                            $result = ['ok' => true, 'result' => 'Worker stopped'];
                            break;
                        case 'bot_login':
                            $result = ['ok' => true, 'result' => $this->MadelineProto->bot_login($settings['other']['params']['token'])];
                            break;
                        case 'phone_login':
                            $result = ['ok' => true, 'result' => $this->MadelineProto->phone_login($settings['other']['params']['number'])];
                            break;
                        case 'complete_phone_login':
                            $result = ['ok' => true, 'result' => $this->MadelineProto->complete_phone_login($settings['other']['params']['code'])];
                            break;
                        default:

                            if ($this->MadelineProto->API->methods->find_by_method($this->method) !== false) {
                                $result = ['ok' => true, 'result' => $this->MadelineProto->API->method_call($this->method, $settings['other']['params'])];
                            }
                    }
                } catch (\danog\MadelineProto\ResponseException $e) {
                    $result = ['ok' => false, 'error_code' => 400, 'error_description' => $e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile())];
                    error_log('Exception thrown in worker '.$worker.': '.$e->getMessage());
                    error_log($e->getTraceAsString());
                } catch (\danog\MadelineProto\Exception $e) {
                    $result = ['ok' => false, 'error_code' => 400, 'error_description' => $e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile())];
                    error_log('Exception thrown in worker '.$worker.': '.$e->getMessage());
                    error_log($e->getTraceAsString());
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $result = ['ok' => false, 'error_code' => $e->getCode(), 'error_description' => $e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile())];
                    error_log('Exception thrown in worker '.$worker.': '.$e->getMessage());
                    error_log($e->getTraceAsString());
                } catch (\danog\MadelineProto\TL\Exception $e) {
                    $result = ['ok' => false, 'error_code' => 400, 'error_description' => $e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile())];
                    error_log('Exception thrown in worker '.$worker.': '.$e->getMessage());
                    error_log($e->getTraceAsString());
                }
                $result['req_id'] = $action;
                $this->pdo->prepare('UPDATE worker_jobs SET response=?, processed=? WHERE request_id=?')->execute([json_encode($result), (int) true, $action['request_id']]);
            }
            try {
                $this->MadelineProto->API->recv_message();
            } catch (\danog\MadelineProto\ResponseException $e) {
                echo json_encode(['ok' => false, 'error_code' => 400, 'error_description' => $e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile())]);
                error_log('Exception thrown in worker '.$worker.': '.$e->getMessage());
                error_log($e->getTraceAsString());
            } catch (\danog\MadelineProto\Exception $e) {
                if (preg_match('/Wrong length was read/', $e->getMessage())) {
                    continue;
                }
                echo json_encode(['ok' => false, 'error_code' => 400, 'error_description' => $e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile())]);
                error_log('Exception thrown in worker '.$worker.': '.$e->getMessage());
                error_log($e->getTraceAsString());
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                echo json_encode(['ok' => false, 'error_code' => $e->getCode(), 'error_description' => $e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile())]);
                error_log('Exception thrown in worker '.$worker.': '.$e->getMessage());
                error_log($e->getTraceAsString());
            } catch (\danog\MadelineProto\TL\Exception $e) {
                echo json_encode(['ok' => false, 'error_code' => 400, 'error_description' => $e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile())]);
                error_log('Exception thrown in worker '.$worker.': '.$e->getMessage());
                error_log($e->getTraceAsString());
            }
            $this->serialize_worker();
            if (empty($actions)) {
                usleep(250000);
            }
            if ($stop) {
                break;
            }
        }

        flock($this->lock_file, LOCK_UN);
        fclose($this->lock_file);

        return ['ok' => true, 'result' => 'Worker stopped successfully!'];
    }

    public function serialize_worker()
    {
        if (time() - $this->last_serialization < $this->settings['workers']['serialization_interval']) {
            return false;
        }
        ftruncate($this->lock_file, 0);
        rewind($this->lock_file);
        $serialized = serialize($this->MadelineProto);
        fwrite($this->lock_file, $serialized);
        $this->last_serialization = time();

        return true;
    }
}
