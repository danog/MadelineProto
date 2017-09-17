<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

class Lua
{
    use \danog\Serializable;
    public $MadelineProto;
    protected $Lua;
    protected $script;

    public function ___construct($script, $MadelineProto)
    {
        if (!file_exists($script)) {
            throw new Exception('Provided script does not exist');
        }
        $this->MadelineProto = $MadelineProto;
        $this->MadelineProto->settings['updates']['handle_updates'] = true;
        $this->script = $script;
        $this->__wakeup();
    }

    public function __sleep()
    {
        return ['MadelineProto', 'script'];
    }

    public function __wakeup()
    {
        $this->Lua = new \Lua($this->script);
        $this->madelineproto_lua = 1;
        $this->Lua->registerCallback('tdcli_function', [$this, 'tdcli_function']);
        $this->Lua->registerCallback('madeline_function', [$this, 'madeline_function']);
        foreach (get_class_methods($this->MadelineProto->API) as $method) {
            $this->Lua->registerCallback($method, [$this->MadelineProto->API, $method]);
        }
        $methods = [];
        foreach ($this->MadelineProto->get_methods_namespaced() as $pair) {
            $namespace = key($pair);
            $method = $pair[$namespace];
            if ($namespace === 'upload') {
                continue;
            }
            $methods[$namespace][$method] = [$this->MadelineProto->{$namespace}, $method];
        }
        foreach ($this->MadelineProto->get_methods_namespaced() as $pair) {
            $namespace = key($pair);
            $method = $pair[$namespace];
            if ($namespace === 'upload') {
                continue;
            }
            $this->{$namespace} = $methods[$namespace];
        }
        $this->MadelineProto->lua = true;
        foreach ($this->MadelineProto->get_methods_namespaced() as $method => $namespace) {
            $namespace = key($pair);
            $this->MadelineProto->{$namespace}->lua = true;
        }
    }

    public function tdcli_function($params, $cb = null, $cb_extra = null)
    {
        $params = $this->MadelineProto->td_to_mtproto($this->MadelineProto->tdcli_to_td($params));
        if ($params === 0) {
            return 0;
        }
        $result = $this->MadelineProto->API->method_call($params['_'], $params, ['datacenter' => $this->MadelineProto->API->datacenter->curdc]);
        if (is_callable($cb)) {
            $cb($this->MadelineProto->mtproto_to_td($result), $cb_extra);
        }

        return $result;
    }

    public function madeline_function($params, $cb = null, $cb_extra = null)
    {
        $result = $this->MadelineProto->API->method_call($params['_'], $params, ['datacenter' => $this->MadelineProto->API->datacenter->curdc]);
        if (is_callable($cb)) {
            $cb($result, $cb_extra);
        }
        self::convert_objects($result);

        return $result;
    }

    public function tdcli_update_callback($update)
    {
        $this->Lua->tdcli_update_callback($this->MadelineProto->mtproto_to_tdcli($update));
    }

    private function convert_array($array)
    {
        if (!is_array($value)) {
            return $array;
        }
        if ($this->is_seqential($value)) {
            return array_flip(array_map(function ($el) {
                return $el + 1;
            }, array_flip($array)));
        }
    }

    private function is_sequential(array $arr)
    {
        if ([] === $arr) {
            return false;
        }

        return isset($arr[0]) && array_keys($arr) === range(0, count($arr) - 1);
    }

    public function __get($name)
    {
        if ($name === 'API') {
            return $this->MadelineProto->API;
        }

        return $this->Lua->{$name};
    }

    public function __call($name, $params)
    {
        self::convert_objects($params);

        try {
            return $this->Lua->{$name}(...$params);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            return ['error_code' => $e->getCode(), 'error' => $e->getMessage()];
        } catch (\danog\MadelineProto\Exception $e) {
            return ['error_code' => $e->getCode(), 'error' => $e->getMessage()];
        } catch (\danog\MadelineProto\TL\Exception $e) {
            return ['error_code' => $e->getCode(), 'error' => $e->getMessage()];
        } catch (\danog\MadelineProto\NothingInTheSocketException $e) {
            return ['error_code' => $e->getCode(), 'error' => $e->getMessage()];
        } catch (\danog\MadelineProto\PTSException $e) {
            return ['error_code' => $e->getCode(), 'error' => $e->getMessage()];
        } catch (\danog\MadelineProto\SecurityException $e) {
            return ['error_code' => $e->getCode(), 'error' => $e->getMessage()];
        } catch (\danog\MadelineProto\TL\Conversion\Exception $e) {
            return ['error_code' => $e->getCode(), 'error' => $e->getMessage()];
        }
    }

    public function __set($name, $value)
    {
        return $this->Lua->{$name} = $value;
    }

    public static function convert_objects(&$data)
    {
        array_walk_recursive($data, function (&$value, $key) {
            if (is_object($value)) {
                $newval = [];
                foreach (get_class_methods($value) as $name) {
                    $newval[$name] = [$value, $name];
                }
                foreach ($value as $key => $name) {
                    if ($key === 'madeline') {
                        continue;
                    }
                    $newval[$key] = $name;
                }
                if ($newval === []) {
                    $newval = $value->__toString();
                }
                $value = $newval;
            }
        });
    }
}
