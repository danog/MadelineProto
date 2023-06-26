<?php declare(strict_types=1);

`wget https://weblate.madelineproto.xyz/download/madelineproto/madelineproto/?format=zip -O langs.zip`;
`unzip -o langs.zip`;
`mv madelineproto/madelineproto/langs/* langs`;
`rm -rf madelineproto langs.zip`;

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
    public static array $lang = %s;

    // THIS WILL BE OVERWRITTEN BY $lang["en"]
    public static array $current_lang = %s;
}';

$base = json_decode(file_get_contents("langs/en.json"), true);

$langs = [];
foreach (glob("langs/*.json") as $lang) {
    $code = basename($lang, '.json');
    $langs[$code] = array_merge($base, json_decode(file_get_contents($lang), true));
    ksort($langs[$code]);
}

file_put_contents('src/Lang.php', sprintf($template, var_export($langs, true), var_export($langs['en'], true)));
