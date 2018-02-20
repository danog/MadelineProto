#!/usr/bin/env php
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

require 'vendor/autoload.php';
use Spatie\Php7to5\DirectoryConverter;

if (!isset($argv[1])) {
    die('This script requires a parameter with the commit number to turn into a phar');
}

function rimraf($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                if (is_dir($dir.'/'.$object)) {
                    rimraf($dir.'/'.$object);
                } else {
                    unlink($dir.'/'.$object);
                }
            }
        }
        rmdir($dir);
    }
}

@unlink('madeline.phar');
rimraf('phar');
rimraf('composer');
mkdir('phar');
mkdir('composer');
chdir('composer');
file_put_contents('composer.json', '{
    "name": "danog/madelineprototests",
    "minimum-stability":"dev",
    "require": {
        "danog/madelineproto": "dev-master#'.$argv[1].'"
    },
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/danog/phpseclib"
        }
    ],
    "authors": [
        {
            "name": "Daniil Gentili",
            "email": "daniil@daniil.it"
        }
    ]
}');
shell_exec('composer update');

(new DirectoryConverter(__DIR__.'/composer', ['.php']))->alsoCopyNonPhpFiles()->savePhp5FilesTo(__DIR__.'/phar');

$p = new Phar(__DIR__.'/madeline.phar', 0, 'madeline.phar');
$p->buildFromDirectory(__DIR__.'/phar', '/^((?!tests).)*(\.php|\.py|\.tl|\.json)$/i');
$p->addFromString('.git/refs/heads/master', file_get_contents(__DIR__.'/.git/refs/heads/master'));

$p->setStub('<?php Phar::interceptFileFuncs(); Phar::mapPhar("madeline.phar"); require_once "phar://madeline.phar/vendor/autoload.php"; __HALT_COMPILER(); ?>');
