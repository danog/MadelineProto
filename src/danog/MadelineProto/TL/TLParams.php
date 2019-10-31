<?php

/**
 * TLParams module.
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

trait TLParams
{
    public function parseParams($key, $mtproto = false)
    {
        foreach ($this->by_id[$key]['params'] as $kkey => $param) {
            if (\preg_match('/(\w*)\.(\d*)\?(.*)/', $param['type'], $matches)) {
                $param['pow'] = \pow(2, $matches[2]);
                $param['type'] = $matches[3];
            }
            if (\preg_match('/^(v|V)ector\<(.*)\>$/', $param['type'], $matches)) {
                $param['type'] = $matches[1] === 'v' ? 'vector' : 'Vector t';
                $param['subtype'] = $matches[2];
                $param['subtype'] = ($mtproto && $param['subtype'] === 'Message' ? 'MT' : '').$param['subtype'];
                $param['subtype'] = $mtproto && $param['subtype'] === '%Message' ? '%MTMessage' : $param['subtype'];
            }
            $param['type'] = ($mtproto && $param['type'] === 'Message' ? 'MT' : '').$param['type'];
            $param['type'] = $mtproto && $param['type'] === '%Message' ? '%MTMessage' : $param['type'];
            $this->by_id[$key]['params'][$kkey] = $param;
        }
    }
}
