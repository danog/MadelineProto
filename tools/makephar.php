#!/usr/bin/env php
<?php
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

if (!isset($argv[3])) {
    echo 'Usage: '.$argv[0].' inputDir output.phar ref'.PHP_EOL;
    die(1);
}

@\unlink($argv[2]);

$p = new Phar(__DIR__.'/../'.$argv[2], 0, $argv[2]);
$p->buildFromDirectory(\realpath($argv[1]), '/^((?!tests).)*(\.php|\.py|\.tl|\.json|\.dat)$/i');
$p->addFromString('vendor/danog/madelineproto/.git/refs/heads/master', $argv[3]);
$p->addFromString('.git/refs/heads/master', $argv[3]);

$p->setStub('<?php
$backtrace = debug_backtrace();
if (!isset($backtrace[0]["file"]) || !in_array(basename($backtrace[0]["file"]), ["madeline.php", "phar.php", "testing.php"])) {
    echo("madeline.phar cannot be required manually: use the automatic loader, instead: https://docs.madelineproto.xyz/docs/INSTALLATION.html#simple".PHP_EOL);
    die(1);
}
if (isset($backtrace[1]["file"])) {
    @chdir(dirname($backtrace[1]["file"]));
}
if ($contents = file_get_contents("https://phar.madelineproto.xyz/phar.php?v=new")) {
    file_put_contents($backtrace[0]["file"], $contents);
}

Phar::interceptFileFuncs(); 
Phar::mapPhar("'.$argv[2].'"); 
return require_once "phar://'.$argv[2].'/vendor/autoload.php"; 

__HALT_COMPILER(); ?>');
