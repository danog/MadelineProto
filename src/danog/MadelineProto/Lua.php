<?php

/**
 * Lua module.
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

namespace danog\MadelineProto;

class Lua
{
    use \danog\Serializable;
    public $MadelineProto;
    protected $Lua;
    protected $script;

    public function __magic_construct($script, $MadelineProto)
    {
        if (!\file_exists($script)) {
            throw new Exception(\danog\MadelineProto\Lang::$current_lang['script_not_exist']);
        }
        $this->MadelineProto = $MadelineProto;
        $this->MadelineProto->settings['updates']['handle_updates'] = true;
        $this->MadelineProto->API->datacenter->sockets[$this->MadelineProto->settings['connection_settings']['default_dc']]->startUpdateLoop();

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
        $this->Lua->registerCallback('tdcliFunction', [$this, 'tdcliFunction']);
        $this->Lua->registerCallback('madelineFunction', [$this, 'madelineFunction']);
        $this->Lua->registerCallback('var_dump', 'var_dump');
        foreach (\get_class_methods($this->MadelineProto->API) as $method) {
            $this->Lua->registerCallback($method, [$this->MadelineProto, $method]);
        }
        $methods = [];
        foreach ($this->MadelineProto->getMethodsNamespaced() as $pair) {
            $namespace = \key($pair);
            $method = $pair[$namespace];
            if ($namespace === 'upload') {
                continue;
            }
            $methods[$namespace][$method] = [$this->MadelineProto->{$namespace}, $method];
        }
        foreach ($this->MadelineProto->getMethodsNamespaced() as $pair) {
            $namespace = \key($pair);
            $method = $pair[$namespace];
            if ($namespace === 'upload') {
                continue;
            }
            $this->{$namespace} = $methods[$namespace];
        }
        $this->MadelineProto->lua = true;
        foreach ($this->MadelineProto->getMethodsNamespaced() as $pair) {
            $namespace = \key($pair);
            $this->MadelineProto->{$namespace}->lua = true;
        }
    }

    public function tdcliFunction($params, $cb = null, $cb_extra = null)
    {
        $params = $this->MadelineProto->td_to_mtproto($this->MadelineProto->tdcliToTd($params));
        if ($params === 0) {
            return 0;
        }
        $result = $this->MadelineProto->API->methodCall($params['_'], $params, ['datacenter' => $this->MadelineProto->API->datacenter->curdc]);
        if (\is_callable($cb)) {
            $cb($this->MadelineProto->mtproto_to_td($result), $cb_extra);
        }

        return $result;
    }

    public function madelineFunction($params, $cb = null, $cb_extra = null)
    {
        $result = $this->MadelineProto->API->methodCall($params['_'], $params, ['datacenter' => $this->MadelineProto->API->datacenter->curdc]);
        if (\is_callable($cb)) {
            $cb($result, $cb_extra);
        }
        self::convertObjects($result);

        return $result;
    }

    public function tdcliUpdateCallback($update)
    {
        $this->Lua->tdcliUpdateCallback($this->MadelineProto->mtproto_to_tdcli($update));
    }

    private function convertArray($array)
    {
        if (!\is_array($array)) {
            return $array;
        }
        if ($this->is_seqential($array)) {
            return \array_flip(\array_map(function ($el) {
                return $el + 1;
            }, \array_flip($array)));
        }
    }

    private function isSequential(array $arr)
    {
        if ([] === $arr) {
            return false;
        }

        return isset($arr[0]) && \array_keys($arr) === \range(0, \count($arr) - 1);
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
        self::convertObjects($params);

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

    public static function convertObjects(&$data)
    {
        \array_walk_recursive($data, function (&$value, $key) {
            if (\is_object($value) && !$value instanceof \tgseclib\Math\BigInteger) {
                $newval = [];
                foreach (\get_class_methods($value) as $name) {
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
