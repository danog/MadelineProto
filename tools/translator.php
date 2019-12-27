<?php

require 'vendor/autoload.php';

$template = '<?php
/**
 * Lang module
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
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

class Lang
{
    public static $lang = %s;

    // THIS WILL BE OVERWRITTEN BY $lang["en"]
    public static $current_lang = %s;
}';
function fromCamelCase($input)
{
    \preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
    $ret = $matches[0];
    foreach ($ret as &$match) {
        $match = $match == \strtoupper($match) ? \strtolower($match) : \lcfirst($match);
    }

    return \implode(' ', $ret);
}

$lang_code = \readline('Enter the language you whish to localize: ');

if (!isset(\danog\MadelineProto\Lang::$lang[$lang_code])) {
    \danog\MadelineProto\Lang::$lang[$lang_code] = \danog\MadelineProto\Lang::$current_lang;
    echo 'New language detected!'.PHP_EOL.PHP_EOL;
} else {
    echo 'Completing localization of existing language'.PHP_EOL.PHP_EOL;
}
$count = \count(\danog\MadelineProto\Lang::$lang[$lang_code]);
$curcount = 0;
\ksort(\danog\MadelineProto\Lang::$current_lang);
foreach (\danog\MadelineProto\Lang::$current_lang as $key => $value) {
    if (!isset(\danog\MadelineProto\Lang::$lang[$lang_code][$key])) {
        \danog\MadelineProto\Lang::$lang[$lang_code][$key] = $value;
    }
    \preg_match('/^[^_]+_(.*?)(?:_param_(.*)_type_(.*))?$/', $key, $matches);
    $method_name = isset($matches[1]) ? $matches[1] : '';
    $param_name = isset($matches[2]) ? $matches[2] : '';
    $param_type = isset($matches[3]) ? $matches[3] : '';
    if (isset(\danog\MadelineProto\MTProto::DISALLOWED_METHODS[$method_name])) {
        \danog\MadelineProto\Lang::$lang[$lang_code][$key] = \danog\MadelineProto\MTProto::DISALLOWED_METHODS[$method_name];
    }

    if (\danog\MadelineProto\Lang::$lang[$lang_code][$key] === $value && (
        $lang_code !== 'en' || $value == '' ||
        \strpos($value, 'You cannot use this method directly') === 0 ||
        \strpos($value, 'Update ') === 0 ||
        \ctype_lower($value[0])
    )) {
        $value = \danog\MadelineProto\Lang::$lang[$lang_code][$key];
        if (\in_array($key, ['v_error', 'v_tgerror'])) {
            $value = \hex2bin($value);
        }
        if ($value == '') {
            $value = $key;
        }
        \preg_match('/^[^_]+_(.*?)(?:_param_(.*)_type_(.*))?$/', $key, $matches);
        $method_name = isset($matches[1]) ? $matches[1] : '';
        $param_name = isset($matches[2]) ? $matches[2] : '';
        $param_type = isset($matches[3]) ? $matches[3] : '';
        if (isset(\danog\MadelineProto\MTProto::DISALLOWED_METHODS[$method_name])) {
            \danog\MadelineProto\Lang::$lang[$lang_code][$key] = \danog\MadelineProto\MTProto::DISALLOWED_METHODS[$method_name];
        }
        /*
        if ($param_name === 'nonce' && $param_type === 'int128') {
            \danog\MadelineProto\Lang::$lang[$lang_code][$key] = 'Random number for cryptographic security';
        } elseif ($param_name === 'server_nonce' && $param_type === 'int128') {
            \danog\MadelineProto\Lang::$lang[$lang_code][$key] = 'Random number for cryptographic security, given by server';
        } elseif ($param_name === 'random_id' && $param_type === 'long') {
            \danog\MadelineProto\Lang::$lang[$lang_code][$key] = 'Random number for cryptographic security';
        } else elseif (\strpos($value, 'Update ') === 0) {
            if (!$param_name && \strpos($key, 'object_') === 0) {
                $value = \str_replace('Update ', '', $value).' update';
            }
            //} elseif (ctype_lower($value[0])) {
        } else {
            \danog\MadelineProto\Lang::$lang[$lang_code][$key] = \readline($value.' => ');
            if (\danog\MadelineProto\Lang::$lang[$lang_code][$key] === '') {
                if ($param_name) {
                    $l = \str_replace('_', ' ', $param_name);
                } else {
                    $l = \explode('.', $method_name);
                    $l = fromCamelCase(\end($l));
                }
                $l = \ucfirst(\strtolower($l));
                if (\preg_match('/ empty$/', $l)) {
                    $l = 'Empty '.\strtolower(\preg_replace('/ empty$/', '', $l));
                }
                foreach (['id', 'url', 'dc'] as $upper) {
                    $l = \str_replace([\ucfirst($upper), ' '.$upper], [\strtoupper($upper), ' '.\strtoupper($upper)], $l);
                }

                if (\in_array($param_type, ['Bool', 'true', 'false'])) {
                    $l .= '?';
                }

                \danog\MadelineProto\Lang::$lang[$lang_code][$key] = $l;
                echo 'Using default value '.\danog\MadelineProto\Lang::$lang[$lang_code][$key].PHP_EOL;
            }
        }*/
        \danog\MadelineProto\Lang::$lang[$lang_code][$key] = \ucfirst(\danog\MadelineProto\Lang::$lang[$lang_code][$key]);
        if (\in_array($key, ['v_error', 'v_tgerror'])) {
            \danog\MadelineProto\Lang::$lang[$lang_code][$key] = \bin2hex(\danog\MadelineProto\Lang::$lang[$lang_code][$key]);
        }
        \file_put_contents('src/danog/MadelineProto/Lang.php', \sprintf($template, \var_export(\danog\MadelineProto\Lang::$lang, true), \var_export(\danog\MadelineProto\Lang::$lang['en'], true)));
        echo 'OK, '.($curcount * 100 / $count).'% done. edit src/danog/MadelineProto/Lang.php to fix mistakes.'.PHP_EOL;
    }
    $curcount++;
}
\file_put_contents('src/danog/MadelineProto/Lang.php', \sprintf($template, \var_export(\danog\MadelineProto\Lang::$lang, true), \var_export(\danog\MadelineProto\Lang::$lang['en'], true)));
echo 'OK. edit src/danog/MadelineProto/Lang.php to fix mistakes.'.PHP_EOL;
