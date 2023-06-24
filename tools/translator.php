<?php declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

/** @internal */
final class Lang
{
    public static $lang = %s;

    // THIS WILL BE OVERWRITTEN BY $lang["en"]
    public static $current_lang = %s;
}';

foreach (Lang::$lang as $code => &$currentLang) {
    if ($code === 'en') {
        continue;
    }
    $currentLang = array_intersect_key($currentLang, Lang::$lang['en']);
}

$lang_code = readline('Enter the language you whish to localize: ');
if ($lang_code === '') {
    file_put_contents('src/Lang.php', sprintf($template, var_export(Lang::$lang, true), var_export(Lang::$lang['en'], true)));
    echo 'OK. edit src/Lang.php to fix mistakes.'.PHP_EOL;
    die;
}

if (!isset(Lang::$lang[$lang_code])) {
    Lang::$lang[$lang_code] = Lang::$current_lang;
    echo 'New language detected!'.PHP_EOL.PHP_EOL;
} else {
    echo 'Completing localization of existing language'.PHP_EOL.PHP_EOL;
}
$count = count(Lang::$lang[$lang_code]);
$curcount = 0;
ksort(Lang::$current_lang);
foreach (Lang::$current_lang as $key => $value) {
    if (!(Lang::$lang[$lang_code][$key] ?? '')) {
        Lang::$lang[$lang_code][$key] = $value;
    }

    if (Lang::$lang[$lang_code][$key] === '' || (Lang::$lang[$lang_code][$key] === Lang::$lang['en'][$key] && Lang::$lang[$lang_code][$key] !== 'Bot')) {
        $value = Lang::$lang[$lang_code][$key];
        if ($value == '') {
            $value = $key;
        }
        Lang::$lang[$lang_code][$key] = readline($value.' => ');
        Lang::$lang[$lang_code][$key] = ucfirst(Lang::$lang[$lang_code][$key]);
        file_put_contents('src/Lang.php', sprintf($template, var_export(Lang::$lang, true), var_export(Lang::$lang['en'], true)));
        echo 'OK, '.($curcount * 100 / $count).'% done. edit src/Lang.php to fix mistakes.'.PHP_EOL;
    }
    $curcount++;
}
file_put_contents('src/Lang.php', sprintf($template, var_export(Lang::$lang, true), var_export(Lang::$lang['en'], true)));
echo 'OK. edit src/Lang.php to fix mistakes.'.PHP_EOL;
