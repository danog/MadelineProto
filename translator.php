<?php

require 'vendor/autoload.php';

$template = '<?php
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

class Lang
{
    public static $lang = %s;

    // THIS WILL BE OVERWRITTEN BY $lang["en"]
    public static $current_lang = %s;
}';

$lang_code = readline('Enter the language you whish to localize: ');

if (!isset(\danog\MadelineProto\Lang::$lang[$lang_code])) {
    \danog\MadelineProto\Lang::$lang[$lang_code] = \danog\MadelineProto\Lang::$current_lang;
    echo 'New language detected!'.PHP_EOL.PHP_EOL;
} else {
    echo 'Completing localization of existing language'.PHP_EOL.PHP_EOL;
}
$count = count(\danog\MadelineProto\Lang::$lang[$lang_code]);
$curcount = 0;
foreach (\danog\MadelineProto\Lang::$current_lang as $key => $value) {
    if (!isset(\danog\MadelineProto\Lang::$lang[$lang_code][$key])) {
        \danog\MadelineProto\Lang::$lang[$lang_code][$key] = $value;
    }
    if (\danog\MadelineProto\Lang::$lang[$lang_code][$key] === $value) {
        $value = \danog\MadelineProto\Lang::$lang[$lang_code][$key];
        if (in_array($key, ['v_error', 'v_tgerror'])) {
            $value = hex2bin($value);
        }
        \danog\MadelineProto\Lang::$lang[$lang_code][$key] = readline($value.' => ');
        if (in_array($key, ['v_error', 'v_tgerror'])) {
            \danog\MadelineProto\Lang::$lang[$lang_code][$key] = bin2hex(\danog\MadelineProto\Lang::$lang[$lang_code][$key]);
        }
        file_put_contents('src/danog/MadelineProto/Lang.php', sprintf($template, var_export(\danog\MadelineProto\Lang::$lang, true), var_export(\danog\MadelineProto\Lang::$lang['en'], true)));
        echo 'OK, '.($curcount * 100 / $count).'% done. edit src/danog/MadelineProto/Lang.php to fix mistakes.'.PHP_EOL;
    }
    $curcount++;
}
file_put_contents('src/danog/MadelineProto/Lang.php', sprintf($template, var_export(\danog\MadelineProto\Lang::$lang, true), var_export(\danog\MadelineProto\Lang::$lang['en'], true)));
echo 'OK. edit src/danog/MadelineProto/Lang.php to fix mistakes.'.PHP_EOL;
