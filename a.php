<?php
class a {}
$a = new a;
list($a->a, $a->b) = ['a', 'b'];
$c = 'lol';
$a->c = &$c;
$c = 'kek';
var_dump($a);

