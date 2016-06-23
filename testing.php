<?php
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libpy2php');
require_once ('libpy2php.php');
require_once ('os.php');
try {
    require_once ('configparser.php');
}
catch(ImportError $e) {
    require_once ('ConfigParser.php');
}
require_once ('mtproto.php');
$config = $configparser->ConfigParser();
if (!($config->read('credentials'))) {
    pyjslib_printnl('File \'credentials\' seems to not exist.');
    $exit(-1);
}
$ip = $config->get('App data', 'ip_address');
$port = $config->getint('App data', 'port');
$Session = mtproto::Session($ip, $port);
Session::create_auth_key();
$future_salts = py2php_kwargs_function_call('Session::method_call', ['get_future_salts'], ["num" => 3]);
pyjslib_printnl($future_salts);
