<?php
/**
 * CombinedPromise module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

class CombinedPromise extends ImmediatePromise
{
    private $promises = [];
    private $count = 0;
    private $resolved = [];
    private $rejected = [];

    public function __construct($promises)
    {
        $this->promises = $promises;
        $this->count = count($this->promises);

        foreach ($this->promises as $id => $promise) {
            $promise->then(function ($result) use ($id) {
                $this->resolved[$id] = $result;
            }, function ($result) use ($id) {
                $this->rejected[$id] = $result;
            });
        }

        parent::__construct([$this, 'waitPromises']);
    }

    public function waitPromises()
    {
        foreach ($this->promises as $promise) {
            if ($promise->getState() === 'pending') {
                $promise->wait();
            }
        }

        $this->resolve(['resolved' => $this->resolved, 'rejected' => $this->rejected]);
    }
}
