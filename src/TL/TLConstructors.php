<?php

declare(strict_types=1);

/**
 * TLConstructors module.
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
final class TLConstructors
{
    use TLParams;
    public array $by_id = [];
    public array $by_predicate_and_layer = [];
    public array $layers = [];
    public function __sleep()
    {
        return ['by_predicate_and_layer', 'by_id', 'layers'];
    }
    public function add(array $json_dict, string $scheme_type): void
    {
        if (isset($this->by_id[$json_dict['id']]) && (!isset($this->by_id[$json_dict['id']]['layer']) || $this->by_id[$json_dict['id']]['layer'] > $json_dict['layer'])) {
            return;
        }
        $predicate = ($scheme_type === 'mtproto' && $json_dict['predicate'] === 'message' ? 'MT' : '').$json_dict['predicate'];
        $this->by_id[$json_dict['id']] = [
            'predicate' => $predicate,
            'params' => $json_dict['params'],
            'flags' => [],
            'type' => ($scheme_type === 'mtproto' && $json_dict['type'] === 'Message' ? 'MT' : '').$json_dict['type'],
            'encrypted' => $scheme_type !== 'mtproto',
        ];
        if ($scheme_type === 'secret') {
            $this->by_id[$json_dict['id']]['layer'] = $json_dict['layer'];
            $this->layers[$json_dict['layer']] = $json_dict['layer'];
            ksort($this->layers);
        } else {
            $json_dict['layer'] = '';
        }
        $this->by_predicate_and_layer[$predicate.$json_dict['layer']] = $json_dict['id'];
        $this->parseParams($json_dict['id'], $scheme_type === 'mtproto', $json_dict['predicate']);
    }
    public function findByType(string $type)
    {
        foreach ($this->by_id as $id => $constructor) {
            if ($constructor['type'] === $type) {
                $constructor['id'] = $id;
                return $constructor;
            }
        }
        return false;
    }
    public function findByPredicate(string $predicate, int $layer = -1)
    {
        if ($layer !== -1) {
            $chosenid = null;
            foreach ($this->layers as $alayer) {
                if ($alayer <= $layer) {
                    if (isset($this->by_predicate_and_layer[$predicate.$alayer])) {
                        $chosenid = $this->by_predicate_and_layer[$predicate.$alayer];
                    }
                } elseif (!isset($chosenid)) {
                    if (isset($this->by_predicate_and_layer[$predicate.$alayer])) {
                        $chosenid = $this->by_predicate_and_layer[$predicate.$alayer];
                    }
                }
            }
            if (!isset($chosenid)) {
                return $this->findByPredicate($predicate);
            }
            $constructor = $this->by_id[$chosenid];
            $constructor['id'] = $chosenid;
            return $constructor;
        }
        if (isset($this->by_predicate_and_layer[$predicate])) {
            $constructor = $this->by_id[$this->by_predicate_and_layer[$predicate]];
            $constructor['id'] = $this->by_predicate_and_layer[$predicate];
            return $constructor;
        }
        return false;
    }
    /**
     * Find constructor by ID.
     *
     * @param string $id Constructor ID
     */
    public function findById(string $id): array|false
    {
        if (isset($this->by_id[$id])) {
            $constructor = $this->by_id[$id];
            $constructor['id'] = $id;
            return $constructor;
        }
        return false;
    }
}
