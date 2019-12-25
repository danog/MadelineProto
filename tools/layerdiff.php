<?php

use danog\MadelineProto\Logger;
use danog\MadelineProto\TL\TL;

/*
Copyright 2016-2019 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
 */

require 'vendor/autoload.php';
$param = 1;
\danog\MadelineProto\Logger::constructor($param);
$logger = \danog\MadelineProto\Logger::$default;

\set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);

if ($argc !== 3) {
    die("Usage: {$argv[0]} layernumberold layernumbernew\n");
}
/**
 * Get TL info of layer.
 *
 * @param int $layer Layer number
 *
 * @return void
 */
function getTL($layer)
{
    $layer = __DIR__."/../src/danog/MadelineProto/TL_telegram_v$layer.tl";
    $layer = new class($layer) {
        use TL;

        public function __construct($layer)
        {
            $this->logger = Logger::$default;
            $this->construct_TL(['telegram' => $layer]);
        }
    };

    return ['methods' => $layer->methods, 'constructors' => $layer->constructors];
}
function getUrl($constructor, $type)
{
    $changed = \str_replace('.', '_', $constructor);

    //return "[$constructor](https://github.com/danog/MadelineProtoDocs/blob/geochats/docs/API_docs/$type/$changed.md)";
    return "[$constructor](https://docs.madelineproto.xyz/API_docs/$type/$changed.html)";
}
$old = getTL($argv[1]);
$new = getTL($argv[2]);
$res = '';

foreach (['methods', 'constructors'] as $type) {
    $finder = $type === 'methods' ? 'findByMethod' : 'findByPredicate';
    $key = $type === 'methods' ? 'method' : 'predicate';

    // New constructors
    $res .= "\n\nNew ".\ucfirst($type)."\n";
    foreach ($new[$type]->by_id as $constructor) {
        $name = $constructor[$key];
        if (!$old[$type]->$finder($name)) {
            $name = getUrl($name, $type);
            $res .= "Added $name\n";
        }
    }

    // Changed constructors
    $res .= "\n\nChanged ".\ucfirst($type)."\n";
    foreach ($new[$type]->by_id as $constructor) {
        $name = $constructor[$key];
        $constructor['id'] = $new[$type]->$finder($name)['id'];
        if ($old[$type]->$finder($name)) {
            $new_args = $constructor['params'];
            $old_args = $old[$type]->$finder($name)['params'];
            $final_new_args = [];
            $final_old_args = [];
            foreach ($new_args as $arg) {
                $final_new_args[$arg['name']] = $arg['type'];
            }
            foreach ($old_args as $arg) {
                $final_old_args[$arg['name']] = $arg['type'];
            }

            $url = getUrl($name, $type);
            foreach ($final_new_args as $name => $ttype) {
                if (!isset($final_old_args[$name]) && $name !== 'flags') {
                    $res .= "Added $name param to $url\n";
                }
            }
            foreach ($final_old_args as $name => $ttype) {
                if (!isset($final_new_args[$name]) && $name !== 'flags') {
                    $res .= "Removed $name param from $url\n";
                }
            }
        }
    }

    // Deleted constructors
    $res .= "\n\nDeleted ".\ucfirst($type)."\n";
    foreach ($old[$type]->by_id as $constructor) {
        $name = $constructor[$key];
        if (!$new[$type]->$finder($name)) {
            $res .= "Removed $name\n";
        }
    }
}

echo $res;
