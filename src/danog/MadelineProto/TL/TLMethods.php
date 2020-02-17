<?php

/**
 * TLMethods module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\TL;

class TLMethods
{
    use \danog\Serializable;
    use \danog\MadelineProto\Tools;
    use TLParams;
    public $by_id = [];
    public $by_method = [];
    public $method_namespace = [];
    public function __sleep()
    {
        return ['by_id', 'by_method', 'method_namespace'];
    }
    public function add($json_dict)
    {
        $this->by_id[$json_dict['id']] = ['method' => $json_dict['method'], 'type' => $json_dict['type'], 'params' => $json_dict['params']];
        $this->by_method[$json_dict['method']] = $json_dict['id'];
        $namespace = \explode('.', $json_dict['method']);
        if (isset($namespace[1])) {
            $this->method_namespace[] = [$namespace[0] => $namespace[1]];
        }
        $this->parseParams($json_dict['id']);
    }
    public function findById($id)
    {
        if (isset($this->by_id[$id])) {
            $method = $this->by_id[$id];
            $method['id'] = $id;
            return $method;
        }
        return false;
    }
    public function findByMethod($method_name)
    {
        if (isset($this->by_method[$method_name])) {
            $method = $this->by_id[$this->by_method[$method_name]];
            $method['id'] = $this->by_method[$method_name];
            return $method;
        }
        return false;
    }
}
