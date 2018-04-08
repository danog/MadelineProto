<?php

if (!file_exists('madeline.phar') || !file_exists('madeline.phar.version') || file_get_contents('madeline.phar.version') !== file_get_contents('https://phar.madelineproto.xyz/release?v=new')) {
    file_put_contents('madeline.phar', file_get_contents('https://phar.madelineproto.xyz/madeline.phar?v=new'));
    file_put_contents('madeline.phar.version', file_get_contents('https://phar.madelineproto.xyz/release?v=new'));
}

require 'madeline.phar';
