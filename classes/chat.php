<?php
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libpy2php');
require_once ('libpy2php.php');
class Chat {
    function __construct() {
        $this->_users = [];
    }
    function add_user($user) {
        $this->_users+= $user;
    }
}
