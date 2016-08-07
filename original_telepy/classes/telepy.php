<?php

set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).DIRECTORY_SEPARATOR.'libpy2php');
require_once 'libpy2php.php';
class telepy
{
    public function __construct()
    {
        try {
            require_once 'configparser.php';
        } catch (ImportError $e) {
            require_once 'ConfigParser.php';
        }
        require_once 'mtproto.php';
        $this->_config = $configparser->ConfigParser();
        if (!($this->_config->read('credentials'))) {
            pyjslib_printnl('File \'credentials\' seems to not exist.');
            $exit(-1);
        }
        $ip = $this->_config->get('App data', 'ip_address');
        $port = $this->_config->getint('App data', 'port');
        $this->_session = $mtproto->Session($ip, $port);
        $this->_session->create_auth_key();
        $__temp22 = py2php_kwargs_method_call($this->_session, 'method_call', ['get_future_salts'], ['num' => 3]);
        $this->_salt = $__temp22;
        $future_salts = $__temp22;
    }
}
