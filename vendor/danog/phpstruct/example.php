<?php

//require('vendor/autoload.php');
require 'lib/danog/PHP/StructTools.php';
require 'lib/danog/PHP/StructClass.php';
require 'lib/danog/PHP/StructException.php';
require 'lib/danog/PHP/Struct.php';
var_dump(bin2hex(pack('J', 18446744073709550590)));
var_dump(bin2hex(\danog\PHP\Struct::pack('Q', 18446744073709550590)));

var_dump(\danog\PHP\Struct::calcsize('>l'));

// Dynamic usage with format definition on istantiation
$struct = new \danog\PHP\StructClass('2cxbxBx?xhxHxixIxlxLxqxQxnxNxfxdx2sx5pP');
var_dump($struct->unpack($struct->pack('n', 'v', -127, 255, true, -32767, 65535, -2147483647, 4294967295, 999999, 9999999, -9223372036854775807, 18446744073709550590, 34434, 344434, 2.2343, 3.03424, 'df', 'asdfghjkl', 1283912)));

// Dynamic usage with format definition on function call
$struct = new \danog\PHP\StructClass();
var_dump($struct->unpack('2cxbxBx?xhxHxixIxlxLxqxQxnxNxfxdx2sx5pP', $struct->pack('2cxbxBx?xhxHxixIxlxLxqxQxnxNxfxdx2sx5pP', 'n', 'v', -127, 255, true, -32767, 65535, -2147483647, 4294967295, 999999, 9999999, -9223372036854775807, 18446744073709550590, 34434, 344434, 2.2343, 3.03424, 'df', 'asdfghjkl', 1283912)));

// Static usage
var_dump(\danog\PHP\Struct::unpack('2cxbxBx?xhxHxixIxlxLxqxQxnxNxfxdx2sx5pP', \danog\PHP\Struct::pack('2cxbxBx?xhxHxixIxlxLxqxQxnxNxfxdx2sx5pP', 'n', 'v', -127, 255, true, -32767, 65535, -2147483647, 4294967295, 999999, 9999999, -922337203685, 18446744073709550590, 34434, 344434, 2.2343, 3.03424, 'df', 'asdfghjkl', 1283912)));

// S'more examples
var_dump(\danog\PHP\Struct::calcsize('f'));
var_dump(bin2hex(pack('J', 999998999999999)));
var_dump(bin2hex(\danog\PHP\Struct::pack('>Q', 999998999999999)));
