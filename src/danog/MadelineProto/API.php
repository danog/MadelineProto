<?php

namespace danog\MadelineProto;

class API
{
    public $session;

    public function __construct($login, $params = [])
    {
        $this->session = new Session($params);
        $this->session->create_auth_key();
        $future_salts = $this->session->method_call('get_future_salts', 3);
        pyjslib_printnl($future_salts);
    }

    public function __destruct()
    {
        unset($this->session);
    }

    public function __call($name, $arguments)
    {
        return $session->method_call($name, $arguments);
    }
}
