<?php

$service_port = getservbyname('www', 'tcp');
$address = gethostbyname('www.google.com');
var_dump(unpack('q', pack('l', 200).chr(0).chr(0).chr(0).chr(0)));
class a extends Volatile
{
    public $a = [];

    public function run()
    {
        $this->a[1] = new b();
        $this->a[1]->a['a'] = [];
        var_dump($this);
    }
}
class b extends \Volatile
{
    public $a = [];
}
class main extends Threaded
{
    public function __construct()
    {
        $this->a = new a();
        var_dump($this->a);
        $this->a->run();
 // One of the OH NOES (b) is printed here
    }

    public function run()
    {
        //        $this->a;
    }
}
$a = new main();
$pool = new Pool(1);
//$pool->submit($a); // One of the OH NOES (a) is printed here
