<?php

set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).DIRECTORY_SEPARATOR.'libpy2php');
require_once 'libpy2php.php';
require_once 'os.php';
require_once 'mtproto.php';
$config = parse_ini_file('credentials', true);
if (!($config)) {
    pyjslib_printnl('File \'credentials\' seems to not exist.');
    $exit(-1);
}
$ip = $config['App data']['ip_address'];
$port = $config['App data']['port'];
$Session = new Session($ip, $port);
$Session->create_auth_key();
$future_salts = $Session->method_call('get_future_salts', 3);
pyjslib_printnl($future_salts);
