<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\TL;

/**
 * @internal
 */
final class TLMethods
{
    use TLParams;
    public array $by_id = [];
    public array $by_method = [];
    public array $method_namespace = [];
    public function __sleep(): array
    {
        return ['by_id', 'by_method', 'method_namespace'];
    }
    public function add(array $json_dict, string $scheme_type): void
    {
        $this->by_id[$json_dict['id']] = [
            'method' => $json_dict['method'],
            'type' => $json_dict['type'],
            'params' => $json_dict['params'],
            'flags' => [],
            'encrypted' => $scheme_type !== 'mtproto',
        ];
        if (preg_match('/^(v|V)ector\\<(.*)\\>$/', $json_dict['type'], $matches)) {
            $this->by_id[$json_dict['id']]['type'] = $matches[1] === 'v' ? 'vector' : 'Vector t';
            $this->by_id[$json_dict['id']]['subtype'] = $matches[2];
        }
        $this->by_method[$json_dict['method']] = $json_dict['id'];
        $namespace = explode('.', $json_dict['method']);
        if (isset($namespace[1])) {
            $this->method_namespace[] = [$namespace[0] => $namespace[1]];
        }
        $this->parseParams($json_dict['id'], false, $json_dict['method']);
    }
    public function findById(string $id)
    {
        if (isset($this->by_id[$id])) {
            $method = $this->by_id[$id];
            $method['id'] = $id;
            return $method;
        }
        return false;
    }
    public function findByMethod(string $method_name)
    {
        if (isset($this->by_method[$method_name])) {
            $method = $this->by_id[$this->by_method[$method_name]];
            $method['id'] = $this->by_method[$method_name];
            return $method;
        }
        return false;
    }
}
