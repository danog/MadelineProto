#!/usr/bin/env php
<?php
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

if (!isset($argv[3])) {
    echo 'Usage: '.$argv[0].' inputDir output.phar ref'.PHP_EOL;
    die(1);
}

@unlink($argv[2]);

$p = new Phar(__DIR__.'/../'.$argv[2], 0, $argv[2]);
$p->buildFromDirectory(realpath($argv[1]), '/^((?!tests).)*(\.php|\.py|\.exe|\.tl|\.json|\.dat|\.h)$/i');

$p->setStub('<?php

if ((PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION < 1) || PHP_MAJOR_VERSION <= 7) {
    die("MadelineProto requires at least PHP 8.1.".PHP_EOL);
}
if (PHP_INT_SIZE < 8) {
    die("A 64-bit build of PHP is required to run MadelineProto, PHP 8.1 is required.".PHP_EOL);
}

if (\defined("MADELINE_PHAR")) {
    throw new \Exception("Please do not include madeline.phar twice, use require_once \'madeline.phar\';!");
}

if (!\defined(\'MADELINE_ALLOW_COMPOSER\') && \class_exists(\Composer\Autoload\ClassLoader::class)) {
    throw new \Exception(\'Composer autoloader detected: madeline.phar is incompatible with Composer, please install MadelineProto using composer: https://docs.madelineproto.xyz/docs/INSTALLATION.html#composer-from-existing-project\');
}

\define(\'MADELINE_PHAR\', __FILE__);

if (defined("MADELINE_REAL_ROOT")) {
    @chdir(MADELINE_REAL_ROOT);
} else {
    $backtrace = \debug_backtrace(0);
    if (\count($backtrace) === 0) {
        if (isset($GLOBALS["argv"]) && !empty($GLOBALS["argv"])) {
            $arguments = \array_slice($GLOBALS["argv"], 1);
        } elseif (isset($_GET["argv"]) && !empty($_GET["argv"])) {
            $arguments = $_GET["argv"];
        } else {
            $arguments = [];
        }
        if (\count($arguments) >= 2) {
            \define(\MADELINE_WORKER_TYPE::class, \array_shift($arguments));
            \define(\MADELINE_WORKER_ARGS::class, $arguments);
        } else {
            die("MadelineProto loader: you must include this file in another PHP script, see https://docs.madelineproto.xyz for more info.".PHP_EOL);
        }
        \define("MADELINE_REAL_ROOT", __DIR__);
        @chdir(\MADELINE_REAL_ROOT);
    }
}

Phar::interceptFileFuncs();
Phar::mapPhar("'.$argv[2].'"); 
$result = require_once "phar://'.$argv[2].'/vendor/autoload.php"; 

if (\defined("MADELINE_WORKER_TYPE") && \constant("MADELINE_WORKER_TYPE") === "madeline-ipc") {
    require_once "phar://'.$argv[2].'/vendor/danog/madelineproto/src/Ipc/Runner/entry.php";
}

return $result;

__HALT_COMPILER(); ?>');
