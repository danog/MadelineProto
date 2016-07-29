#!/usr/bin/env php
<?php
require 'lib/danog/PHP/StructTools.php';
require 'lib/danog/PHP/StructClass.php';
require 'lib/danog/PHP/StructException.php';
require 'lib/danog/PHP/Struct.php';

echo \danog\PHP\Struct::pack('2cxbxBx?xhxHxixIxlxLxqxQxfxdx2xsx5pP',
'n', 'v', -127, 100, true, 333, 444, 232423, 234342, 999999999999, 999999999999, -88888888888888,
88888888888877, 2.2343,
3.03424, 'df', 'asdfghjkl', 1283912);die;
