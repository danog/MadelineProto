<?php

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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\TL;

class TLConstructors
{
    use \danog\Serializable;
    use \danog\MadelineProto\Tools;
    use TLParams;
    public $by_id = [];
    public $by_predicate_and_layer = [];
    public $layers = [];

    public function __sleep()
    {
        return ['by_predicate_and_layer', 'by_id', 'layers'];
    }

    public function add($json_dict, $scheme_type)
    {
        if (isset($this->by_id[$json_dict['id']]) && (!isset($this->by_id[$json_dict['id']]['layer']) || $this->by_id[$json_dict['id']]['layer'] > $json_dict['layer'])) {
            return false;
        }

        $predicate = (string) (($scheme_type === 'mtproto' && $json_dict['predicate'] === 'message' ? 'MT' : '').$json_dict['predicate']);

        $this->by_id[$json_dict['id']] = ['predicate' => $predicate, 'params' => $json_dict['params'], 'type' => ($scheme_type === 'mtproto' && $json_dict['type'] === 'Message' ? 'MT' : '').$json_dict['type']];
        if ($scheme_type === 'secret') {
            $this->by_id[$json_dict['id']]['layer'] = $json_dict['layer'];
            $this->layers[$json_dict['layer']] = $json_dict['layer'];
            \ksort($this->layers);
        } else {
            $json_dict['layer'] = '';
        }
        $this->by_predicate_and_layer[$predicate.$json_dict['layer']] = $json_dict['id'];
        $this->parseParams($json_dict['id'], $scheme_type === 'mtproto');
    }

    public function findByType($type)
    {
        foreach ($this->by_id as $id => $constructor) {
            if ($constructor['type'] === $type) {
                $constructor['id'] = $id;

                return $constructor;
            }
        }

        return false;
    }

    public function findByPredicate($predicate, $layer = -1)
    {
        if ($layer !== -1) {
            foreach ($this->layers as $alayer) {
                if ($alayer <= $layer) {
                    if (isset($this->by_predicate_and_layer[$predicate.$alayer])) {
                        $chosenid = $this->by_predicate_and_layer[$predicate.$alayer];
                    }
                } elseif (!isset($chosenid)) {
                    $chosenid = $this->by_predicate_and_layer[$predicate.$alayer];
                }
            }
            if (!isset($chosenid)) {
                return false;
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
     *
     * @return array|false
     */
    public function findById(string $id)
    {
        if (isset($this->by_id[$id])) {
            $constructor = $this->by_id[$id];
            $constructor['id'] = $id;

            return $constructor;
        }

        return false;
    }
}
