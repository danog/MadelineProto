<?php

set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).DIRECTORY_SEPARATOR.'libpy2php');
require_once 'libpy2php.php';
class chat
{
    public function __construct()
    {
        $this->_users = [];
    }

    public function add_user($user)
    {
        $this->_users += $user;
    }
}
