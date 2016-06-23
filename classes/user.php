<?php
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libpy2php');
require_once ('libpy2php.php');
class User {
    public $me = null;
    // current connected user
    public $friends = [];
    // current connected user's friends
    function __construct($uid) {
        $this->uid = $uid;
    }
}
