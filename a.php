<?php

require 'vendor/autoload.php';
class a extends Volatile
{
    //    public $a = [];
    public function __construct()
    {
        $this->a = 'le';
    }
}
$a = new a();

var_dump($a);
