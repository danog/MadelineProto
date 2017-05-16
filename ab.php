<?php

$packet = file_get_contents('t');
var_dump(strrev(hash('crc32b', substr($packet, 0, -4), true)) !== substr($packet, -4));
