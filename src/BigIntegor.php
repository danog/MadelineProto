<?php

namespace phpseclib\Math;

if (PHP_MAJOR_VERSION < 7 && !(class_exists('\Phar') && \Phar::running())) {
    throw new \Exception('MadelineProto requires php 7 to run');
}

class BigIntegor
{
}
