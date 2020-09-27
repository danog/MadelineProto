<?php

use danog\MadelineProto\Lang;

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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
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

foreach (Lang::$lang as $code => &$currentLang) {
    if ($code === 'en') {
        continue;
    }
    $currentLang = \array_intersect_key($currentLang, Lang::$lang['en']);
}

$lang_code = \readline('Enter the language you whish to localize: ');

if (!isset(Lang::$lang[$lang_code])) {
    Lang::$lang[$lang_code] = Lang::$current_lang;
    echo 'New language detected!'.PHP_EOL.PHP_EOL;
} else {
    echo 'Completing localization of existing language'.PHP_EOL.PHP_EOL;
}
$count = \count(Lang::$lang[$lang_code]);
$curcount = 0;
\ksort(Lang::$current_lang);
foreach (Lang::$current_lang as $key => $value) {
    if (!(Lang::$lang[$lang_code][$key] ?? '')) {
        Lang::$lang[$lang_code][$key] = $value;
    }

    if (Lang::$lang[$lang_code][$key] === '' || (Lang::$lang[$lang_code][$key] === Lang::$lang['en'][$key] && Lang::$lang[$lang_code][$key] !== 'Bot')) {
        $value = Lang::$lang[$lang_code][$key];
        if (\in_array($key, ['v_error', 'v_tgerror'])) {
            $value = \hex2bin($value);
        }
        if ($value == '') {
            $value = $key;
        }
        Lang::$lang[$lang_code][$key] = \readline($value.' => ');
        /*
        if ($param_name === 'nonce' && $param_type === 'int128') {
            Lang::$lang[$lang_code][$key] = 'Random number for cryptographic security';
        } elseif ($param_name === 'server_nonce' && $param_type === 'int128') {
            Lang::$lang[$lang_code][$key] = 'Random number for cryptographic security, given by server';
        } elseif ($param_name === 'random_id' && $param_type === 'long') {
            Lang::$lang[$lang_code][$key] = 'Random number for cryptographic security';
        } else elseif (\strpos($value, 'Update ') === 0) {
            if (!$param_name && \strpos($key, 'object_') === 0) {
                $value = \str_replace('Update ', '', $value).' update';
            }
            //} elseif (ctype_lower($value[0])) {
        } else {
            Lang::$lang[$lang_code][$key] = \readline($value.' => ');
            if (Lang::$lang[$lang_code][$key] === '') {
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

                Lang::$lang[$lang_code][$key] = $l;
                echo 'Using default value '.Lang::$lang[$lang_code][$key].PHP_EOL;
            }
        }*/
        Lang::$lang[$lang_code][$key] = \ucfirst(Lang::$lang[$lang_code][$key]);
        if (\in_array($key, ['v_error', 'v_tgerror'])) {
            Lang::$lang[$lang_code][$key] = \bin2hex(Lang::$lang[$lang_code][$key]);
        }
        \file_put_contents('src/danog/MadelineProto/Lang.php', \sprintf($template, \var_export(Lang::$lang, true), \var_export(Lang::$lang['en'], true)));
        echo 'OK, '.($curcount * 100 / $count).'% done. edit src/danog/MadelineProto/Lang.php to fix mistakes.'.PHP_EOL;
    }
    $curcount++;
}
\file_put_contents('src/danog/MadelineProto/Lang.php', \sprintf($template, \var_export(Lang::$lang, true), \var_export(Lang::$lang['en'], true)));
echo 'OK. edit src/danog/MadelineProto/Lang.php to fix mistakes.'.PHP_EOL;
