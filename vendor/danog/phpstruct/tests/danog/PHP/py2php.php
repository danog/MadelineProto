#!/usr/bin/env php
<?php
require 'lib/danog/PHP/StructTools.php';
require 'lib/danog/PHP/StructClass.php';
require 'lib/danog/PHP/StructException.php';
require 'lib/danog/PHP/Struct.php';

var_dump(\danog\PHP\Struct::unpack('2cxbxBx?xhxHxixIxlxLxqxQxfxdx2xsx5pP', shell_exec('tests/danog/PHP/py2php.py')));
