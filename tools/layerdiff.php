<?php declare(strict_types=1);

use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Logger as SettingsLogger;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\Tools;

/*
Copyright 2016-2020 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
 */

require 'vendor/autoload.php';
$logger = new Logger(new SettingsLogger);

set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);

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
    $layerFile = __DIR__."/../schemas/TL_telegram_v$layer.tl";
    $layer = new TL();
    $layer->init((new TLSchema)->setAPISchema($layerFile)->setSecretSchema(''));

    return ['methods' => $layer->getMethods(), 'constructors' => $layer->getConstructors()];
}
function getUrl($constructor, $type)
{
    $orig = $constructor;
    $constructor = Tools::markdownEscape($constructor);

    return "[$constructor](https://docs.madelineproto.xyz/API_docs/$type/$orig.html)";
}
$old = getTL($argv[1]);
$new = getTL($argv[2]);
$res = '';

foreach (['methods', 'constructors'] as $type) {
    $finder = $type === 'methods' ? 'findByMethod' : 'findByPredicate';
    $key = $type === 'methods' ? 'method' : 'predicate';

    // New constructors
    $res .= "\n\nNew ".ucfirst($type).":\n";
    foreach ($new[$type]->by_id as $constructor) {
        $name = $constructor[$key];
        if (!$old[$type]->$finder($name)) {
            $name = getUrl($name, $type);
            $res .= "- $name\n";
        }
    }

    // Changed constructors
    $res .= "\n\nChanged ".ucfirst($type).":\n";
    foreach ($new[$type]->by_id as $constructor) {
        $name = $constructor[$key];
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
                if (!isset($final_old_args[$name]) && $name !== 'flags' && $name !== 'flags2') {
                    $name = Tools::markdownEscape($name);
                    $res .= "Added $name param to $url\n";
                }
            }
            foreach ($final_old_args as $name => $ttype) {
                if (!isset($final_new_args[$name]) && $name !== 'flags' && $name !== 'flags2') {
                    $name = Tools::markdownEscape($name);
                    $res .= "Removed $name param from $url\n";
                }
            }
        }
    }

    // Deleted constructors
    $res .= "\n\nDeleted ".ucfirst($type).":\n";
    foreach ($old[$type]->by_id as $constructor) {
        $name = $constructor[$key];
        if (!$new[$type]->$finder($name)) {
            $name = Tools::markdownEscape($name);
            $res .= "- $name\n";
        }
    }
}

$bot = new \danog\MadelineProto\API('testing.madeline');
$bot->start();

foreach (explode("\n\n", $res) as $chunk) {
    if (!$chunk || !trim(explode(':', $chunk)[1])) {
        continue;
    }
    $bot->messages->sendMessage(['peer' => 'danogentili', 'message' => $chunk, 'parse_mode' => 'markdown']);
}
