<?php

if (!file_exists('madeline.phar')) {
    file_put_contents('madeline.phar', file_get_contents('https://phar.madelineproto.xyz/madeline.phar?v=new'));
}
require 'madeline.phar';

if (trim(file_get_contents('phar://madeline.phar/.git/refs/heads/master')) !== trim(file_get_contents('https://phar.madelineproto.xyz/release?v=new'))) {
    file_put_contents('madeline.phar', file_get_contents('https://phar.madelineproto.xyz/madeline.phar?v=new'));
}
