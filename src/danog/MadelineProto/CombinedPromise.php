<?php
/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
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

                if (count($this->resolved) + count($this->rejected) === $this->count) {
                    $this->resolve(['resolved' => $this->resolved, 'rejected' => $this->rejected]);
                }
            }, function ($result) use ($id) {
                $this->resolved[$id] = $result;

                if (count($this->resolved) + count($this->rejected) === $this->count) {
                    $this->resolve(['resolved' => $this->resolved, 'rejected' => $this->rejected]);
                }
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
    }
}
